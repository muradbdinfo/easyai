<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'tenant_id',
        'role',
        'content',
        'tokens',
        'model',
        'attachment_id',
        'has_attachment',
    ];

    protected $casts = [
        'tokens'         => 'integer',
        'has_attachment' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function chat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attachment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChatAttachment::class, 'attachment_id');
    }
}