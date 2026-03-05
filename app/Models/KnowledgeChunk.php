<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'document_id', 'knowledge_base_id', 'tenant_id', 'content', 'chunk_index',
    ];

    public function document() { return $this->belongsTo(KnowledgeDocument::class); }
    public function knowledgeBase() { return $this->belongsTo(KnowledgeBase::class); }
}