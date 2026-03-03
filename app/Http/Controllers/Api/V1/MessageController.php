<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
            return response()->json([
                'success' => false,
                'message' => 'Chat is closed.',
                'data'    => null,
            ], 422);
        }

        // Quota check
        if ($tenant->token_quota > 0 && $tenant->tokens_used >= $tenant->token_quota) {
            return response()->json([
                'success' => false,
                'message' => 'quota_exceeded',
                'data'    => ['remaining' => 0],
            ], 402);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        // Save user message
        $userMessage = Message::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'role'      => 'user',
            'content'   => $validated['content'],
            'tokens'    => (int) ceil(mb_strlen($validated['content']) / 4),
        ]);

        // Update chat title on first message
        if ($chat->title === 'New Chat' || $chat->title === null) {
            $chat->update(['title' => mb_substr($validated['content'], 0, 60)]);
        }

        // Dispatch AI job
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

        $latest = Message::where('chat_id', $chat->id)
            ->where('role', 'assistant')
            ->latest()
            ->first();

        return response()->json([
            'success'      => true,
            'message'      => 'OK',
            'data'         => [
                'has_response' => $latest !== null,
                'message'      => $latest,
            ],
        ]);
    }
}