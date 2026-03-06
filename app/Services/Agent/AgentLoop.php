<?php

namespace App\Services\Agent;

use App\Models\AgentRun;
use App\Models\AgentStep;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\OllamaService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Log;

class AgentLoop
{
    private ToolRegistry $tools;

    public function __construct(
        private OllamaService       $ollama,
        private QuotaService        $quota,
        private TokenCounterService $counter,
    ) {}

    /**
     * Run the full ReAct loop for an AgentRun.
     * Thought → Tool → Observation → repeat → Final Answer
     */
    public function run(AgentRun $agentRun): void
    {
        $chat    = Chat::with('project')->find($agentRun->chat_id);
        $tenant  = Tenant::find($agentRun->tenant_id);

        if (!$chat || !$tenant || !$chat->project) {
            $this->fail($agentRun, 'Chat or tenant not found.');
            return;
        }

        $project = $chat->project;
        $model   = $project->model ?? config('ollama.model');

        $this->tools = new ToolRegistry($project, $chat);

        // ── Build initial messages ────────────────────────────────
        $messages = $this->buildInitialMessages($agentRun, $project);

        $stepNumber  = 0;
        $totalTokens = 0;
        $maxSteps    = $agentRun->max_steps ?? 10;

        // ── ReAct Loop ────────────────────────────────────────────
        while ($stepNumber < $maxSteps) {

            // Check if stopped externally
            $agentRun->refresh();
            if ($agentRun->status === 'stopped') {
                Log::info("AgentLoop: run #{$agentRun->id} stopped externally.");
                return;
            }

            // Check quota
            if ($this->quota->isExceeded($tenant)) {
                $this->fail($agentRun, 'Token quota exceeded.');
                return;
            }

            $stepNumber++;

            // ── Call Ollama ───────────────────────────────────────
            try {
                $response = $this->ollama->chat($messages, $model);
            } catch (\Throwable $e) {
                Log::error("AgentLoop Ollama error: {$e->getMessage()}");
                $this->fail($agentRun, "Ollama error: {$e->getMessage()}");
                return;
            }

            $llmOutput   = trim($response['content']);
            $stepTokens  = $response['prompt_tokens'] + $response['completion_tokens'];

            if ($stepTokens === 0) {
                $stepTokens = $this->counter->estimate($llmOutput);
            }

            $totalTokens += $stepTokens;

            // ── Parse the LLM response ────────────────────────────
            $parsed = $this->tools->parseResponse($llmOutput);

            // ── Final Answer reached ──────────────────────────────
            if ($parsed['type'] === 'final') {

                $this->saveStep($agentRun, $stepNumber, [
                    'thought'     => 'Final answer reached.',
                    'tool_name'   => null,
                    'tool_input'  => null,
                    'tool_output' => null,
                    'status'      => 'completed',
                    'tokens'      => $stepTokens,
                ]);

                $finalAnswer = $parsed['answer'];

                // Save final answer as assistant message in chat
                Message::create([
                    'chat_id'   => $agentRun->chat_id,
                    'tenant_id' => $agentRun->tenant_id,
                    'role'      => 'assistant',
                    'content'   => $finalAnswer,
                    'tokens'    => $totalTokens,
                    'model'     => $model,
                ]);

                // Update run
                $agentRun->update([
                    'status'       => 'completed',
                    'final_answer' => $finalAnswer,
                    'tokens_used'  => $totalTokens,
                    'steps_count'  => $stepNumber,
                ]);

                // Deduct quota + log usage
                $this->quota->deduct($tenant, $totalTokens);
                $this->logUsage($agentRun, $totalTokens, $model);

                Log::info("AgentLoop: run #{$agentRun->id} completed in {$stepNumber} steps.");
                return;
            }

            // ── Tool call ─────────────────────────────────────────
            if ($parsed['type'] === 'tool') {

                $toolName  = $parsed['name'];
                $toolInput = $parsed['input'] ?? [];
                $thought   = $parsed['thought'] ?? '';

                // Execute tool
                $toolOutput = $this->tools->execute($toolName, $toolInput);

                // Save step
                $this->saveStep($agentRun, $stepNumber, [
                    'thought'     => $thought,
                    'tool_name'   => $toolName,
                    'tool_input'  => $toolInput,
                    'tool_output' => $toolOutput,
                    'status'      => 'completed',
                    'tokens'      => $stepTokens,
                ]);

                // Append to messages: LLM output + tool result as observation
                $messages[] = [
                    'role'    => 'assistant',
                    'content' => $llmOutput,
                ];
                $messages[] = [
                    'role'    => 'user',
                    'content' => "Observation: {$toolOutput}",
                ];

                // Update run progress
                $agentRun->update([
                    'steps_count' => $stepNumber,
                    'tokens_used' => $totalTokens,
                ]);

                continue;
            }

            // ── Just thinking (no tool, no final answer) ──────────
            // The LLM is reasoning — append and continue
            $this->saveStep($agentRun, $stepNumber, [
                'thought'     => $parsed['thought'] ?? $llmOutput,
                'tool_name'   => null,
                'tool_input'  => null,
                'tool_output' => null,
                'status'      => 'completed',
                'tokens'      => $stepTokens,
            ]);

            $messages[] = [
                'role'    => 'assistant',
                'content' => $llmOutput,
            ];

            $agentRun->update([
                'steps_count' => $stepNumber,
                'tokens_used' => $totalTokens,
            ]);
        }

        // ── Max steps exceeded ────────────────────────────────────
        // Force a final answer from the LLM
        Log::warning("AgentLoop: run #{$agentRun->id} hit max steps ({$maxSteps}). Forcing final answer.");

        $messages[] = [
            'role'    => 'user',
            'content' => 'You have reached the maximum number of steps. '
                       . 'Please provide your best Final Answer now based on what you have gathered so far.',
        ];

        try {
            $finalResponse = $this->ollama->chat($messages, $model);
            $finalParsed   = $this->tools->parseResponse($finalResponse['content']);
            $finalAnswer   = $finalParsed['type'] === 'final'
                ? $finalParsed['answer']
                : $finalResponse['content'];
        } catch (\Throwable $e) {
            $finalAnswer = 'Agent reached maximum steps without a definitive answer.';
        }

        Message::create([
            'chat_id'   => $agentRun->chat_id,
            'tenant_id' => $agentRun->tenant_id,
            'role'      => 'assistant',
            'content'   => $finalAnswer,
            'tokens'    => $totalTokens,
            'model'     => $model,
        ]);

        $agentRun->update([
            'status'       => 'completed',
            'final_answer' => $finalAnswer,
            'tokens_used'  => $totalTokens,
            'steps_count'  => $stepNumber,
        ]);

        $this->quota->deduct($tenant, $totalTokens);
        $this->logUsage($agentRun, $totalTokens, $model);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function buildInitialMessages(AgentRun $agentRun, Project $project): array
    {
        $toolsPrompt = str_replace(
            '{max_steps}',
            (string) ($agentRun->max_steps ?? 10),
            $this->tools->toSystemPrompt()
        );

        $systemContent = "You are an autonomous AI agent. Your job is to complete goals step-by-step.\n\n"
            . $toolsPrompt;

        // Add project system prompt if set
        if (!empty($project->system_prompt)) {
            $systemContent .= "\n\n## Project Instructions\n" . $project->system_prompt;
        }

        return [
            [
                'role'    => 'system',
                'content' => $systemContent,
            ],
            [
                'role'    => 'user',
                'content' => "Goal: {$agentRun->goal}",
            ],
        ];
    }

    private function saveStep(AgentRun $agentRun, int $stepNumber, array $data): AgentStep
    {
        return AgentStep::create([
            'agent_run_id' => $agentRun->id,
            'tenant_id'    => $agentRun->tenant_id,
            'step_number'  => $stepNumber,
            'thought'      => $data['thought'] ?? null,
            'tool_name'    => $data['tool_name'] ?? null,
            'tool_input'   => $data['tool_input'] ?? null,
            'tool_output'  => $data['tool_output'] ?? null,
            'status'       => $data['status'] ?? 'completed',
            'tokens'       => $data['tokens'] ?? 0,
        ]);
    }

    private function fail(AgentRun $agentRun, string $reason): void
    {
        $agentRun->update([
            'status'        => 'failed',
            'error_message' => $reason,
        ]);

        // Save failure as assistant message so user sees it
        Message::create([
            'chat_id'   => $agentRun->chat_id,
            'tenant_id' => $agentRun->tenant_id,
            'role'      => 'assistant',
            'content'   => "⚠️ Agent failed: {$reason}",
            'tokens'    => 0,
            'model'     => config('ollama.model'),
        ]);

        Log::error("AgentLoop: run #{$agentRun->id} failed — {$reason}");
    }

    private function logUsage(AgentRun $agentRun, int $totalTokens, string $model): void
    {
        try {
            UsageLog::create([
                'tenant_id'         => $agentRun->tenant_id,
                'user_id'           => $agentRun->user_id,
                'chat_id'           => $agentRun->chat_id,
                'model'             => $model,
                'prompt_tokens'     => 0,
                'completion_tokens' => $totalTokens,
                'total_tokens'      => $totalTokens,
                'cost'              => 0.000000,
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AgentLoop: usage log failed — ' . $e->getMessage());
        }
    }
}