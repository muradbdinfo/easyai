<?php

namespace App\Services\Agent\Tools;

abstract class BaseTool
{
    /**
     * Unique tool name — used by the LLM to call this tool.
     */
    abstract public function name(): string;

    /**
     * One-line description the LLM sees when deciding which tool to use.
     */
    abstract public function description(): string;

    /**
     * JSON-schema-style parameter definitions.
     * Each entry: ['name' => '', 'type' => 'string', 'description' => '', 'required' => true]
     */
    abstract public function parameters(): array;

    /**
     * Execute the tool. Returns a plain-text result string.
     */
    abstract public function execute(array $input): string;

    /**
     * Render the tool signature for inclusion in the system prompt.
     */
    public function toPromptString(): string
    {
        $params = collect($this->parameters())
            ->map(fn($p) => "  - {$p['name']} ({$p['type']}, " . ($p['required'] ? 'required' : 'optional') . "): {$p['description']}")
            ->implode("\n");

        return "Tool: {$this->name()}\nDescription: {$this->description()}\nParameters:\n{$params}";
    }
}