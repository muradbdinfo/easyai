<?php

namespace App\Services\Agent;

use App\Models\Chat;
use App\Models\Project;
use App\Services\Agent\Tools\BaseTool;
use App\Services\Agent\Tools\CalculatorTool;
use App\Services\Agent\Tools\KnowledgeBaseSearchTool;
use App\Services\Agent\Tools\UrlReaderTool;
use App\Services\Agent\Tools\WebSearchTool;
use App\Services\RagService;

class ToolRegistry
{
    /** @var BaseTool[] */
    private array $tools = [];

    public function __construct(Project $project, Chat $chat)
    {
        $this->register(new WebSearchTool());
        $this->register(new UrlReaderTool());
        $this->register(new CalculatorTool());
        $this->register(new KnowledgeBaseSearchTool(
            new RagService(),
            $project,
            $chat,
        ));
    }

    public function register(BaseTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Get all tool names.
     */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Get a specific tool by name.
     */
    public function get(string $name): ?BaseTool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Execute a tool by name with given input.
     * Returns the output string.
     */
    public function execute(string $toolName, array $input): string
    {
        $tool = $this->get($toolName);

        if (!$tool) {
            return "Error: unknown tool \"{$toolName}\". Available tools: " . implode(', ', $this->names());
        }

        try {
            return $tool->execute($input);
        } catch (\Throwable $e) {
            return "Tool \"{$toolName}\" failed: {$e->getMessage()}";
        }
    }

    /**
     * Build the tools section of the system prompt.
     * The LLM reads this to know which tools exist and how to call them.
     */
    public function toSystemPrompt(): string
    {
        $lines   = [];
        $lines[] = "## Available Tools\n";
        $lines[] = "You can use the following tools by responding with this EXACT format:\n";
        $lines[] = "```";
        $lines[] = "Thought: <your reasoning about what to do next>";
        $lines[] = "Tool: <tool_name>";
        $lines[] = "Input: {\"param\": \"value\"}";
        $lines[] = "```\n";
        $lines[] = "After receiving the tool result, continue reasoning until you have a final answer.\n";
        $lines[] = "When you have the final answer, respond with:\n";
        $lines[] = "```";
        $lines[] = "Thought: I now have enough information to answer.";
        $lines[] = "Final Answer: <your complete answer here>";
        $lines[] = "```\n";
        $lines[] = "### Tool Definitions\n";

        foreach ($this->tools as $tool) {
            $lines[] = $tool->toPromptString();
            $lines[] = '';
        }

        $lines[] = "### Rules";
        $lines[] = "- ALWAYS use the calculator tool for any math — do NOT compute in your head.";
        $lines[] = "- ALWAYS use web_search for current events, prices, or recent data.";
        $lines[] = "- Use search_knowledge_base when the user asks about uploaded documents.";
        $lines[] = "- Use read_url only for specific URLs mentioned by the user or found in search results.";
        $lines[] = "- Do NOT fabricate tool results. Only use what the tool actually returns.";
        $lines[] = "- Stop and give Final Answer once you have enough information. Max steps: {max_steps}.";

        return implode("\n", $lines);
    }

    /**
     * Parse the LLM response to extract tool call or final answer.
     * Returns: ['type' => 'tool', 'name' => ..., 'input' => [...]]
     *       or ['type' => 'final', 'answer' => ...]
     *       or ['type' => 'thinking', 'thought' => ...]
     */
    public function parseResponse(string $response): array
    {
        $response = trim($response);

        // Check for Final Answer
        if (preg_match('/Final Answer:\s*(.+)/si', $response, $m)) {
            return [
                'type'   => 'final',
                'answer' => trim($m[1]),
            ];
        }

        // Check for Tool call
        if (
            preg_match('/Tool:\s*(\w+)/i', $response, $toolMatch) &&
            preg_match('/Input:\s*(\{.+?\})/si', $response, $inputMatch)
        ) {
            $toolName = trim($toolMatch[1]);
            $input    = [];

            try {
                $input = json_decode($inputMatch[1], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // Try to extract simple key:value
                preg_match_all('/"(\w+)"\s*:\s*"([^"]+)"/', $inputMatch[1], $pairs);
                foreach ($pairs[1] as $i => $key) {
                    $input[$key] = $pairs[2][$i];
                }
            }

            $thought = '';
            if (preg_match('/Thought:\s*(.+?)(?=Tool:|$)/si', $response, $thoughtMatch)) {
                $thought = trim($thoughtMatch[1]);
            }

            return [
                'type'    => 'tool',
                'name'    => $toolName,
                'input'   => $input,
                'thought' => $thought,
            ];
        }

        // Just thinking / no structured output
        $thought = '';
        if (preg_match('/Thought:\s*(.+)/si', $response, $m)) {
            $thought = trim($m[1]);
        }

        return [
            'type'    => 'thinking',
            'thought' => $thought ?: $response,
            'raw'     => $response,
        ];
    }
}