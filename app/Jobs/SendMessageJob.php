<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\UsageLog;
use App\Services\OllamaService;
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

    public function handle(OllamaService $ollama): void
    {
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
        $contextLimit = config('ollama.context_limit', 6000);
        $messages     = [];

        // Add system prompt if project has one
        if (!empty($chat->project->system_prompt)) {
            $messages[] = [
                'role'    => 'system',
                'content' => $chat->project->system_prompt,
            ];
        }

        // Add project memory / context summary
        if (!empty($chat->project->context_summary)) {
            $messages[] = [
                'role'    => 'system',
                'content' => 'Previous context summary: ' . $chat->project->context_summary,
            ];
        }

        // Load last 20 user/assistant messages (ordered ASC)
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

            // Save an error message so the UI doesn't stay in loading state
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
        $assistantMessage = Message::create([
            'chat_id'   => $this->chatId,
            'tenant_id' => $this->tenantId,
            'role'      => 'assistant',
            'content'   => $result['content'],
            'tokens'    => $result['completion_tokens'],
            'model'     => $chat->project->model ?? config('ollama.model'),
        ]);

        // f) Calculate total tokens for this exchange
        $totalTokens = $result['prompt_tokens'] + $result['completion_tokens'];

        // Fallback: estimate if Ollama returned 0
        if ($totalTokens === 0) {
            $lastUserMsg  = Message::where('chat_id', $this->chatId)
                ->where('role', 'user')
                ->latest()
                ->skip(1) // skip the one just saved, get the one before assistant
                ->first();
            $totalTokens = $this->estimateTokens($result['content']);
            if ($lastUserMsg) {
                $totalTokens += $this->estimateTokens($lastUserMsg->content);
            }
        }

        // g) Update chat total_tokens
        $chat->increment('total_tokens', $totalTokens);

        // h) Check quota and deduct from tenant
        $tenant->increment('tokens_used', $totalTokens);

        // i) Log usage
        UsageLog::create([
            'tenant_id'         => $this->tenantId,
            'user_id'           => $this->userId,
            'chat_id'           => $this->chatId,
            'model'             => $chat->project->model ?? config('ollama.model'),
            'prompt_tokens'     => $result['prompt_tokens'],
            'completion_tokens' => $result['completion_tokens'],
            'total_tokens'      => $totalTokens,
            'cost'              => 0, // self-hosted = free
            'created_at'        => now(),
        ]);

        // j) Close chat if tenant exceeded quota
        if ($tenant->fresh()->tokens_used >= $tenant->token_quota && $tenant->token_quota > 0) {
            $chat->update([
                'status'        => 'closed',
                'closed_reason' => 'Token quota exceeded.',
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