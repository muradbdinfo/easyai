<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddonMiddleware
{
    /**
     * Usage in routes:  ->middleware('addon:agent-ai')
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $tenant = app('tenant');

        if (!$tenant) {
            abort(403, 'No tenant context.');
        }

        if (!$tenant->hasAddon($slug)) {
            // API request → JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'addon_required',
                    'data'    => [
                        'addon_slug' => $slug,
                        'upgrade_url' => route('addons.index'),
                    ],
                ], 403);
            }

            // Web request → redirect to addons page with message
            return redirect()->route('addons.index')
                ->with('addon_required', $slug);
        }

        return $next($request);
    }
}