<?php

// FILE: app/Http/Controllers/InvitationController.php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(private TeamService $teamService) {}

    // ─── show ─────────────────────────────────────────────────────
    // GET /invitation/{token}   (no auth required)
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)
            ->with(['tenant:id,name', 'inviter:id,name'])
            ->first();

        // Not found
        if (!$invitation) {
            return Inertia::render('Team/Accept', [
                'error' => 'This invitation link is invalid or has already been used.',
            ]);
        }

        // Already used
        if (in_array($invitation->status, ['accepted', 'declined'])) {
            return Inertia::render('Team/Accept', [
                'error' => 'This invitation has already been ' . $invitation->status . '.',
            ]);
        }

        // Expired
        if ($invitation->expires_at->isPast() || $invitation->status === 'expired') {
            $invitation->update(['status' => 'expired']);
            return Inertia::render('Team/Accept', [
                'error' => 'This invitation has expired. Please ask for a new one.',
            ]);
        }

        // Already a member of this tenant (logged in)
        if (auth()->check() && auth()->user()->tenant_id === $invitation->tenant_id) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already a member of this workspace.');
        }

        $existingUser = User::where('email', $invitation->email)->exists();

        return Inertia::render('Team/Accept', [
            'invitation' => [
                'token'      => $invitation->token,
                'email'      => $invitation->email,
                'role'       => $invitation->role,
                'expires_at' => $invitation->expires_at->toDateTimeString(),
            ],
            'tenant'       => ['name' => $invitation->tenant->name],
            'inviter'      => ['name' => $invitation->inviter->name],
            'existingUser' => $existingUser,
            'isLoggedIn'   => auth()->check(),
            'loggedInEmail'=> auth()->user()?->email,
        ]);
    }

    // ─── accept ───────────────────────────────────────────────────
    // POST /invitation/{token}/accept
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return back()->withErrors(['token' => 'Invalid or already used invitation.']);
        }

        if ($invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            return back()->withErrors(['token' => 'This invitation has expired.']);
        }

        // ── Case 1: Already logged in ─────────────────────────────
        if (auth()->check()) {
            $user = auth()->user();

            // Must be same email
            if (strtolower($user->email) !== strtolower($invitation->email)) {
                return back()->withErrors([
                    'email' => 'You are logged in as ' . $user->email .
                               '. Please log out and use the invited email address.',
                ]);
            }

            $this->teamService->acceptInvitation($invitation, []);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to ' . $invitation->tenant->name . '!');
        }

        // ── Case 2: Existing user not logged in ───────────────────
        $existingUser = User::where('email', $invitation->email)->first();
        if ($existingUser) {
            // Store token in session and redirect to login
            session(['pending_invitation' => $token]);
            return redirect()->route('login')
                ->with('info', 'Please log in to accept your team invitation.');
        }

        // ── Case 3: New user — register + accept ──────────────────
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $user = $this->teamService->acceptInvitation($invitation, $validated);
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Account created! Welcome to ' . $invitation->tenant->name . '!');
    }

    // ─── decline ──────────────────────────────────────────────────
    // POST /invitation/{token}/decline
    public function decline(string $token): RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)
            ->whereIn('status', ['pending'])
            ->first();

        if ($invitation) {
            $invitation->update(['status' => 'declined']);
        }

        return redirect()->route('login')
            ->with('info', 'Invitation declined.');
    }
}
