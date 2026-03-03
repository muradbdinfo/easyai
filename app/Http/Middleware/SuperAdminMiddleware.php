<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Must be authenticated + superadmin role
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Superadmin access required.');
        }

        // Must be on admin domain
        $host = $request->getHost();
        if ($host !== 'admin.easyai.local') {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}