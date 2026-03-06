<?php

// FILE: app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'notification_preferences', // ADDED
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'        => 'datetime',
        'password'                 => 'hashed',
        'is_active'                => 'boolean',
        'notification_preferences' => 'array', // ADDED
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Invitations this user sent to others */
    public function sentInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'invited_by');
    }

    /** Project-level memberships for this user */
    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    // ─── Team Helpers ─────────────────────────────────────────────

    /** True if user can manage team members (admin or superadmin) */
    public function canManageTeam(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /** Check if user has access to a specific project */
    public function canAccessProject(Project $project): bool
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if (!$project->is_restricted) {
            return true;
        }

        return ProjectMember::where('project_id', $project->id)
            ->where('user_id', $this->id)
            ->exists();
    }

    // ─── Notification Helpers ─────────────────────────────────────  ADDED

    public function getNotificationPreferences(): array
    {
        return $this->notification_preferences ?? [
            'quota_warning'   => true,
            'quota_exceeded'  => true,
            'payment_confirm' => true,
            'team_invitation' => true,
        ];
    }
}