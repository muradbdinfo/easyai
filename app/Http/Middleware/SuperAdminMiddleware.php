<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Superadmin access required.');
        }

        // Reads from config/domains.php → env('ADMIN_DOMAIN')
        if ($request->getHost() !== config('domains.admin')) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}