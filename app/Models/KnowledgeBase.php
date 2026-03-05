<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = [
        'project_id', 'chat_id', 'tenant_id', 'name', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function project()   { return $this->belongsTo(Project::class); }
    public function chat()      { return $this->belongsTo(Chat::class); }
    public function tenant()    { return $this->belongsTo(Tenant::class); }
    public function documents() { return $this->hasMany(KnowledgeDocument::class); }
    public function chunks()    { return $this->hasMany(KnowledgeChunk::class); }

    public function isChatLevel(): bool    { return !is_null($this->chat_id); }
    public function isProjectLevel(): bool { return is_null($this->chat_id); }
}