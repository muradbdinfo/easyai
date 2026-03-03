<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $url;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
            $this->url     = config('ollama.url');
            $this->model   = config('ollama.model');
            $this->timeout = config('ollama.timeout');
    }

    /**
     * Send a chat request to Ollama.
     *
     * @param  array  $messages  Array of ['role' => ..., 'content' => ...]
     * @param  string|null  $model  Override model
     * @return array{content: string, prompt_tokens: int, completion_tokens: int}
     */
    public function chat(array $messages, ?string $model = null): array
    {
        $model = $model ?? $this->model;

        $response = Http::timeout($this->timeout)
            ->post("{$this->url}/api/chat", [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
            ]);

        if ($response->failed()) {
            Log::error('Ollama API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Ollama request failed: ' . $response->status());
        }

        $data = $response->json();

        $content           = $data['message']['content'] ?? '';
        $promptTokens      = $data['prompt_eval_count'] ?? 0;
        $completionTokens  = $data['eval_count'] ?? 0;

        return [
            'content'           => $content,
            'prompt_tokens'     => (int) $promptTokens,
            'completion_tokens' => (int) $completionTokens,
        ];
    }

    /**
     * Check if Ollama is reachable.
     */
    public function health(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->url}/api/tags");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}