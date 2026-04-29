<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeDocument extends Model
{
    protected $fillable = [
        'knowledge_base_id', 'tenant_id', 'title',
        'source_type', 'source_url',
        'file_path', 'file_type', 'file_size',
        'status', 'error_message', 'chunk_count',
    ];

    // Supported file types
    public static array $supportedTypes = ['pdf', 'txt', 'md', 'docx', 'doc', 'xlsx', 'xls'];
    public static string $mimeTypes     = '.pdf,.txt,.md,.docx,.doc,.xlsx,.xls';
    public static int $maxSizeMb        = 20;

    public function knowledgeBase() { return $this->belongsTo(KnowledgeBase::class); }
    public function tenant()        { return $this->belongsTo(Tenant::class); }
    public function chunks()        { return $this->hasMany(KnowledgeChunk::class, 'document_id'); }

    public function isReady(): bool { return $this->status === 'ready'; }

    public function fileTypeLabel(): string
    {
        return strtoupper($this->file_type);
    }

    public function isUrl(): bool
    {
        return $this->source_type === 'url';
    }

    public function isFile(): bool
    {
        return $this->source_type === 'file';
    }

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            'url'    => 'URL',
            'github' => 'GitHub',
            default  => strtoupper($this->file_type ?? 'FILE'),
        };
    }
}
