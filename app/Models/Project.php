<?php

// FILE: app/Models/Project.php
// CHANGES from original:
//   + 'is_restricted' added to $fillable
//   + projectMembers() relationship
//   + members() relationship (users through project_members)
//   + isRestricted() helper

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'system_prompt',
        'context_summary',
        'model',
        'is_restricted','is_default', 
    ];

    protected $casts = [
        'is_restricted' => 'boolean',
        'is_default'    => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function knowledgeBases()
    {
        return $this->hasMany(\App\Models\KnowledgeBase::class);
    }

    /** Explicit project member records */
    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** Users that have been explicitly added to this project */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isRestricted(): bool
    {
        return (bool) $this->is_restricted;
    }
}
