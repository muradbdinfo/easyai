<?php

// FILE: app/Http/Controllers/ProjectMemberController.php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectMemberController extends Controller
{
    // ─── index ────────────────────────────────────────────────────
    // GET /projects/{project}/members
    public function index(Project $project): Response
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);

        $members = ProjectMember::where('project_id', $project->id)
            ->with('user:id,name,email,role,is_active')
            ->get()
            ->map(fn (ProjectMember $pm) => [
                'id'          => $pm->id,
                'user_id'     => $pm->user_id,
                'role'        => $pm->role,
                'name'        => $pm->user->name,
                'email'       => $pm->user->email,
                'tenant_role' => $pm->user->role,
                'is_active'   => $pm->user->is_active,
            ]);

        // Tenant users NOT yet in this project
        $addableUsers = User::where('tenant_id', $tenant->id)
            ->whereNotIn('id', $members->pluck('user_id'))
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Projects/Members', [
            'project'      => [
                'id'            => $project->id,
                'name'          => $project->name,
                'is_restricted' => $project->is_restricted,
            ],
            'members'      => $members,
            'addableUsers' => $addableUsers,
            'canManage'    => auth()->user()->canManageTeam(),
        ]);
    }

    // ─── add ──────────────────────────────────────────────────────
    // POST /projects/{project}/members
    public function add(Request $request, Project $project): RedirectResponse
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if(!auth()->user()->canManageTeam(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'in:owner,editor,viewer'],
        ]);

        // Security: user must belong to this tenant
        $user = User::where('id', $validated['user_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        ProjectMember::firstOrCreate(
            [
                'project_id' => $project->id,
                'user_id'    => $user->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'role'      => $validated['role'],
            ]
        );

        return back()->with('success', $user->name . ' added to project.');
    }

    // ─── updateRole ───────────────────────────────────────────────
    // PUT /projects/{project}/members/{member}
    public function updateRole(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($member->project_id !== $project->id, 404);
        abort_if(!auth()->user()->canManageTeam(), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:owner,editor,viewer'],
        ]);

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Member role updated.');
    }

    // ─── remove ───────────────────────────────────────────────────
    // DELETE /projects/{project}/members/{member}
    public function remove(Project $project, ProjectMember $member): RedirectResponse
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if($member->project_id !== $project->id, 404);
        abort_if(!auth()->user()->canManageTeam(), 403);

        $member->delete();

        return back()->with('success', 'Member removed from project.');
    }

    // ─── toggleRestricted ─────────────────────────────────────────
    // PUT /projects/{project}/members/restricted
    public function toggleRestricted(Project $project): RedirectResponse
    {
        $tenant = app('tenant');
        abort_if($project->tenant_id !== $tenant->id, 403);
        abort_if(!auth()->user()->canManageTeam(), 403);

        $newState = !$project->is_restricted;
        $project->update(['is_restricted' => $newState]);

        // When enabling restriction, ensure the toggling user is an owner
        if ($newState) {
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => auth()->id()],
                ['tenant_id' => $tenant->id, 'role' => 'owner']
            );
        }

        $msg = $newState
            ? 'Project is now restricted. Only added members can access it.'
            : 'Project is now open to all workspace members.';

        return back()->with('success', $msg);
    }
}
