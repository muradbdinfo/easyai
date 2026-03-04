<?php

// FILE: app/Http/Controllers/MessageController.php
// REPLACES existing file entirely

namespace App\Http\Controllers;

use App\Jobs\SendMessageJob;
use App\Models\Chat;
use App\Models\ChatAttachment;
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
        abort_if($chat->project_id   !== $project->id, 404);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        if ($chat->isClosed()) {
            return back()->withErrors(['message' => 'This chat is closed.']);
        }

        // ── Quota check ───────────────────────────────────────────
        if (!$this->quota->check($tenant)) {
            return back()
                ->withErrors(['message' => 'Token quota exceeded. Please upgrade your plan.'])
                ->with('quota_exceeded', true);
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

        // ── Link attachment to message ────────────────────────────
        $attachmentId = null;
        if (!empty($validated['attachment_id'])) {
            $attachment = ChatAttachment::find($validated['attachment_id']);

            // Security: must belong to same tenant + same chat
            if (
                $attachment
                && $attachment->tenant_id === $tenant->id
                && $attachment->chat_id   === $chat->id
                && $attachment->message_id === null   // not already linked
            ) {
                $attachment->update(['message_id' => $userMessage->id]);
                $userMessage->update(['attachment_id' => $attachment->id]);
                $attachmentId = $attachment->id;
            }
        }

        // ── Update chat title from first message ──────────────────
        if ($chat->title === 'New Chat' || $chat->title === null) {
            $chat->update([
                'title' => mb_substr($validated['content'], 0, 60),
            ]);
        }

        // ── Dispatch AI job ───────────────────────────────────────
        SendMessageJob::dispatch(
            $chat->id,
            $tenant->id,
            $request->user()->id,
            $attachmentId
        );

        return back();
    }

    public function index(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id    !== $tenant->id, 403);

        $messages = Message::where('chat_id', $chat->id)
            ->with(['attachment:id,type,original_name,url,extension,meta,file_size'])
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return response()->json([
            'messages' => $messages,
            'chat'     => [
                'id'           => $chat->id,
                'status'       => $chat->status,
                'total_tokens' => $chat->fresh()->total_tokens,
            ],
        ]);
    }
}