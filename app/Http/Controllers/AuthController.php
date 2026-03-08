<?php

// FILE: app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    // ─── Show Login ───────────────────────────────────────────────
    public function showLogin(Request $request): Response
    {
        if ($request->getHost() === config('domains.admin')) {
            return Inertia::render('Admin/Login');
        }

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

        if ($user->isSuperAdmin()) {
            return redirect()->intended('http://' . config('domains.admin'));
        }

        return redirect()->intended(route('dashboard'));
    }

    // ─── Show Register ────────────────────────────────────────────
    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    // ─── Register ─────────────────────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $plan = Plan::where('name', 'Starter')->firstOrFail();

        $tenant = Tenant::create([
            'name'          => $request->name . "'s Workspace",
            'slug'          => Str::slug($request->name . '-' . uniqid()),
            'plan_id'       => $plan->id,
            'token_quota'   => $plan->monthly_token_limit,
            'tokens_used'   => 0,
            'status'        => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // Default "General" project
        Project::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => $user->id,
            'name'        => 'General',
            'description' => 'Default workspace for quick chats.',
            'model'       => config('ollama.model', 'llama3'),
            'is_default'  => true,
        ]);

        // Fire Laravel's built-in email verification event
        event(new Registered($user));

        // Email notification to all superadmins
        User::where('role', 'superadmin')->each(
            fn($admin) => $admin->notify(new NewUserRegistered($user))
        );

        // In-app DB notification to all superadmins
        $this->notifications->newUserRegistered($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    // ─── Logout ───────────────────────────────────────────────────
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->getHost() === config('domains.admin')) {
            return redirect('http://' . config('domains.admin') . '/login');
        }

        return redirect()->route('login');
    }
}