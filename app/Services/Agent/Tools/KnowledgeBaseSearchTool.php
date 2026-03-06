<?php

namespace App\Services\Agent\Tools;

use App\Models\Chat;
use App\Models\Project;
use App\Services\RagService;

class KnowledgeBaseSearchTool extends BaseTool
{
    public function __construct(
        private RagService $ragService,
        private Project $project,
        private Chat $chat,
    ) {}

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function description(): string
    {
        return 'Search the project knowledge base (uploaded documents). Use when the user asks about content from uploaded files, documentation, or internal knowledge.';
    }

    public function parameters(): array
    {
        return [
            [
                'name'        => 'query',
                'type'        => 'string',
                'description' => 'The search query to look up in the knowledge base',
                'required'    => true,
            ],
            [
                'name'        => 'top_k',
                'type'        => 'integer',
                'description' => 'Number of relevant chunks to return (default 5, max 10)',
                'required'    => false,
            ],
        ];
    }

    public function execute(array $input): string
    {
        $query = trim($input['query'] ?? '');
        $topK  = min((int) ($input['top_k'] ?? 5), 10);

        if (!$query) {
            return 'Error: query is required.';
        }

        $chunks = $this->ragService->search(
            $this->project,
            $query,
            $topK,
            $this->chat,
        );

        if (empty($chunks)) {
            return "No relevant content found in knowledge base for: \"{$query}\"";
        }

        $output = "Knowledge base results for \"{$query}\":\n\n";
        foreach ($chunks as $i => $chunk) {
            $output .= "--- Excerpt " . ($i + 1) . " ---\n{$chunk}\n\n";
        }

        return rtrim($output);
    }
}