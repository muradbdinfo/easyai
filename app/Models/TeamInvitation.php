<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invited_by',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ─── Status Helpers ───────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    // ─── Token Generation ─────────────────────────────────────────

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // 64-char hex string
    }

    // ─── Invitation URL ───────────────────────────────────────────

    public function getInviteUrlAttribute(): string
    {
        return 'http://easyai.local/invitation/' . $this->token;
    }
}
