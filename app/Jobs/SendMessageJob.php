<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Services\OllamaService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 150;

    public function __construct(
        public int $chatId,
        public int $tenantId,
        public int $userId,
    ) {}

 public function handle(
        OllamaService      $ollama,
        QuotaService       $quota,
        TokenCounterService $counter,
    ): void {
        // a) Load chat with project
        $chat = Chat::with('project')->find($this->chatId);

        if (!$chat) {
            Log::warning("SendMessageJob: chat {$this->chatId} not found.");
            return;
        }

        // b) Load tenant
        $tenant = Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::warning("SendMessageJob: tenant {$this->tenantId} not found.");
            return;
        }

        // c) Build message history
        $messages = [];

        if (!empty($chat->project->system_prompt)) {
            $messages[] = [
                'role'    => 'system',
                'content' => $chat->project->system_prompt,
            ];
        }

        if (!empty($chat->project->context_summary)) {
            $messages[] = [
                'role'    => 'system',
                'content' => 'Previous context summary: ' . $chat->project->context_summary,
            ];
        }

        $history = Message::where('chat_id', $this->chatId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->take(20)
            ->get();

        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg->role,
                'content' => $msg->content,
            ];
        }

        // d) Call Ollama
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

        // e) Save assistant response
        Message::create([
            'chat_id'   => $this->chatId,
            'tenant_id' => $this->tenantId,
            'role'      => 'assistant',
            'content'   => $result['content'],
            'tokens'    => $result['completion_tokens'],
            'model'     => $chat->project->model ?? config('ollama.model'),
        ]);

        // f) Calculate total tokens — use TokenCounterService as fallback
        $totalTokens = $result['prompt_tokens'] + $result['completion_tokens'];

        if ($totalTokens === 0) {
            $totalTokens = $counter->estimateMessages($messages)
                         + $counter->estimate($result['content']);
        }

        try {
            // g) Update chat total_tokens
            $chat->increment('total_tokens', $totalTokens);

            // h) Deduct from tenant via QuotaService
            $quota->deduct($tenant, $totalTokens);

            // i) Log usage
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

            // j) Close chat if quota exceeded after this exchange
            $tenant->refresh();
            if ($quota->isExceeded($tenant)) {
                $chat->update([
                    'status'        => 'closed',
                    'closed_reason' => 'Token quota exceeded.',
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('SendMessageJob post-save error: ' . $e->getMessage(), [
                'chat_id'   => $this->chatId,
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }


    /**
     * Simple token estimator (~4 chars per token).
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}