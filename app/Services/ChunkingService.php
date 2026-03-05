<?php

namespace App\Services;

class ChunkingService
{
    private int $chunkSize;
    private int $overlap;

    public function __construct(int $chunkSize = 500, int $overlap = 50)
    {
        $this->chunkSize = $chunkSize;
        $this->overlap   = $overlap;
    }

    public function chunk(string $text): array
    {
        $text  = $this->cleanText($text);
        $words = explode(' ', $text);
        $total = count($words);

        if ($total === 0) return [];

        $chunks = [];
        $start  = 0;

        while ($start < $total) {
            $slice    = array_slice($words, $start, $this->chunkSize);
            $content  = trim(implode(' ', $slice));

            if (strlen($content) > 20) {
                $chunks[] = $content;
            }

            $start += ($this->chunkSize - $this->overlap);
        }

        return $chunks;
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^\x20-\x7E\n]/', ' ', $text);
        return trim($text);
    }
}