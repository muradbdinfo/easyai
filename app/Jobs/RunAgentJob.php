<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(
        public int $agentRunId,
        public int $tenantId,
        public int $userId,
    ) {}

    public function handle(): void
    {
        // Full implementation added in Step 8 (AgentLoop + ToolRegistry)
        Log::info("RunAgentJob: run #{$this->agentRunId} queued — engine not yet implemented.");

        \App\Models\AgentRun::where('id', $this->agentRunId)->update([
            'status'       => 'failed',
            'error_message' => 'Agent engine not yet installed. Coming in Step 8.',
        ]);
    }
}