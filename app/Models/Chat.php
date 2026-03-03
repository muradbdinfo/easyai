<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'tenant_id',
        'title',
        'total_tokens',
        'status',
        'closed_reason',
    ];

    protected $casts = [
        'total_tokens' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // ─── Status Helpers ───────────────────────────────────────────
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}