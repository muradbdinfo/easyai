<?php

// FILE: app/Http/Controllers/Api/V1/MessageController.php
// REPLACES existing file entirely
// Steps 14+15: attachment_id support in store, eager-load in index

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessageJob;
use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * GET /api/v1/projects/{project}/chats/{chat}/messages
     * Returns paginated messages with attachment details eager-loaded.
     */
    public function index(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->with(['attachment:id,type,original_name,url,extension,meta,file_size'])
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

    /**
     * POST /api/v1/projects/{project}/chats/{chat}/messages
     * Send a message (optionally with a pre-uploaded attachment_id).
     */
    public function store(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

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
            'content'       => ['required', 'string', 'max:4000'],
            'attachment_id' => ['nullable', 'integer', 'exists:chat_attachments,id'],
        ]);

        // ── Save user message ─────────────────────────────────────
        $userMessage = Message::create([
            'chat_id'        => $chat->id,
            'tenant_id'      => $tenant->id,
            'role'           => 'user',
            'content'        => $validated['content'],
            'tokens'         => (int) ceil(mb_strlen($validated['content']) / 4),
            'has_attachment' => !empty($validated['attachment_id']),
        ]);

        // ── Link attachment ───────────────────────────────────────
        $attachmentId = null;
        if (!empty($validated['attachment_id'])) {
            $attachment = ChatAttachment::find($validated['attachment_id']);

            if (
                $attachment
                && $attachment->tenant_id  === $tenant->id
                && $attachment->chat_id    === $chat->id
                && $attachment->message_id === null
            ) {
                $attachment->update(['message_id' => $userMessage->id]);
                $userMessage->update(['attachment_id' => $attachment->id]);
                $attachmentId = $attachment->id;
            }
        }

        // ── Update chat title on first message ────────────────────
        if ($chat->title === 'New Chat' || $chat->title === null) {
            $chat->update(['title' => mb_substr($validated['content'], 0, 60)]);
        }

        // ── Dispatch AI job ───────────────────────────────────────
        SendMessageJob::dispatch(
            $chat->id,
            $tenant->id,
            $request->user()->id,
            $attachmentId
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent. AI is processing.',
            'data'    => [
                'message_id'    => $userMessage->id,
                'attachment_id' => $attachmentId,
                'status'        => 'processing',
            ],
        ], 202);
    }

    /**
     * GET /api/v1/projects/{project}/chats/{chat}/messages/status
     * Poll to check if the latest assistant response has arrived.
     */
    public function status(Request $request, Project $project, Chat $chat): JsonResponse
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $latest = Message::where('chat_id', $chat->id)
            ->where('role', 'assistant')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'has_response' => $latest !== null,
                'message'      => $latest,
            ],
        ]);
    }
}