<?php

namespace App\Http\Middleware;

use App\Services\QuotaService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $quota = null;

        if ($request->user() && $request->user()->tenant_id) {
            $tenant = $request->user()->tenant;

            if ($tenant) {
                $qs = new QuotaService();

                $quota = [
                    'used'       => $tenant->tokens_used,
                    'total'      => $tenant->token_quota,
                    'remaining'  => $qs->remaining($tenant),
                    'percent'    => $qs->percentUsed($tenant),
                    'exceeded'   => $qs->isExceeded($tenant),
                    'reset_date' => now()->startOfMonth()->addMonth()->toDateString(),
                ];
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                    'role'  => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'quota'         => $quota,
            'ollama_models' => config('ollama.available_models'),
        ]);
    }
}