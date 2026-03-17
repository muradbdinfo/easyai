<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenClawService
{
    private string $url;
    private string $token;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->url     = rtrim(config('openclaw.url', 'http://127.0.0.1:18789'), '/');
        $this->token   = config('openclaw.token', '');
        $this->model   = config('openclaw.model', 'ollama/llama3.2');
        $this->timeout = config('openclaw.timeout', 120);
    }

    public function health(): bool
    {
        try {
            $resp = Http::timeout(5)
                ->withHeaders($this->headers())
                ->get("{$this->url}/v1/models");
            return $resp->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Stream response from OpenClaw via cURL.
     * Calls $onToken for each streamed token string.
     * Returns [content, prompt_tokens, completion_tokens].
     */
    public function stream(array $messages, string $model = null, callable $onToken = null): array
    {
        $model       = $model ?: $this->model;
        $fullContent = '';
        $promptTokens     = 0;
        $completionTokens = 0;

        $ch = curl_init("{$this->url}/v1/chat/completions");

        $headers = ['Content-Type: application/json'];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model'    => $model,
            'messages' => $messages,
            'stream'   => true,
        ]));

        curl_setopt($ch, CURLOPT_WRITEFUNCTION,
            function ($ch, $data) use (&$fullContent, &$promptTokens, &$completionTokens, $onToken) {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line === 'data: [DONE]') continue;

                    $raw  = str_starts_with($line, 'data: ') ? substr($line, 6) : $line;
                    $json = json_decode($raw, true);
                    if (!$json) continue;

                    // OpenAI-compat delta format
                    $token = $json['choices'][0]['delta']['content'] ?? null;
                    if ($token !== null && $token !== '') {
                        $fullContent .= $token;
                        if ($onToken) $onToken($token);
                    }

                    // Usage (usually in last chunk)
                    if (isset($json['usage'])) {
                        $promptTokens     = $json['usage']['prompt_tokens']     ?? 0;
                        $completionTokens = $json['usage']['completion_tokens'] ?? 0;
                    }
                }
                return strlen($data);
            }
        );

        curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('OpenClawService cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return [
            'content'           => $fullContent,
            'prompt_tokens'     => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];
    }

    private function headers(): array
    {
        $h = ['Content-Type' => 'application/json'];
        if ($this->token) {
            $h['Authorization'] = 'Bearer ' . $this->token;
        }
        return $h;
    }
}