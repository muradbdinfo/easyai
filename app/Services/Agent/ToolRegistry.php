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

    public function names(): array
    {
        return array_keys($this->tools);
    }

    public function get(string $name): ?BaseTool
    {
        return $this->tools[$name] ?? null;
    }

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

    public function toSystemPrompt(int $maxSteps = 10): string
    {
        $toolDefs = '';
        foreach ($this->tools as $tool) {
            $toolDefs .= $tool->toPromptString() . "\n\n";
        }

        return <<<PROMPT
You are a highly capable AI agent with both your own training knowledge AND access to tools for live information.

## HOW TO RESPOND

You must ALWAYS respond in one of these two EXACT formats:

FORMAT A — Use a tool:
Thought: [your reasoning about what to do]
Tool: [tool_name]
Input: {"param": "value"}

FORMAT B — Give final answer (use this when you already know the answer OR after using tools):
Thought: [I have enough information to answer]
Final Answer: [your complete, detailed answer]

Do NOT add anything outside these formats. Do NOT use markdown code blocks around your response.

## WHEN TO USE TOOLS vs YOUR OWN KNOWLEDGE

USE YOUR OWN KNOWLEDGE (give Final Answer directly) when:
- The question is about a well-known person, place, historical event, or concept you already know
- The question asks for general explanation, definition, or background
- Examples: "Who is Sakib Al Hasan?", "What is photosynthesis?", "Who is Elon Musk?"

USE TOOLS when:
- The question needs CURRENT/LIVE data: today's score, latest news, current price, recent events
- The question is about math/calculation → use calculator
- The question is about uploaded documents → use search_knowledge_base
- The question explicitly asks for "latest", "current", "today", "news", "price", "score"
- You genuinely do not know the answer from your training

## AVAILABLE TOOLS

{$toolDefs}

## RULES

1. If you already know the answer well → give Final Answer IMMEDIATELY without using any tool.
2. Only use web_search for things that require live/current information you don't have.
3. If web_search returns poor results, try a simpler query before giving up.
4. For math: ALWAYS use calculator — never compute in your head.
5. Maximum steps: {$maxSteps}. Don't waste steps searching for things you already know.
6. Final Answer must be complete, detailed, and genuinely helpful.
7. NEVER say "I couldn't find information" about a well-known person or fact you clearly know from training.

## EXAMPLES

Goal: Who is Sakib Al Hasan?
Thought: I know Sakib Al Hasan well from my training. He is a famous Bangladeshi cricketer. I can answer directly.
Final Answer: Shakib Al Hasan is a Bangladeshi professional cricketer widely regarded as one of the greatest all-rounders in cricket history...

Goal: What is the current price of Bitcoin?
Thought: This requires live data I don't have. I'll search the web.
Tool: web_search
Input: {"query": "Bitcoin price today 2025"}

Goal: What is 1547 * 89?
Thought: I should use the calculator for accuracy.
Tool: calculator
Input: {"expression": "1547 * 89"}

---

Now complete the user's goal. Begin.
PROMPT;
    }

    /**
     * Parse the LLM response to extract tool call or final answer.
     */
    public function parseResponse(string $response): array
    {
        $response = trim($response);

        // ── Check for Final Answer ───────────────────────────────────────
        if (preg_match('/Final Answer:\s*(.+)/si', $response, $m)) {
            return [
                'type'   => 'final',
                'answer' => trim($m[1]),
            ];
        }

        // ── Check for Tool call ──────────────────────────────────────────
        $hasTool  = preg_match('/Tool:\s*(\w+)/i', $response, $toolMatch);
        $hasInput = preg_match('/Input:\s*(\{.+)/si', $response, $inputMatch);

        if ($hasTool && $hasInput) {
            $toolName = trim($toolMatch[1]);
            $input    = [];

            // Clean up the JSON — sometimes LLM adds trailing text
            $rawJson = $inputMatch[1];

            // Extract balanced JSON object
            $jsonStr = $this->extractJson($rawJson);

            try {
                $input = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // Fallback: extract simple "key": "value" pairs
                preg_match_all('/"(\w+)"\s*:\s*"([^"]+)"/', $rawJson, $pairs);
                foreach ($pairs[1] as $i => $key) {
                    $input[$key] = $pairs[2][$i];
                }

                // Also try integer values
                preg_match_all('/"(\w+)"\s*:\s*(\d+)/', $rawJson, $intPairs);
                foreach ($intPairs[1] as $i => $key) {
                    $input[$key] = (int) $intPairs[2][$i];
                }
            }

            $thought = '';
            if (preg_match('/Thought:\s*(.+?)(?=\n(?:Tool:|Input:)|$)/si', $response, $thoughtMatch)) {
                $thought = trim($thoughtMatch[1]);
            }

            return [
                'type'    => 'tool',
                'name'    => $toolName,
                'input'   => $input,
                'thought' => $thought,
            ];
        }

        // ── Thinking only (no structured output) ────────────────────────
        $thought = '';
        if (preg_match('/Thought:\s*(.+)/si', $response, $m)) {
            $thought = trim($m[1]);
        }

        // If the response looks like a final answer without the prefix, treat it as final
        if (
            !$hasTool
            && strlen($response) > 100
            && !str_contains($response, 'Tool:')
        ) {
            return [
                'type'   => 'final',
                'answer' => $thought ?: $response,
            ];
        }

        return [
            'type'    => 'thinking',
            'thought' => $thought ?: $response,
            'raw'     => $response,
        ];
    }

    /**
     * Extract a balanced JSON object from a string that may have trailing text.
     */
    private function extractJson(string $text): string
    {
        $text  = ltrim($text);
        $depth = 0;
        $start = strpos($text, '{');

        if ($start === false) return '{}';

        for ($i = $start; $i < strlen($text); $i++) {
            if ($text[$i] === '{') $depth++;
            if ($text[$i] === '}') $depth--;
            if ($depth === 0) {
                return substr($text, $start, $i - $start + 1);
            }
        }

        return substr($text, $start);
    }
}