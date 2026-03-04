<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChatTitleJob;
use App\Jobs\SendMessageJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $messages->items(),
            'meta'    => [
                'current_page' => $messages->currentPage(),
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return response()->json(['success' => false, 'message' => 'Chat is closed.'], 422);
        }

        if ($tenant->token_quota > 0 && $tenant->tokens_used >= $tenant->token_quota) {
            return response()->json([
                'success' => false,
                'message' => 'quota_exceeded',
                'data'    => ['remaining' => 0],
            ], 402);
        }

        $validated = $request->validate(['content' => ['required', 'string', 'max:4000']]);

        $userMessage = Message::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'role'      => 'user',
            'content'   => $validated['content'],
            'tokens'    => (int) ceil(mb_strlen($validated['content']) / 4),
        ]);

        // Auto-generate title on first message
        if ($chat->title === 'New Chat' || empty($chat->title)) {
            GenerateChatTitleJob::dispatch(
                $chat->id,
                $validated['content'],
                $project->model
            );
        }

        SendMessageJob::dispatch($chat->id, $tenant->id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Message sent. AI is processing.',
            'data'    => [
                'message_id' => $userMessage->id,
                'status'     => 'processing',
            ],
        ], 202);
    }

    public function status(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id !== $tenant->id, 403);

        $last = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'success'      => true,
            'has_response' => $last && $last->role === 'assistant',
            'message'      => $last && $last->role === 'assistant' ? $last : null,
            'chat_title'   => $chat->fresh()->title,
        ]);
    }
}