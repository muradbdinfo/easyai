<?php

namespace App\Services\Agent\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSearchTool extends BaseTool
{
    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the web for current information. Use when you need recent facts, news, prices, or any info not in your training data.';
    }

    public function parameters(): array
    {
        return [
            [
                'name'        => 'query',
                'type'        => 'string',
                'description' => 'The search query to look up',
                'required'    => true,
            ],
            [
                'name'        => 'num_results',
                'type'        => 'integer',
                'description' => 'Number of results to return (default 5, max 10)',
                'required'    => false,
            ],
        ];
    }

    public function execute(array $input): string
    {
        $query      = trim($input['query'] ?? '');
        $numResults = min((int) ($input['num_results'] ?? 5), 10);

        if (!$query) {
            return 'Error: query is required.';
        }

        try {
            // DuckDuckGo Lite — no API key, no JS required
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; EasyAI-Agent/1.0)',
                'Accept'     => 'text/html',
            ])
            ->timeout(15)
            ->get('https://html.duckduckgo.com/html/', [
                'q' => $query,
            ]);

            if (!$response->successful()) {
                return "Search failed: HTTP {$response->status()}";
            }

            $html    = $response->body();
            $results = $this->parseResults($html, $numResults);

            if (empty($results)) {
                return "No results found for: {$query}";
            }

            $output = "Search results for \"{$query}\":\n\n";
            foreach ($results as $i => $r) {
                $output .= ($i + 1) . ". **{$r['title']}**\n";
                $output .= "   URL: {$r['url']}\n";
                $output .= "   {$r['snippet']}\n\n";
            }

            return rtrim($output);

        } catch (\Throwable $e) {
            Log::warning('WebSearchTool error: ' . $e->getMessage());
            return "Search error: {$e->getMessage()}";
        }
    }

    private function parseResults(string $html, int $limit): array
    {
        $results = [];

        // Extract result blocks
        preg_match_all('/<div class="result[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>/s', $html, $blocks);

        if (empty($blocks[1])) {
            // Fallback: parse links + snippets directly
            preg_match_all('/<a class="result__a" href="([^"]+)"[^>]*>(.*?)<\/a>/s', $html, $links);
            preg_match_all('/<a class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $snippets);

            $count = min(count($links[1]), $limit);
            for ($i = 0; $i < $count; $i++) {
                $results[] = [
                    'title'   => strip_tags(html_entity_decode($links[2][$i] ?? 'No title')),
                    'url'     => $this->extractUrl($links[1][$i]),
                    'snippet' => strip_tags(html_entity_decode($snippets[1][$i] ?? '')),
                ];
            }
            return $results;
        }

        foreach (array_slice($blocks[1], 0, $limit) as $block) {
            // Title + URL
            preg_match('/<a class="result__a" href="([^"]+)"[^>]*>(.*?)<\/a>/s', $block, $titleMatch);
            // Snippet
            preg_match('/<a class="result__snippet"[^>]*>(.*?)<\/a>/s', $block, $snippetMatch);

            if (!$titleMatch) continue;

            $results[] = [
                'title'   => strip_tags(html_entity_decode($titleMatch[2] ?? 'No title')),
                'url'     => $this->extractUrl($titleMatch[1]),
                'snippet' => strip_tags(html_entity_decode($snippetMatch[1] ?? '')),
            ];
        }

        return $results;
    }

    private function extractUrl(string $raw): string
    {
        // DuckDuckGo wraps URLs in redirect — extract uddg param
        if (str_contains($raw, 'uddg=')) {
            parse_str(parse_url($raw, PHP_URL_QUERY), $params);
            return urldecode($params['uddg'] ?? $raw);
        }
        return $raw;
    }
}