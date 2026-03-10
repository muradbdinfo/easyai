<?php

// FILE: app/Services/TeamService.php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\ProjectMember;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;

class TeamService
{
    public function __construct(private NotificationService $notifications) {}

    public function createInvitation(
        Tenant $tenant,
        User   $inviter,
        string $email,
        string $role
    ): TeamInvitation {
        // Expire any existing pending invitation for same email in this tenant
        TeamInvitation::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $invitation = TeamInvitation::create([
            'tenant_id'  => $tenant->id,
            'invited_by' => $inviter->id,
            'email'      => $email,
            'role'       => $role,
            'token'      => TeamInvitation::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Email invitee + in-app notification if they have an account
        $this->notifications->invitationSent($invitation);

        return $invitation;
    }

    public function acceptInvitation(TeamInvitation $invitation, array $userData): User
    {
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            $user->update([
                'tenant_id' => $invitation->tenant_id,
                'role'      => $invitation->role,
                'is_active' => true,
            ]);
        } else {
            $user = User::create([
                'name'      => $userData['name'],
                'email'     => $invitation->email,
                'password'  => bcrypt($userData['password']),
                'role'      => $invitation->role,
                'tenant_id' => $invitation->tenant_id,
                'is_active' => true,
            ]);
        }

        $invitation->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        // Email inviter + in-app notify inviter + all other admins
        $this->notifications->invitationAccepted($invitation, $user);

        return $user;
    }

    public function removeMember(User $member, int $tenantId): void
    {
        ProjectMember::where('user_id', $member->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        $member->update([
            'tenant_id' => null,
            'is_active' => false,
        ]);
    }
}