<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    protected $fillable = [
        'chat_id', 'tenant_id', 'user_id', 'goal',
        'status', 'steps_count', 'max_steps',
        'tokens_used', 'final_answer', 'error_message',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AgentStep::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }
}