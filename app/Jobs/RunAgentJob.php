<?php

namespace App\Jobs;

use App\Models\AgentRun;
use App\Services\Agent\AgentLoop;
use App\Services\OllamaService;
use App\Services\QuotaService;
use App\Services\TokenCounterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes max per run
    public int $tries   = 1;   // Don't retry — agent runs are stateful

    public function __construct(
        public int $agentRunId,
        public int $tenantId,
        public int $userId,
    ) {}

    public function handle(
        OllamaService       $ollama,
        QuotaService        $quota,
        TokenCounterService $counter,
    ): void {

        $agentRun = AgentRun::find($this->agentRunId);

        if (!$agentRun) {
            Log::warning("RunAgentJob: AgentRun #{$this->agentRunId} not found.");
            return;
        }

        // Already stopped or completed (e.g. duplicate dispatch)
        if (!$agentRun->isRunning()) {
            Log::info("RunAgentJob: AgentRun #{$this->agentRunId} is not running (status: {$agentRun->status}). Skipping.");
            return;
        }

        Log::info("RunAgentJob: starting AgentRun #{$this->agentRunId}");

        $loop = new AgentLoop($ollama, $quota, $counter);

        try {
            $loop->run($agentRun);
        } catch (\Throwable $e) {
            Log::error("RunAgentJob: unhandled exception for run #{$this->agentRunId} — {$e->getMessage()}");

            $agentRun->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("RunAgentJob failed permanently: run #{$this->agentRunId} — {$exception->getMessage()}");

        AgentRun::where('id', $this->agentRunId)
            ->where('status', 'running')
            ->update([
                'status'        => 'failed',
                'error_message' => 'Job failed: ' . $exception->getMessage(),
            ]);
    }
}