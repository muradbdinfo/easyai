<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan_id',
        'token_quota',
        'tokens_used',
        'status',
        'trial_ends_at',
        'logo_path',      // ADDED
        'default_model',  // ADDED
    ];

    protected $casts = [
        'token_quota'    => 'integer',
        'tokens_used'    => 'integer',
        'trial_ends_at'  => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function teamInvitations()
    {
        return $this->hasMany(\App\Models\TeamInvitation::class);
    }

    // ─── Status Helpers ───────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    // ─── Token Helpers ────────────────────────────────────────────

    public function tokensRemaining(): int
    {
        return max(0, $this->token_quota - $this->tokens_used);
    }

    public function percentUsed(): float
    {
        if ($this->token_quota <= 0) return 0;
        return round(($this->tokens_used / $this->token_quota) * 100, 2);
    }

    // ─── Logo Helper ──────────────────────────────────────────────  ADDED

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? asset('storage/' . $this->logo_path)
            : null;
    }
}