<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\PromptTemplate;
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
        $user   = $request->user();
        $tenant = null;
        $quota  = null;

        if ($user && $user->tenant_id) {
            try {
                $tenant = app('tenant');
            } catch (\Throwable) {
                $tenant = $user->tenant;
            }
        }

        if ($tenant) {
            $total     = $tenant->token_quota ?? 0;
            $used      = $tenant->tokens_used ?? 0;
            $remaining = max(0, $total - $used);
            $percent   = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            $quota = [
                'used'      => $used,
                'total'     => $total,
                'remaining' => $remaining,
                'percent'   => $percent,
                'exceeded'  => $used >= $total && $total > 0,
            ];
        }

        $sidebarProjects = $tenant
            ? Project::where('tenant_id', $tenant->id)
                ->with(['chats' => function ($q) {
                    $q->orderBy('updated_at', 'desc')->take(10);
                }])
                ->orderByRaw('is_default DESC')
                ->orderBy('created_at', 'asc')
                ->take(20)
                ->get()
                ->toArray()
            : [];

        $templates = [];
        if ($user && $tenant) {
            $templates = PromptTemplate::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('is_shared', true);
                })
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        $ollamaModels = array_filter(
            config('ollama.available_models', [config('ollama.model')])
        );

        // Active addon slugs for the current tenant
        $activeAddons = $tenant
            ? \App\Models\TenantAddon::where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->with('addon:id,slug')
                ->get()
                ->pluck('addon.slug')
                ->filter()
                ->values()
                ->toArray()
            : [];

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ] : null,
            ],
            'tenant'            => $tenant,
            'quota'             => $quota,
            'sidebar_projects'  => $sidebarProjects,
            'templates'         => $templates,
            'ollama_models'     => array_values($ollamaModels),
            'has_agent_addon'   => in_array('agent-ai', $activeAddons),
            'active_addons'     => $activeAddons,
            'openclaw_url'      => config('openclaw.url'),
            'openclaw_model'    => config('openclaw.model'),
            'theme'             => \App\Services\ThemeService::get(),
            'flash'             => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }
}