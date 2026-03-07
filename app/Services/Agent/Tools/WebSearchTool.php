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
        return 'Search the web for current information. Use when you need facts, news, people, prices, or any info. If first search returns nothing useful, try a different/simpler query.';
    }

    public function parameters(): array
    {
        return [
            [
                'name'        => 'query',
                'type'        => 'string',
                'description' => 'The search query. Keep it short and specific for best results.',
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

        // ── Strategy 1: DuckDuckGo Instant Answer JSON API ──────────────
        $instant = $this->duckduckgoInstant($query);
        if ($instant) {
            return $instant;
        }

        // ── Strategy 2: DuckDuckGo HTML scraping ────────────────────────
        $html = $this->duckduckgoHtml($query, $numResults);
        if ($html) {
            return $html;
        }

        // ── Strategy 3: Bing HTML (no API key needed) ───────────────────
        $bing = $this->bingHtml($query, $numResults);
        if ($bing) {
            return $bing;
        }

        return "No search results found for: \"{$query}\". Try a simpler or more specific query.";
    }

    // ─────────────────────────────────────────────────────────────────
    // Strategy 1 — DuckDuckGo Instant Answers (JSON)
    // Great for: people, definitions, facts, Wikipedia subjects
    // ─────────────────────────────────────────────────────────────────
    private function duckduckgoInstant(string $query): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; EasyAI/1.0)',
            ])
            ->timeout(10)
            ->get('https://api.duckduckgo.com/', [
                'q'              => $query,
                'format'         => 'json',
                'no_html'        => '1',
                'skip_disambig'  => '1',
            ]);

            if (!$response->successful()) return '';

            $data   = $response->json();
            $output = '';

            // Abstract (Wikipedia-style summary)
            $abstract = trim($data['Abstract'] ?? '');
            $source   = trim($data['AbstractSource'] ?? '');
            $url      = trim($data['AbstractURL'] ?? '');

            if ($abstract) {
                $output .= "**{$source}:** {$abstract}\n";
                if ($url) $output .= "Source: {$url}\n";
                $output .= "\n";
            }

            // Answer (instant fact)
            $answer = trim($data['Answer'] ?? '');
            if ($answer) {
                $output .= "**Instant Answer:** {$answer}\n\n";
            }

            // Definition
            $definition = trim($data['Definition'] ?? '');
            $defSource  = trim($data['DefinitionSource'] ?? '');
            if ($definition) {
                $output .= "**Definition ({$defSource}):** {$definition}\n\n";
            }

            // Related topics (first 5)
            $related = $data['RelatedTopics'] ?? [];
            $topics  = [];
            foreach (array_slice($related, 0, 5) as $topic) {
                $text = $topic['Text'] ?? '';
                $link = $topic['FirstURL'] ?? '';
                if ($text) {
                    $topics[] = "- {$text}" . ($link ? " ({$link})" : '');
                }
            }
            if ($topics) {
                $output .= "**Related Topics:**\n" . implode("\n", $topics) . "\n\n";
            }

            // Infobox key-value pairs
            $infobox = $data['Infobox']['content'] ?? [];
            if ($infobox) {
                $output .= "**Key Facts:**\n";
                foreach (array_slice($infobox, 0, 8) as $item) {
                    $label = $item['label'] ?? '';
                    $value = $item['value'] ?? '';
                    if ($label && $value) {
                        $output .= "- {$label}: {$value}\n";
                    }
                }
                $output .= "\n";
            }

            return $output ? "Search results for \"{$query}\" (DuckDuckGo):\n\n" . rtrim($output) : '';

        } catch (\Throwable $e) {
            Log::debug('DDG instant failed: ' . $e->getMessage());
            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Strategy 2 — DuckDuckGo HTML lite
    // ─────────────────────────────────────────────────────────────────
    private function duckduckgoHtml(string $query, int $limit): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml',
            ])
            ->timeout(15)
            ->get('https://html.duckduckgo.com/html/', [
                'q' => $query,
            ]);

            if (!$response->successful()) return '';

            $results = $this->parseDdgHtml($response->body(), $limit);
            if (empty($results)) return '';

            $output = "Search results for \"{$query}\":\n\n";
            foreach ($results as $i => $r) {
                $output .= ($i + 1) . ". **{$r['title']}**\n";
                if ($r['url'])     $output .= "   URL: {$r['url']}\n";
                if ($r['snippet']) $output .= "   {$r['snippet']}\n";
                $output .= "\n";
            }
            return rtrim($output);

        } catch (\Throwable $e) {
            Log::debug('DDG HTML failed: ' . $e->getMessage());
            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Strategy 3 — Bing HTML (no API key required)
    // ─────────────────────────────────────────────────────────────────
    private function bingHtml(string $query, int $limit): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Accept'     => 'text/html',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->timeout(15)
            ->get('https://www.bing.com/search', [
                'q'    => $query,
                'form' => 'QBLH',
            ]);

            if (!$response->successful()) return '';

            $results = $this->parseBingHtml($response->body(), $limit);
            if (empty($results)) return '';

            $output = "Search results for \"{$query}\" (Bing):\n\n";
            foreach ($results as $i => $r) {
                $output .= ($i + 1) . ". **{$r['title']}**\n";
                if ($r['url'])     $output .= "   URL: {$r['url']}\n";
                if ($r['snippet']) $output .= "   {$r['snippet']}\n";
                $output .= "\n";
            }
            return rtrim($output);

        } catch (\Throwable $e) {
            Log::debug('Bing HTML failed: ' . $e->getMessage());
            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // HTML Parsers
    // ─────────────────────────────────────────────────────────────────
    private function parseDdgHtml(string $html, int $limit): array
    {
        $results = [];

        // Try multiple class patterns DDG has used
        $patterns = [
            '/<a class="result__a"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/si',
            '/<a[^>]+class="[^"]*result__url[^"]*"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/si',
            '/<h2[^>]*class="[^"]*result__title[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/si',
        ];

        $links = [];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $html, $m);
            if (!empty($m[1])) {
                $links = $m;
                break;
            }
        }

        // Snippets
        preg_match_all(
            '/<a[^>]+class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/a>/si',
            $html,
            $snippets
        );

        $count = min(count($links[1] ?? []), $limit);
        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'title'   => $this->clean($links[2][$i] ?? 'Result'),
                'url'     => $this->extractUrl($links[1][$i] ?? ''),
                'snippet' => $this->clean($snippets[1][$i] ?? ''),
            ];
        }

        return $results;
    }

    private function parseBingHtml(string $html, int $limit): array
    {
        $results = [];

        // Bing result structure: <li class="b_algo"><h2><a href="...">title</a></h2><div class="b_caption"><p>snippet</p></div></li>
        preg_match_all('/<li[^>]+class="b_algo"[^>]*>(.*?)<\/li>/si', $html, $blocks);

        foreach (array_slice($blocks[1] ?? [], 0, $limit) as $block) {
            // Title + URL from h2 > a
            preg_match('/<h2[^>]*>.*?<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/si', $block, $titleM);
            // Snippet from caption
            preg_match('/<p[^>]*>(.*?)<\/p>/si', $block, $snippetM);

            if (empty($titleM[1])) continue;

            $results[] = [
                'title'   => $this->clean($titleM[2] ?? 'Result'),
                'url'     => $titleM[1],
                'snippet' => $this->clean($snippetM[1] ?? ''),
            ];
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────
    private function clean(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function extractUrl(string $raw): string
    {
        if (str_contains($raw, 'uddg=')) {
            parse_str(parse_url($raw, PHP_URL_QUERY) ?? '', $params);
            return urldecode($params['uddg'] ?? $raw);
        }
        return $raw;
    }
}