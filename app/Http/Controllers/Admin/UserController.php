<?php
// FILE: app/Http/Controllers/Admin/UserController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    // GET /users
    public function index(Request $request)
    {
        $query = User::with('tenant:id,name,slug')
            ->where('role', '!=', 'superadmin');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name',  'like', "%$s%")
                  ->orWhere('email','like', "%$s%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(25)->withQueryString();

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'tenants' => $tenants,
            'filters' => $request->only('search', 'role', 'tenant_id', 'status'),
            'stats'   => [
                'total'    => User::where('role', '!=', 'superadmin')->count(),
                'admins'   => User::where('role', 'admin')->count(),
                'members'  => User::where('role', 'member')->count(),
                'inactive' => User::where('is_active', false)->where('role', '!=', 'superadmin')->count(),
            ],
        ]);
    }

    // PUT /users/{user}/role
    public function updateRole(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin(), 403, 'Cannot change superadmin role.');

        $request->validate(['role' => ['required', 'in:admin,member']]);
        $user->update(['role' => $request->role]);

        return back()->with('success', "{$user->name}'s role updated to {$request->role}.");
    }

    // PUT /users/{user}/status
    public function toggleStatus(User $user)
    {
        abort_if($user->isSuperAdmin(), 403);

        $user->update(['is_active' => !$user->is_active]);
        $label = $user->fresh()->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$label}.");
    }

    // DELETE /users/{user}
    public function destroy(User $user)
    {
        abort_if($user->isSuperAdmin(), 403, 'Cannot delete superadmin.');

        $name = $user->name;

        // Revoke tokens
        $user->tokens()->delete();

        // Detach from tenant (don't delete the tenant)
        $user->update(['tenant_id' => null, 'is_active' => false]);
        $user->delete();

        return back()->with('success', "{$name} has been deleted.");
    }
}