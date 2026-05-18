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

    /**
     * Main entry point.
     * Auto-detects Q&A structured content and uses smaller chunks.
     */
    public function chunk(string $text): array
    {
        $text = $this->cleanText($text);

        if (empty($text)) return [];

        // Auto-detect Cambridge exam paper structure
        // If text has numbered questions (1., 2., Q1, Question 1) use question-aware chunking
        if ($this->looksLikeExamPaper($text)) {
            return $this->chunkByQuestions($text);
        }

        return $this->chunkByWords($text);
    }

    // -- Word-based chunking (general documents) --------------------------

    private function chunkByWords(string $text): array
    {
        $words = explode(' ', $text);
        $total = count($words);

        if ($total === 0) return [];

        $chunks = [];
        $start  = 0;

        while ($start < $total) {
            $slice   = array_slice($words, $start, $this->chunkSize);
            $content = trim(implode(' ', $slice));

            if (strlen($content) > 20) {
                $chunks[] = $content;
            }

            $start += ($this->chunkSize - $this->overlap);
        }

        return $chunks;
    }

    // -- Question-aware chunking (exam papers) ----------------------------

    /**
     * Split by question boundaries, then chunk each question block.
     * Keeps each question + its sub-parts together in one or few chunks.
     * This prevents a question and its mark scheme from being split apart.
     */
    private function chunkByQuestions(string $text): array
    {
        // Split on common Cambridge question patterns:
        // "1 ", "2 ", "Q1", "Question 1", "(a)", "(b)", "Section A"
        $pattern = '/(?=(?:^|\n)(?:\d{1,2}[\.\)]\s|Q\d+|Question\s+\d+|Section\s+[A-Z]|\([a-z]\)\s))/m';

        $blocks = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($blocks) <= 2) {
            // Pattern didn't work well — fall back to word chunking with smaller size
            $service = new self(300, 30);
            return $service->chunkByWords($text);
        }

        $chunks = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if (strlen($block) < 20) continue;

            $words = explode(' ', $block);

            // If block fits in one chunk — keep it whole
            if (count($words) <= $this->chunkSize) {
                $chunks[] = $block;
            } else {
                // Block is large (e.g. essay question with full mark scheme) — sub-chunk it
                $subService = new self(300, 30);
                foreach ($subService->chunkByWords($block) as $sub) {
                    $chunks[] = $sub;
                }
            }
        }

        return $chunks;
    }

    // -- Helpers ----------------------------------------------------------

    private function looksLikeExamPaper(string $text): bool
    {
        // Check for numbered question patterns in first 3000 chars
        $sample = mb_substr($text, 0, 3000);

        $indicators = [
            '/^\d{1,2}[\.\)]\s/m',           // "1. " or "1) " at line start
            '/^Q\d+/m',                        // "Q1", "Q2"
            '/^Question\s+\d+/mi',             // "Question 1"
            '/^\(a\)/m',                       // "(a)"
            '/mark scheme/i',                  // mark scheme header
            '/\[\d+\s*marks?\]/i',             // "[2 marks]"
            '/Cambridge\s+O\s+Level/i',        // Cambridge branding
            '/Cambridge\s+A\s+Level/i',
            '/CAIE/i',
            '/Paper\s+[12]/i',                 // "Paper 1", "Paper 2"
        ];

        $hits = 0;
        foreach ($indicators as $pattern) {
            if (preg_match($pattern, $sample)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }

    private function cleanText(string $text): string
    {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Collapse multiple spaces (but keep newlines for question detection)
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Remove non-printable characters except newlines and tabs
        $text = preg_replace('/[^\x09\x0A\x20-\x7E]/', ' ', $text);

        // Collapse 3+ newlines to 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}