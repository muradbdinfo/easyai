<?php

// FILE: app/Http/Controllers/Api/V1/TeamController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function index(): JsonResponse
    {
        $tenant = app('tenant');

        $members = User::where('tenant_id', $tenant->id)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'role' => $u->role, 'is_active' => $u->is_active, 'joined_at' => $u->created_at,
            ]);

        $invitations = TeamInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')->where('expires_at', '>', now())
            ->get(['id', 'email', 'role', 'status', 'expires_at', 'created_at']);

        return response()->json([
            'success' => true, 'message' => 'OK',
            'data' => [
                'members'             => $members,
                'pending_invitations' => $invitations,
                'members_count'       => $members->count(),
                'seats'               => $this->seatInfo($tenant),
            ],
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        $tenant = app('tenant');

        $seats = $this->seatInfo($tenant);
        if ($seats['is_seat_plan'] && $seats['available'] <= 0) {
            return response()->json(['success' => false, 'message' => "Seat limit reached ({$seats['used']}/{$seats['purchased']})."], 422);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', 'in:admin,member'],
        ]);

        if (User::where('email', $validated['email'])->where('tenant_id', $tenant->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This email is already a member of the workspace.'], 422);
        }

        $invitation = $this->teamService->createInvitation($tenant, auth()->user(), $validated['email'], $validated['role']);

        return response()->json([
            'success' => true, 'message' => 'Invitation created.',
            'data' => [
                'invitation_id' => $invitation->id, 'email' => $invitation->email,
                'role' => $invitation->role, 'invite_url' => $invitation->invite_url,
                'expires_at' => $invitation->expires_at,
            ],
        ], 201);
    }

    public function cancelInvite(TeamInvitation $invitation): JsonResponse
    {
        abort_if($invitation->tenant_id !== app('tenant')->id, 403);
        abort_if(!auth()->user()->canManageTeam(), 403);
        $invitation->update(['status' => 'expired']);
        return response()->json(['success' => true, 'message' => 'Invitation cancelled.', 'data' => null]);
    }

    public function updateRole(Request $request, User $member): JsonResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403);
        $validated = $request->validate(['role' => ['required', 'in:admin,member']]);
        $member->update(['role' => $validated['role']]);
        return response()->json([
            'success' => true, 'message' => 'Member role updated.',
            'data' => ['id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'role' => $member->fresh()->role],
        ]);
    }

    public function toggleStatus(User $member): JsonResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403);
        $member->update(['is_active' => !$member->is_active]);
        return response()->json(['success' => true, 'message' => 'Member status updated.', 'data' => ['id' => $member->id, 'is_active' => $member->fresh()->is_active]]);
    }

    public function removeMember(User $member): JsonResponse
    {
        abort_if(!auth()->user()->canManageTeam(), 403);
        abort_if($member->tenant_id !== app('tenant')->id, 403);
        abort_if($member->id === auth()->id(), 403);
        $this->teamService->removeMember($member, app('tenant')->id);
        return response()->json(['success' => true, 'message' => 'Member removed from workspace.', 'data' => null]);
    }
}