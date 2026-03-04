<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAttachment extends Model
{
    protected $fillable = [
        'chat_id',
        'message_id',
        'tenant_id',
        'user_id',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'type',
        'path',
        'url',
        'extracted_text',
        'file_size',
        'meta',
    ];

    protected $casts = [
        'meta'           => 'array',
        'file_size'      => 'integer',
        'has_attachment' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function chat():    \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function tenant():  \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user():    \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Type helpers ──────────────────────────────────────────────
    public function isImage(): bool { return $this->type === 'image'; }
    public function isPdf():   bool { return $this->type === 'pdf';   }
    public function isExcel(): bool { return $this->type === 'excel'; }
    public function isText():  bool { return $this->type === 'text';  }

    // ── Public URL ────────────────────────────────────────────────
    public function getPublicUrl(): string
    {
        if ($this->url) {
            return $this->url;
        }
        return asset('storage/' . $this->path);
    }

    // ── Formatted file size ───────────────────────────────────────
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)        return $bytes . ' B';
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}