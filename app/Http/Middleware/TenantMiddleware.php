<?php

// FILE: app/Http/Middleware/TenantMiddleware.php
// CHANGES: Added is_active check after tenant suspension check

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Superadmin bypasses tenant check
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // User must have a tenant
        if (!$user || !$user->tenant_id) {
            abort(403, 'No workspace assigned.');
        }

        // ── NEW: Check if user account is active ─────────────────
        if (!$user->is_active) {
            abort(403, 'Your account has been deactivated by the workspace admin.');
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            abort(403, 'Workspace not found.');
        }

        // Block suspended tenants
        if ($tenant->isSuspended()) {
            abort(403, 'Your workspace has been suspended. Please contact support.');
        }

        // Bind tenant to app container
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
