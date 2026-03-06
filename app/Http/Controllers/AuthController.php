<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    // ─── Show Login ───────────────────────────────────────────────
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    // ─── Login ────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Superadmin → admin panel
        if ($user->isSuperAdmin()) {
            return redirect()->intended('http://admin.easyai.local');
        }

        return redirect()->intended(route('dashboard'));
    }

    // ─── Show Register ────────────────────────────────────────────
    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
{
    $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $plan = \App\Models\Plan::where('name', 'Starter')->first();

    $tenant = \App\Models\Tenant::create([
        'name'          => $request->name . "'s Workspace",
        'slug'          => \Illuminate\Support\Str::slug($request->name . '-' . uniqid()),
        'plan_id'       => $plan->id,
        'token_quota'   => $plan->monthly_token_limit,
        'tokens_used'   => 0,
        'status'        => 'trial',
        'trial_ends_at' => now()->addDays(14),
    ]);

    $user = \App\Models\User::create([
        'name'      => $request->name,
        'email'     => $request->email,
        'password'  => $request->password,
        'role'      => 'admin',
        'tenant_id' => $tenant->id,
    ]);

    // Create default General project for standalone chats
    \App\Models\Project::create([
        'tenant_id'   => $tenant->id,
        'user_id'     => $user->id,
        'name'        => 'General',
        'description' => 'Default workspace for quick chats.',
        'model'       => config('ollama.model', 'llama3.2:latest'),
        'is_default'  => true,
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('dashboard');
}

    // ─── Logout ───────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}