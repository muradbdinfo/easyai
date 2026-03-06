<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAddon extends Model
{
    protected $fillable = [
        'tenant_id', 'addon_id', 'payment_id', 'status',
        'starts_at', 'expires_at', 'cancelled_at',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'cancelled_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }
}