<?php
// FILE: app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\GitHubConnection;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{


public function integrations()
    {
        $tenant = app('tenant');
        $conn   = GitHubConnection::where('tenant_id', $tenant->id)
            ->where('user_id', Auth::id())
            ->first();

        $projects = Project::where('tenant_id', $tenant->id)
            ->select('id', 'name')
            ->latest()
            ->get();

        return Inertia::render('Settings/Integrations', [
            'github' => [
                'connected'   => (bool) $conn,
                'github_user' => $conn?->github_user,
            ],
            'projects' => $projects,
        ]);
    }

    
    // ── Only admin/superadmin may manage workspace ────────────────
    private function requireAdmin(): void
    {
        abort_if(
            ! in_array(auth()->user()->role, ['admin', 'superadmin']),
            403,
            'Only workspace admins can perform this action.'
        );
    }

    // ── index ─────────────────────────────────────────────────────
    public function index()
    {
        $user   = auth()->user();
        $tenant = app('tenant');

        return Inertia::render('Settings/Index', [       // ← was 'Admin/Settings/Index'
            'user' => array_merge($user->only('id', 'name', 'email', 'role'), [
                'notification_preferences' => $user->notification_preferences ?? [
                    'quota_warning'   => true,
                    'quota_exceeded'  => true,
                    'payment_confirm' => true,
                    'team_invitation' => true,
                ],
            ]),
            'tenant'        => array_merge(
                $tenant->only('id', 'name', 'slug', 'logo_path', 'default_model'),
                ['logo_url' => $tenant->logo_path ? asset('storage/' . $tenant->logo_path) : null]
            ),
            'api_tokens'    => $user->tokens()
                ->select('id', 'name', 'last_used_at', 'created_at')
                ->latest()->get(),
            'ollama_models' => array_values(array_filter(
                config('ollama.available_models', [config('ollama.model', 'llama3')])
            )),
        ]);
    }

    // ── updateProfile — all roles ─────────────────────────────────
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . auth()->id()],
        ]);
        auth()->user()->update($request->only('name', 'email'));
        return back()->with('success', 'Profile updated.');
    }

    // ── updatePassword — all roles ────────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);
        if (! Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        auth()->user()->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password updated.');
    }

    // ── updateWorkspace — admin only ──────────────────────────────
    public function updateWorkspace(Request $request)
    {
        $this->requireAdmin();
        $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'default_model' => ['required', 'string'],
        ]);
        app('tenant')->update($request->only('name', 'default_model'));
        return back()->with('success', 'Workspace settings saved.');
    }

    // ── uploadLogo — admin only ───────────────────────────────────
    public function uploadLogo(Request $request)
    {
        $this->requireAdmin();
        $request->validate(['logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048']]);

        $tenant = app('tenant');
        if ($tenant->logo_path) Storage::disk('public')->delete($tenant->logo_path);

        $path = $request->file('logo')->store('logos', 'public');
        $tenant->update([
            'logo_path' => $path,
            'logo_url'  => asset('storage/' . $path),
        ]);
        return back()->with('success', 'Logo updated.');
    }

    // ── deleteLogo — admin only ───────────────────────────────────
    public function deleteLogo()
    {
        $this->requireAdmin();
        $tenant = app('tenant');
        if ($tenant->logo_path) Storage::disk('public')->delete($tenant->logo_path);
        $tenant->update(['logo_path' => null, 'logo_url' => null]);
        return back()->with('success', 'Logo removed.');
    }

    // ── createApiKey — admin only ─────────────────────────────────
    public function createApiKey(Request $request)
    {
        $this->requireAdmin();
        $request->validate(['name' => ['required', 'string', 'max:100']]);
        $token = auth()->user()->createToken($request->name);
        return back()
            ->with('new_token', $token->plainTextToken)
            ->with('success', 'API key created.');
    }

    // ── deleteApiKey — admin only ─────────────────────────────────
    public function deleteApiKey(int $id)
    {
        $this->requireAdmin();
        auth()->user()->tokens()->where('id', $id)->delete();
        return back()->with('success', 'API key revoked.');
    }

    // ── updateNotifications — all roles ───────────────────────────
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'quota_warning'   => ['boolean'],
            'quota_exceeded'  => ['boolean'],
            'payment_confirm' => ['boolean'],
            'team_invitation' => ['boolean'],
        ]);
        auth()->user()->update([
            'notification_preferences' => $request->only(
                'quota_warning', 'quota_exceeded', 'payment_confirm', 'team_invitation'
            ),
        ]);
        return back()->with('success', 'Notification preferences saved.');
    }
}