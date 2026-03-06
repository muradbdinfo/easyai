<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'user_id', 'plan_id', 'addon_id','method',
        'amount', 'currency', 'status', 'transaction_id',
        'gateway_response', 'invoice_number', 'invoice_path',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'approved_at'      => 'datetime',
        'amount'           => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
public function addon()
{
    return $this->belongsTo(Addon::class);
}
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}