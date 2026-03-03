<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\PromptTemplate;
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
        $quota     = null;
        $projects  = [];
        $templates = [];

        if ($request->user() && $request->user()->tenant_id) {
            $tenant       = $request->user()->tenant;
            $quotaService = new QuotaService();

            if ($tenant) {
                // Quota
                $quota = [
                    'used'       => $tenant->tokens_used,
                    'total'      => $tenant->token_quota,
                    'remaining'  => $quotaService->remaining($tenant),
                    'percent'    => $quotaService->percentUsed($tenant),
                    'exceeded'   => $quotaService->isExceeded($tenant),
                    'reset_date' => now()->startOfMonth()->addMonth()->toDateString(),
                ];

                // Sidebar projects with recent chats
                $projects = Project::where('tenant_id', $tenant->id)
                    ->with(['chats' => function ($q) {
                        $q->orderBy('updated_at', 'desc')->take(5);
                    }])
                    ->orderBy('updated_at', 'desc')
                    ->take(10)
                    ->get(['id', 'name', 'model', 'updated_at']);

                // Prompt templates for chat input picker
                $templates = PromptTemplate::where('tenant_id', $tenant->id)
                    ->where(function ($q) use ($request) {
                        $q->where('user_id', $request->user()->id)
                          ->orWhere('is_shared', true);
                    })
                    ->orderBy('name')
                    ->get(['id', 'name', 'content']);
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
            'quota'            => $quota,
            'sidebar_projects' => $projects,
            'templates'        => $templates,
            'ollama_models'    => config('ollama.available_models'),
        ]);
    }
}