<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebCrawlerService
{
    /**
     * Crawl a URL and extract clean text content.
     */
    public function crawl(string $url): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'EasyAI-Bot/1.0 (Knowledge Base Crawler)',
                'Accept'     => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch URL: HTTP {$response->status()}");
        }

        $html        = $response->body();
        $contentType = $response->header('Content-Type', '');

        // If PDF, return raw for file-based processing
        if (str_contains($contentType, 'application/pdf')) {
            throw new \RuntimeException('URL points to a PDF. Please upload it as a file instead.');
        }

        $title = $this->extractTitle($html);
        $text  = $this->extractText($html);

        if (mb_strlen(trim($text)) < 50) {
            throw new \RuntimeException('Could not extract meaningful text from this URL.');
        }

        return [
            'title' => $title ?: parse_url($url, PHP_URL_HOST),
            'text'  => $text,
            'size'  => strlen($html),
        ];
    }

    /**
     * Extract page title from HTML.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    /**
     * Extract clean text from HTML — remove scripts, styles, nav, footer, etc.
     */
    private function extractText(string $html): string
    {
        // Remove unwanted tags entirely
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/si', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/si', '', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/si', '', $html);
        $html = preg_replace('/<aside[^>]*>.*?<\/aside>/si', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Try to extract main content area first
        $mainContent = '';
        if (preg_match('/<(?:main|article)[^>]*>(.*?)<\/(?:main|article)>/si', $html, $m)) {
            $mainContent = $m[1];
        }

        $source = $mainContent ?: $html;

        // Convert block elements to newlines
        $source = preg_replace('/<\/(?:p|div|li|h[1-6]|tr|br|blockquote)>/i', "\n", $source);
        $source = preg_replace('/<(?:br|hr)\s*\/?>/i', "\n", $source);

        // Strip remaining tags
        $text = strip_tags($source);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Clean whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);         // collapse horizontal space
        $text = preg_replace('/\n{3,}/', "\n\n", $text);      // max 2 newlines
        $text = implode("\n", array_map('trim', explode("\n", $text))); // trim each line

        return trim($text);
    }

    /**
     * Crawl multiple pages (simple sitemap/link following).
     * Returns combined text with page separators.
     */
    public function crawlMultiple(string $baseUrl, int $maxPages = 5): array
    {
        $visited = [];
        $queue   = [$baseUrl];
        $allText = '';
        $titles  = [];
        $totalSize = 0;

        while (!empty($queue) && count($visited) < $maxPages) {
            $url = array_shift($queue);
            $normalized = rtrim($url, '/');

            if (in_array($normalized, $visited)) continue;
            $visited[] = $normalized;

            try {
                $result = $this->crawl($url);
                $allText .= "\n\n=== Page: {$result['title']} ({$url}) ===\n\n" . $result['text'];
                $titles[] = $result['title'];
                $totalSize += $result['size'];

                // Extract same-domain links for crawling
                if (count($visited) < $maxPages) {
                    $links = $this->extractLinks($url);
                    $queue = array_merge($queue, $links);
                }
            } catch (\Throwable $e) {
                \Log::warning("WebCrawler skip {$url}: {$e->getMessage()}");
                continue;
            }
        }

        return [
            'title'       => $titles[0] ?? parse_url($baseUrl, PHP_URL_HOST),
            'text'        => trim($allText),
            'size'        => $totalSize,
            'pages_count' => count($visited),
        ];
    }

    /**
     * Extract same-domain links from a page.
     */
    private function extractLinks(string $sourceUrl): array
    {
        try {
            $response = Http::timeout(15)->get($sourceUrl);
            $html     = $response->body();
        } catch (\Throwable) {
            return [];
        }

        $baseHost = parse_url($sourceUrl, PHP_URL_HOST);
        $scheme   = parse_url($sourceUrl, PHP_URL_SCHEME) ?: 'https';
        $links    = [];

        preg_match_all('/href=["\']([^"\'#]+)["\']/i', $html, $matches);

        foreach ($matches[1] ?? [] as $href) {
            // Skip non-page links
            if (preg_match('/\.(jpg|png|gif|css|js|svg|ico|pdf|zip|mp4|mp3)/i', $href)) continue;
            if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) continue;
            if (str_starts_with($href, 'javascript:')) continue;

            // Resolve relative URLs
            if (str_starts_with($href, '/')) {
                $href = "{$scheme}://{$baseHost}{$href}";
            } elseif (!str_starts_with($href, 'http')) {
                continue;
            }

            // Same domain only
            if (parse_url($href, PHP_URL_HOST) === $baseHost) {
                $links[] = strtok($href, '#'); // remove fragment
            }
        }

        return array_unique($links);
    }
}
