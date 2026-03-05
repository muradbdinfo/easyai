<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\ChatAttachment;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Services\MemoryService;
use App\Services\OllamaService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 150;

    public function __construct(
        public int  $chatId,
        public int  $tenantId,
        public int  $userId,
        public ?int $attachmentId = null,
    ) {}

    public function handle(
        OllamaService       $ollama,
        QuotaService        $quota,
        TokenCounterService $counter,
        MemoryService       $memory,
    ): void {

        // ── a) Load chat with project ─────────────────────────────
        $chat = Chat::with('project')->find($this->chatId);

        if (!$chat) {
            Log::warning("SendMessageJob: chat {$this->chatId} not found.");
            return;
        }

        // ── b) Load tenant ────────────────────────────────────────
        $tenant = Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::warning("SendMessageJob: tenant {$this->tenantId} not found.");
            return;
        }

        // ── c) Get last user message for RAG query ────────────────
        $lastUserMessage = Message::where('chat_id', $this->chatId)
            ->where('role', 'user')
            ->latest()
            ->value('content');

        // ── d) Build context using MemoryService (with RAG) ───────
        $messages = $memory->buildContext($chat, $chat->project, $lastUserMessage ?? '');

        // ── e) Inject attachment content into context ─────────────
        if ($this->attachmentId) {
            $attachment = ChatAttachment::find($this->attachmentId);

            if ($attachment) {
                $lastKey = null;
                foreach ($messages as $k => $m) {
                    if ($m['role'] === 'user') {
                        $lastKey = $k;
                    }
                }

                if ($lastKey !== null) {
                    $userContent = $messages[$lastKey]['content'] ?? '';
                    $model       = $chat->project->model ?? config('ollama.model');

                    if ($attachment->isImage()) {
                        if (str_contains(strtolower($model), 'llava')) {
                            try {
                                $imageData = base64_encode(
                                    Storage::disk('public')->get($attachment->path)
                                );
                                $messages[$lastKey]['images'] = [$imageData];
                            } catch (\Throwable $e) {
                                $messages[$lastKey]['content'] =
                                    "[Attached Image: {$attachment->original_name}]\n\n"
                                    . $userContent;
                            }
                        } else {
                            $messages[$lastKey]['content'] =
                                "[Image attached: {$attachment->original_name}. "
                                . "Please describe or analyze this image if you can.]\n\n"
                                . $userContent;
                        }
                    } else {
                        $extracted   = $attachment->extracted_text ?? '[Could not extract file content]';
                        $fileContext = "=== Attached File: {$attachment->original_name} ===\n"
                                    . $extracted
                                    . "\n=== End of File ===";

                        array_splice($messages, $lastKey, 0, [[
                            'role'    => 'system',
                            'content' => $fileContext,
                        ]]);
                    }
                }
            }
        }

        // ── f) Call Ollama ────────────────────────────────────────
        try {
            $result = $ollama->chat($messages, $chat->project->model ?? null);
        } catch (\Throwable $e) {
            Log::error("SendMessageJob: Ollama error — {$e->getMessage()}");

            Message::create([
                'chat_id'   => $this->chatId,
                'tenant_id' => $this->tenantId,
                'role'      => 'assistant',
                'content'   => 'Sorry, I could not connect to the AI engine. Please try again.',
                'tokens'    => 0,
                'model'     => $chat->project->model ?? config('ollama.model'),
            ]);
            return;
        }

        // ── g) Save assistant response ────────────────────────────
        Message::create([
            'chat_id'   => $this->chatId,
            'tenant_id' => $this->tenantId,
            'role'      => 'assistant',
            'content'   => $result['content'],
            'tokens'    => $result['completion_tokens'],
            'model'     => $chat->project->model ?? config('ollama.model'),
        ]);

        // ── h) Calculate total tokens (with fallback) ─────────────
        $totalTokens = $result['prompt_tokens'] + $result['completion_tokens'];

        if ($totalTokens === 0) {
            $totalTokens = $counter->estimateMessages($messages)
                         + $counter->estimate($result['content']);
        }

        try {
            // ── i) Update chat total_tokens ───────────────────────
            $chat->increment('total_tokens', $totalTokens);

            // ── j) Deduct from tenant quota ───────────────────────
            $quota->deduct($tenant, $totalTokens);

            // ── k) Log usage ──────────────────────────────────────
            UsageLog::create([
                'tenant_id'         => $this->tenantId,
                'user_id'           => $this->userId,
                'chat_id'           => $this->chatId,
                'model'             => $chat->project->model ?? config('ollama.model'),
                'prompt_tokens'     => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
                'total_tokens'      => $totalTokens,
                'cost'              => 0.000000,
                'created_at'        => now(),
            ]);

            // ── l) Close chat if quota exceeded ───────────────────
            $tenant->refresh();
            if ($quota->isExceeded($tenant)) {
                $chat->update([
                    'status'        => 'closed',
                    'closed_reason' => 'Token quota exceeded.',
                ]);
            }

            // ── m) Dispatch summarize job every 20 messages ───────
            $messageCount = Message::where('chat_id', $this->chatId)->count();
            if ($messageCount >= 20 && $messageCount % 20 === 0) {
                SummarizeChatJob::dispatch($this->chatId);
            }

        } catch (\Throwable $e) {
            Log::error('SendMessageJob post-save error: ' . $e->getMessage(), [
                'chat_id' => $this->chatId,
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}