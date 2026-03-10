<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'project_id',
        'source',
        'external_id',
        'name',
        'path',
        'content',
        'byte_size',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeGithub($query)
    {
        return $query->where('source', 'github');
    }

    public function scopeDrive($query)
    {
        return $query->where('source', 'drive');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}