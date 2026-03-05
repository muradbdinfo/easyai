<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\KnowledgeChunk;
use App\Models\Project;

class RagService
{
    public function search(Project $project, string $query, int $topK = 5, ?Chat $chat = null): array
    {
        if (empty(trim($query))) return [];

        $kbIds = $this->getKbIds($project, $chat);
        if (empty($kbIds)) return [];

        $chunks = KnowledgeChunk::whereIn('knowledge_base_id', $kbIds)
            ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$this->buildQuery($query)])
            ->orderByRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE) DESC', [$this->buildQuery($query)])
            ->limit($topK)
            ->get();

        if ($chunks->isEmpty()) {
            $words  = array_slice(explode(' ', $query), 0, 3);
            $chunks = KnowledgeChunk::whereIn('knowledge_base_id', $kbIds)
                ->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        if (strlen($word) > 2) {
                            $q->orWhere('content', 'LIKE', '%' . $word . '%');
                        }
                    }
                })
                ->limit($topK)
                ->get();
        }

        return $chunks->pluck('content')->toArray();
    }

    private function getKbIds(Project $project, ?Chat $chat): array
    {
        $ids = $project->knowledgeBases()
            ->where('is_active', true)
            ->whereNull('chat_id')
            ->pluck('id')
            ->toArray();

        if ($chat) {
            $chatKbIds = $chat->knowledgeBases()
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
            $ids = array_merge($ids, $chatKbIds);
        }

        return array_unique($ids);
    }

    public function injectIntoUserMessage(string $userQuery, array $chunks): string
    {
        if (empty($chunks)) return $userQuery;

        $context  = "The following are excerpts from uploaded documents. Use them to answer the question at the end.\n\n";

        foreach ($chunks as $i => $chunk) {
            $context .= "[Document Excerpt " . ($i + 1) . "]\n" . $chunk . "\n\n";
        }

        $context .= "---\nQuestion: " . $userQuery . "\nAnswer based on the document excerpts above:";

        return $context;
    }

    private function buildQuery(string $query): string
    {
        $words = array_filter(explode(' ', $query), fn($w) => strlen($w) > 2);
        return implode(' ', array_map(fn($w) => '+' . $w . '*', array_slice($words, 0, 8)));
    }
}