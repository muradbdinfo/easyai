<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateChatTitleJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private QuotaService $quota) {}

    /**
     * Save user message only.
     * AI response is handled directly by StreamController via SSE.
     */
    public function store(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return response()->json(['success' => false, 'message' => 'Chat is closed.'], 422);
        }

        if (!$this->quota->check($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'quota_exceeded',
                'data'    => ['remaining' => 0],
            ], 402);
        }

        $validated = $request->validate([
            'content'       => ['required', 'string', 'max:4000'],
            'attachment_id' => ['nullable', 'integer'],
        ]);

        $userMsg = Message::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'role'      => 'user',
            'content'   => $validated['content'],
            'tokens'    => (int) ceil(mb_strlen($validated['content']) / 4),
        ]);

        // Auto-generate chat title on first message (queued job)
        if ($chat->title === 'New Chat' || empty($chat->title)) {
            GenerateChatTitleJob::dispatch(
                $chat->id,
                $validated['content'],
                $project->model
            );
        }

        // NOTE: No SendMessageJob — AI response handled by StreamController
        return response()->json([
            'success'    => true,
            'message_id' => $userMsg->id,
        ]);
    }

    /**
     * Return messages for a chat (used as fallback / history load).
     */
    public function index(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get();

        $fresh = $chat->fresh();

        return response()->json([
            'messages' => $messages,
            'chat'     => [
                'id'           => $fresh->id,
                'title'        => $fresh->title,
                'status'       => $fresh->status,
                'total_tokens' => $fresh->total_tokens,
            ],
        ]);
    }
}