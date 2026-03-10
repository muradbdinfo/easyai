<?php

// FILE: app/Http/Controllers/TeamController.php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Subscription;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private TeamService $teamService) {}

    private function seatInfo($tenant): array
    {
        $isSeat = $tenant->plan?->isSeatBased() ?? false;
        if (!$isSeat) return ['is_seat_plan' => false, 'purchased' => 0, 'used' => 0, 'available' => null];
        $sub = Subscription::where('tenant_id', $tenant->id)->where('status', 'active')->latest()->first();
        $purchased = $sub?->seats ?? 0;
        $used = User::where('tenant_id', $tenant->id)->count();
        return ['is_seat_plan' => true, 'purchased' => $purchased, 'used' => $used, 'available' => max(0, $purchased - $used)];
    }

    public function index(): Response
    {
        $tenant = app('tenant');
        $user   = auth()->user();

        $members = User::where('tenant_id', $tenant->id)
            ->orderByRaw("FIELD(role,'admin','member')")->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->map(fn (User $u) => [
                'id'        => $u->id,   'name'      => $u->name,
                'email'     => $u->email,'role'      => $u->role,
                'is_active' => $u->is_active,
                'is_self'   => $u->id === $user->id,
                'joined_at' => $u->created_at?->diffForHumans(),
            ]);

        $invitations = TeamInvitation::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending'])->where('expires_at', '>', now())
            ->with('inviter:id,name')->orderByDesc('created_at')->get()
            ->map(fn (TeamInvitation $inv) => [
                'id'         => $inv->id,    'email'      => $inv->email,
                'role'       => $inv->role,  'status'     => $inv->status,
                'expires_at' => $inv->expires_at->toDateTimeString(),
                'invite_url' => $inv->invite_url,
                'inviter'    => $inv->inviter?->name,
                'created_at' => $inv->created_at->diffForHumans(),
            ]);

        $notifications = AppNotification::where('user_id', $user->id)
            ->whereIn('type', ['team_invitation', 'invitation_accepted'])
            ->orderByDesc('created_at')->limit(30)->get()
            ->map(fn (AppNotification $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'action_url' => $n->action_url,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return Inertia::render('Team/Index', [
            'members'       => $members,
            'invitations'   => $invitations,
            'notifications' => $notifications,
            'canManage'     => $user->canManageTeam(),
            'currentUser'   => ['id' => $user->id, 'role' => $user->role],
            'seats'         => $this->seatInfo($tenant),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403, 'Only admins can invite members.');
        $tenant = app('tenant');

        $seats = $this->seatInfo($tenant);
        if ($seats['is_seat_plan'] && $seats['available'] <= 0) {
            return back()->withErrors(['email' => "Seat limit reached ({$seats['used']}/{$seats['purchased']}). Upgrade your plan to add more members."]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', 'in:admin,member'],
        ]);

        if (User::where('email', $validated['email'])->where('tenant_id', $tenant->id)->exists()) {
            return back()->withErrors(['email' => 'This person is already a member of your workspace.']);
        }

        $invitation = $this->teamService->createInvitation($tenant, auth()->user(), $validated['email'], $validated['role']);

        return back()->with(['success' => 'Invitation sent to ' . $validated['email'] . '.', 'invite_url' => $invitation->invite_url]);
    }

    public function resendInvite(TeamInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->tenant_id !== app('tenant')->id, 403);
        abort_if(!auth()->user()->canManageTeam(), 403);
        $invitation->update(['token' => TeamInvitation::generateToken(), 'status' => 'pending', 'expires_at' => now()->addDays(7)]);
        return back()->with(['success' => 'Invitation refreshed.', 'invite_url' => $invitation->fresh()->invite_url]);
    }

    public function cancelInvite(TeamInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->tenant_id !== app('tenant')->id, 403);
        abort_if(!auth()->user()->canManageTeam(), 403);
        $invitation->update(['status' => 'expired']);
        return back()->with('success', 'Invitation cancelled.');
    }

    public function updateRole(Request $request, User $member): RedirectResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403, 'You cannot change your own role.');
        abort_if($member->isSuperAdmin(), 403, 'Cannot modify superadmin.');
        $validated = $request->validate(['role' => ['required', 'in:admin,member']]);
        $member->update(['role' => $validated['role']]);
        return back()->with('success', $member->name . "'s role updated to " . $validated['role'] . '.');
    }

    public function toggleStatus(User $member): RedirectResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403, 'You cannot deactivate yourself.');
        $member->update(['is_active' => !$member->is_active]);
        $status = $member->fresh()->is_active ? 'activated' : 'deactivated';
        return back()->with('success', $member->name . ' has been ' . $status . '.');
    }

    public function removeMember(User $member): RedirectResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403, 'You cannot remove yourself.');
        abort_if($member->isSuperAdmin(), 403, 'Cannot remove superadmin.');
        $name = $member->name;
        $this->teamService->removeMember($member, app('tenant')->id);
        return back()->with('success', $name . ' has been removed from the workspace.');
    }

    // POST /team/notifications/read
    public function markNotificationsRead(Request $request): RedirectResponse
    {
        $query = AppNotification::where('user_id', auth()->id())
            ->whereIn('type', ['team_invitation', 'invitation_accepted'])
            ->whereNull('read_at');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        $query->update(['read_at' => now()]);
        return back();
    }
}