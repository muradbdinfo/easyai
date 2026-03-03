<?php

namespace App\Services;

class TokenCounterService
{
    /**
     * Estimate token count for a single string.
     * Rule of thumb: ~4 characters per token.
     */
    public function estimate(string $text): int
    {
        return (int) ceil(mb_strlen(trim($text)) / 4);
    }

    /**
     * Estimate total tokens for an array of messages.
     * Each message: ['role' => ..., 'content' => ...]
     */
    public function estimateMessages(array $messages): int
    {
        $total = 0;
        foreach ($messages as $message) {
            $total += $this->estimate($message['content'] ?? '');
            $total += 4; // overhead per message (role + formatting)
        }
        return $total;
    }
}