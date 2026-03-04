<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateChatTitleJob;
use App\Jobs\SendMessageJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private QuotaService $quota) {}

    public function store(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id !== $project->id, 404);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return back()->withErrors(['message' => 'This chat is closed.']);
        }

        if (!$this->quota->check($tenant)) {
            return back()
                ->withErrors(['message' => 'Token quota exceeded. Please upgrade your plan.'])
                ->with('quota_exceeded', true);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        Message::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'role'      => 'user',
            'content'   => $validated['content'],
            'tokens'    => (int) ceil(mb_strlen($validated['content']) / 4),
        ]);

        // Auto-generate title via Ollama (only on first message)
        if ($chat->title === 'New Chat' || empty($chat->title)) {
            GenerateChatTitleJob::dispatch(
                $chat->id,
                $validated['content'],
                $project->model
            );
        }

        SendMessageJob::dispatch($chat->id, $tenant->id, $request->user()->id);

        return back();
    }

    public function index(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return response()->json([
            'messages' => $messages,
            'chat'     => [
                'id'           => $chat->id,
                'title'        => $chat->fresh()->title,
                'status'       => $chat->fresh()->status,
                'total_tokens' => $chat->fresh()->total_tokens,
            ],
        ]);
    }
}