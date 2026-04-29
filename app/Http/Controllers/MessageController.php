<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateChatTitleJob;
use App\Jobs\SendMessageJob;
use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Message;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private QuotaService $quota) {}

    /**
     * Save the user's message (and link attachment if provided).
     * AI response is handled by StreamController via SSE — no job dispatch.
     *
     * POST /projects/{project}/chats/{chat}/messages
     */
    public function store(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->project_id   !== $project->id, 404);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Chat is closed.',
            ], 422);
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
            'attachment_id' => ['nullable', 'integer', 'exists:chat_attachments,id'],
        ]);

        // Resolve and validate ownership of the attachment
        $attachment = null;
        if (!empty($validated['attachment_id'])) {
            $attachment = ChatAttachment::find($validated['attachment_id']);

            // Security: make sure this attachment belongs to this tenant & chat
            if (
                !$attachment
                || $attachment->tenant_id !== $tenant->id
                || $attachment->chat_id   !== $chat->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid attachment.',
                ], 422);
            }
        }

        // Save user message
        $userMsg = Message::create([
            'chat_id'        => $chat->id,
            'tenant_id'      => $tenant->id,
            'role'           => 'user',
            'content'        => $validated['content'],
            'tokens'         => (int) ceil(mb_strlen($validated['content']) / 4),
            'attachment_id'  => $attachment?->id,
            'has_attachment' => $attachment !== null,
        ]);

        // Link the attachment to this message (so it's permanent)
        if ($attachment) {
            $attachment->update(['message_id' => $userMsg->id]);
        }

        // Auto-generate title on first real message
        if ($chat->title === 'New Chat' || empty($chat->title)) {
            GenerateChatTitleJob::dispatch(
                $chat->id,
                $validated['content'],
                $project->model
            );
        }

        // ── n8n: fire message.sent event ──────────────────────────────
try {
    (new \App\Services\N8nService())->fire($tenant, 'message_sent', [
        'chat_id'    => $chat->id,
        'chat_title' => $chat->title,
        'project'    => $project->name,
        'content'    => mb_substr($validated['content'], 0, 500),
        'user_id'    => $request->user()->id,
    ]);
} catch (\Throwable) {}

        // NOTE: No SendMessageJob dispatched here.
        // AI response is streamed directly by StreamController via SSE.

        return response()->json([
            'success'    => true,
            'message_id' => $userMsg->id,
        ]);
    }

    /**
     * Return recent messages for a chat (used as fallback / history refresh).
     *
     * GET /projects/{project}/chats/{chat}/messages
     */
    public function index(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->with('attachment')
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get()
            ->map(function (Message $msg) {
                $data = $msg->toArray();

                // Append attachment data for frontend rendering
                if ($msg->has_attachment && $msg->attachment) {
                    $data['attachment'] = [
                        'type'          => $msg->attachment->type,
                        'original_name' => $msg->attachment->original_name,
                        'extension'     => $msg->attachment->extension,
                        'url'           => $msg->attachment->getPublicUrl(),
                        'file_size'     => $msg->attachment->file_size,
                    ];
                }

                return $data;
            });

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

    /**
     * Retry a failed assistant message.
     * POST /projects/{project}/chats/{chat}/messages/{message}/retry
     */
    public function retry(Request $request, Project $project, Chat $chat, Message $message)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id   !== $tenant->id, 403);
        abort_if($message->chat_id  !== $chat->id, 404);
        abort_if($message->role !== 'assistant' || $message->status !== 'failed', 422, 'Only failed assistant messages can be retried.');

        if (!$this->quota->check($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'quota_exceeded',
            ], 402);
        }

        // Delete the failed assistant message — SSE stream will create a new one
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ready to retry.',
        ]);
    }
}
