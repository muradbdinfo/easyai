<?php

namespace App\Services\Agent\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlReaderTool extends BaseTool
{
    // Max characters to return (avoid flooding context)
    private const MAX_CHARS = 4000;

    public function name(): string
    {
        return 'read_url';
    }

    public function description(): string
    {
        return 'Fetch and read the text content of a webpage or URL. Use when you need to read a specific article, documentation page, or website.';
    }

    public function parameters(): array
    {
        return [
            [
                'name'        => 'url',
                'type'        => 'string',
                'description' => 'The full URL to fetch (must start with http:// or https://)',
                'required'    => true,
            ],
            [
                'name'        => 'max_chars',
                'type'        => 'integer',
                'description' => 'Maximum characters to return (default 3000, max 4000)',
                'required'    => false,
            ],
        ];
    }

    public function execute(array $input): string
    {
        $url      = trim($input['url'] ?? '');
        $maxChars = min((int) ($input['max_chars'] ?? 3000), self::MAX_CHARS);

        if (!$url) {
            return 'Error: url is required.';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return "Error: \"{$url}\" is not a valid URL.";
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return 'Error: URL must start with http:// or https://';
        }

        // Block private/local addresses
        $host = parse_url($url, PHP_URL_HOST);
        if ($this->isPrivateHost($host)) {
            return 'Error: Access to private/local addresses is not allowed.';
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; EasyAI-Agent/1.0)',
                'Accept'     => 'text/html,text/plain',
            ])
            ->timeout(20)
            ->get($url);

            if (!$response->successful()) {
                return "Failed to fetch URL: HTTP {$response->status()}";
            }

            $contentType = $response->header('Content-Type', '');

            // Handle plain text / JSON directly
            if (
                str_contains($contentType, 'text/plain') ||
                str_contains($contentType, 'application/json')
            ) {
                $text = substr($response->body(), 0, $maxChars);
                return "Content from {$url}:\n\n{$text}";
            }

            // Parse HTML
            $text = $this->extractText($response->body());
            $text = substr($text, 0, $maxChars);

            if (!trim($text)) {
                return "Could not extract readable text from {$url}";
            }

            $truncated = strlen($response->body()) > $maxChars ? "\n\n[Content truncated to {$maxChars} characters]" : '';

            return "Content from {$url}:\n\n{$text}{$truncated}";

        } catch (\Throwable $e) {
            Log::warning('UrlReaderTool error: ' . $e->getMessage());
            return "Error reading URL: {$e->getMessage()}";
        }
    }

    private function extractText(string $html): string
    {
        // Remove scripts, styles, nav, footer
        $html = preg_replace('/<(script|style|nav|footer|header|aside|noscript)[^>]*>.*?<\/\1>/si', '', $html);

        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Convert block elements to newlines
        $html = preg_replace('/<\/(p|div|li|h[1-6]|br|tr|td|th|blockquote|pre)>/i', "\n", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

        // Strip remaining tags
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function isPrivateHost(?string $host): bool
    {
        if (!$host) return true;

        $privatePatterns = [
            'localhost', '127.0.0.1', '::1',
            '0.0.0.0', '169.254.',
        ];

        foreach ($privatePatterns as $pattern) {
            if (str_contains($host, $pattern)) return true;
        }

        // Block 10.x, 172.16-31.x, 192.168.x
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }
}