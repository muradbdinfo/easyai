<?php

// FILE: app/Http/Controllers/SettingsController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    // ── index ──────────────────────────────────────────────────────
    public function index()
    {
        $user   = auth()->user();
        $tenant = app('tenant');

        $tokens = $user->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->latest()
            ->get();

        return Inertia::render('Settings/Index', [
            'user'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'notification_preferences' => $user->getNotificationPreferences(),
            ],
            'tenant' => [
                'id'            => $tenant->id,
                'name'          => $tenant->name,
                'slug'          => $tenant->slug,
                'logo_url'      => $tenant->logoUrl(),
                'default_model' => $tenant->default_model ?? 'llama3',
            ],
            'api_tokens'     => $tokens,
            'ollama_models'  => config('ollama.available_models', ['llama3', 'mistral', 'codellama']),
        ]);
    }

    // ── updateProfile ──────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated.');
    }

    // ── updatePassword ─────────────────────────────────────────────
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        auth()->user()->update(['password' => $request->password]);

        return back()->with('success', 'Password updated.');
    }

    // ── updateWorkspace ────────────────────────────────────────────
    public function updateWorkspace(Request $request)
    {
        $tenant = app('tenant');

        // Only admin can update workspace settings
        abort_if(!auth()->user()->canManageTeam(), 403);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'default_model' => ['required', 'string', 'max:100'],
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Workspace settings updated.');
    }

    // ── uploadLogo ─────────────────────────────────────────────────
    public function uploadLogo(Request $request)
    {
        abort_if(!auth()->user()->canManageTeam(), 403);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        $tenant = app('tenant');

        // Delete old logo
        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $tenant->update(['logo_path' => $path]);

        return back()->with('success', 'Logo updated.')->with('logo_url', asset('storage/' . $path));
    }

    // ── deleteLogo ─────────────────────────────────────────────────
    public function deleteLogo()
    {
        abort_if(!auth()->user()->canManageTeam(), 403);

        $tenant = app('tenant');

        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo removed.');
    }

    // ── createApiKey ───────────────────────────────────────────────
    public function createApiKey(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $token = auth()->user()->createToken($request->name);

        return back()
            ->with('success', 'API key created.')
            ->with('new_token', $token->plainTextToken);
    }

    // ── deleteApiKey ───────────────────────────────────────────────
    public function deleteApiKey(int $id)
    {
        auth()->user()->tokens()->where('id', $id)->delete();

        return back()->with('success', 'API key revoked.');
    }

    // ── updateNotifications ────────────────────────────────────────
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'quota_warning'   => ['boolean'],
            'quota_exceeded'  => ['boolean'],
            'payment_confirm' => ['boolean'],
            'team_invitation' => ['boolean'],
        ]);

        auth()->user()->update(['notification_preferences' => $validated]);

        return back()->with('success', 'Notification preferences saved.');
    }
}