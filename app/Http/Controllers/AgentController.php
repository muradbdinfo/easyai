<?php

namespace App\Http\Controllers;

use App\Jobs\RunAgentJob;
use App\Models\AgentRun;
use App\Models\Chat;
use App\Models\Project;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function run(Request $request, Project $project, Chat $chat)
    {
        $tenant = app('tenant');

        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($chat->tenant_id !== $tenant->id, 403);
        abort_if($chat->isClosed(), 422, 'Chat is closed.');

        $validated = $request->validate([
            'goal'      => ['required', 'string', 'max:2000'],
            'max_steps' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $agentRun = AgentRun::create([
            'chat_id'   => $chat->id,
            'tenant_id' => $tenant->id,
            'user_id'   => $request->user()->id,
            'goal'      => $validated['goal'],
            'status'    => 'running',
            'max_steps' => $validated['max_steps'] ?? 10,
        ]);

        RunAgentJob::dispatch($agentRun->id, $tenant->id, $request->user()->id);

        return back()->with('agent_run_id', $agentRun->id);
    }

    public function steps(Request $request, Project $project, Chat $chat, AgentRun $agentRun)
    {
        $tenant = app('tenant');

        abort_if($agentRun->tenant_id !== $tenant->id, 403);
        abort_if($agentRun->chat_id !== $chat->id, 403);

        return response()->json([
            'success' => true,
            'data'    => [
                'run'   => $agentRun->only(['id', 'status', 'steps_count', 'tokens_used', 'final_answer', 'error_message']),
                'steps' => $agentRun->steps()->orderBy('step_number')->get(),
            ],
        ]);
    }

    public function stop(Request $request, Project $project, Chat $chat, AgentRun $agentRun)
    {
        $tenant = app('tenant');

        abort_if($agentRun->tenant_id !== $tenant->id, 403);

        if ($agentRun->isRunning()) {
            $agentRun->update(['status' => 'stopped']);
        }

        return back()->with('success', 'Agent stopped.');
    }
}