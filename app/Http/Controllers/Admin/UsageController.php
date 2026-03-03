<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageController extends Controller
{
    public function index(Request $request)
    {
        $query = UsageLog::with(['tenant', 'user']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $logs = $query->latest()->paginate(50);

        $summary = [
            'total_tokens_today'   => UsageLog::whereDate('created_at', today())->sum('total_tokens'),
            'total_tokens_month'   => UsageLog::whereMonth('created_at', now()->month)->sum('total_tokens'),
            'total_messages_month' => UsageLog::whereMonth('created_at', now()->month)->count(),
            'top_tenants'          => UsageLog::whereMonth('created_at', now()->month)
                ->selectRaw('tenant_id, sum(total_tokens) as tokens')
                ->groupBy('tenant_id')
                ->orderByDesc('tokens')
                ->take(5)
                ->with('tenant:id,name')
                ->get(),
        ];

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Usage/Index', [
            'logs'     => $logs,
            'summary'  => $summary,
            'tenants'  => $tenants,
            'filters'  => $request->only('tenant_id'),
        ]);
    }
}