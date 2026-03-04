<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateChatTitleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        public int    $chatId,
        public string $userMessage,
        public ?string $model = null,
    ) {}

    public function handle(OllamaService $ollama): void
    {
        $chat = Chat::find($this->chatId);

        if (!$chat) return;

        // Only run if title is still generic
        if ($chat->title !== 'New Chat' && !empty($chat->title) && mb_strlen($chat->title) > 10) {
            return;
        }

        try {
            $result = $ollama->chat([
                [
                    'role'    => 'system',
                    'content' => 'Generate a concise 4-6 word title for a chat conversation. '
                               . 'Reply with ONLY the title. No quotes. No punctuation at end. '
                               . 'No extra explanation.',
                ],
                [
                    'role'    => 'user',
                    'content' => 'First message: ' . mb_substr($this->userMessage, 0, 300),
                ],
            ], $this->model);

            $title = trim($result['content']);

            // Clean up: remove quotes, trailing punctuation
            $title = preg_replace('/^["\']|["\']$/', '', $title);
            $title = rtrim($title, '.!?,;:');
            $title = mb_substr($title, 0, 80);

            if (!empty($title) && mb_strlen($title) > 2) {
                $chat->update(['title' => $title]);
            }
        } catch (\Throwable $e) {
            Log::warning("GenerateChatTitleJob failed for chat {$this->chatId}: {$e->getMessage()}");
            // Leave title as-is if Ollama fails
        }
    }
}