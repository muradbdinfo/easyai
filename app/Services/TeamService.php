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
    /**
     * Create (or refresh) an invitation for the given email+tenant.
     * If a pending invitation already exists for this email, it is expired
     * first so there is always only one active token per email per tenant.
     */
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

        // Create an in-app notification if the invitee already has an account
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            AppNotification::create([
                'tenant_id'  => $existingUser->tenant_id ?? $tenant->id,
                'user_id'    => $existingUser->id,
                'type'       => 'team_invitation',
                'title'      => 'Team Invitation',
                'body'       => $inviter->name . ' invited you to join ' . $tenant->name . ' as ' . $role . '.',
                'action_url' => '/invitation/' . $invitation->token,
                'data'       => [
                    'tenant_id'     => $tenant->id,
                    'tenant_name'   => $tenant->name,
                    'inviter_name'  => $inviter->name,
                    'role'          => $role,
                    'token'         => $invitation->token,
                ],
            ]);
        }

        return $invitation;
    }

    /**
     * Accept an invitation.
     *
     * For new users: provide ['name' => ..., 'password' => ...]
     * For existing users already logged in: provide []
     */
    public function acceptInvitation(TeamInvitation $invitation, array $userData): User
    {
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            // Existing user — move to new tenant, update role
            $user->update([
                'tenant_id' => $invitation->tenant_id,
                'role'      => $invitation->role,
                'is_active' => true,
            ]);
        } else {
            // New user — register them
            $user = User::create([
                'name'      => $userData['name'],
                'email'     => $invitation->email,
                'password'  => bcrypt($userData['password']),
                'role'      => $invitation->role,
                'tenant_id' => $invitation->tenant_id,
                'is_active' => true,
            ]);
        }

        // Mark invitation as accepted
        $invitation->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        // Notify the inviter
        $inviter = $invitation->inviter;
        if ($inviter) {
            AppNotification::create([
                'tenant_id'  => $invitation->tenant_id,
                'user_id'    => $inviter->id,
                'type'       => 'invitation_accepted',
                'title'      => 'Invitation Accepted',
                'body'       => $user->name . ' accepted your invitation to join the workspace.',
                'action_url' => '/team',
                'data'       => ['user_id' => $user->id, 'user_name' => $user->name],
            ]);
        }

        return $user;
    }

    /**
     * Remove a member from a tenant.
     * Clears their project memberships within that tenant,
     * then detaches them from the tenant.
     */
    public function removeMember(User $member, int $tenantId): void
    {
        // Remove from restricted projects within this tenant
        ProjectMember::where('user_id', $member->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        // Detach from tenant
        $member->update([
            'tenant_id' => null,
            'is_active' => false,
        ]);
    }
}
