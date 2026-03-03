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

class SummarizeChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 120;

    public function __construct(public int $chatId) {}

    public function handle(OllamaService $ollama): void
    {
        // a) Load chat with project and messages
        $chat = Chat::with(['project', 'messages' => function ($q) {
            $q->whereIn('role', ['user', 'assistant'])
              ->orderBy('created_at', 'asc');
        }])->find($this->chatId);

        if (!$chat || !$chat->project) {
            Log::warning("SummarizeChatJob: chat {$this->chatId} not found.");
            return;
        }

        if ($chat->messages->isEmpty()) {
            return;
        }

        // b) Build conversation text for summarization
        $conversation = $chat->messages->map(function ($msg) {
            $role = ucfirst($msg->role);
            return "{$role}: {$msg->content}";
        })->implode("\n\n");

        $prompt = [
            [
                'role'    => 'system',
                'content' => 'You are a helpful assistant that summarizes conversations concisely. '
                           . 'Create a brief summary (3-5 sentences) of the key topics, decisions, '
                           . 'and context from this conversation. Focus on facts and context that '
                           . 'would be useful in future conversations.',
            ],
            [
                'role'    => 'user',
                'content' => "Please summarize this conversation:\n\n{$conversation}",
            ],
        ];

        // c) Call Ollama for summary
        try {
            $result = $ollama->chat($prompt, $chat->project->model ?? null);
        } catch (\Throwable $e) {
            Log::error("SummarizeChatJob: Ollama error — {$e->getMessage()}");
            return;
        }

        $summary = trim($result['content']);

        if (empty($summary)) {
            return;
        }

        // d) Append summary to project context_summary with separator
        $project = $chat->project;

        $existing = $project->context_summary;

        $newSummary = empty($existing)
            ? $summary
            : $existing . "\n\n---\n\n" . $summary;

        $project->update(['context_summary' => $newSummary]);

        Log::info("SummarizeChatJob: summary saved for project {$project->id}");
    }
}