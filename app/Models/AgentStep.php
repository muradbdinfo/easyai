<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStep extends Model
{
    protected $fillable = [
        'agent_run_id', 'tenant_id', 'step_number',
        'thought', 'tool_name', 'tool_input',
        'tool_output', 'status', 'tokens',
    ];

    protected $casts = [
        'tool_input' => 'array',
    ];

    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }
}