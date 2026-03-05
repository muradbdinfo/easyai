<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UsageController extends Controller
{
    public function index(Request $request)
    {
        $query = UsageLog::with(['tenant:id,name', 'user:id,name']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        $summary = [
            'total_tokens_today'   => (int) UsageLog::whereDate('created_at', today())->sum('total_tokens'),
            'total_tokens_month'   => (int) UsageLog::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_tokens'),
            'total_messages_month' => (int) UsageLog::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'top_tenants'          => UsageLog::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->selectRaw('tenant_id, SUM(total_tokens) as tokens')
                ->groupBy('tenant_id')
                ->orderByDesc('tokens')
                ->take(5)
                ->with('tenant:id,name')
                ->get(),
        ];

        // last 30 days platform-wide daily
        $rawDaily = UsageLog::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_tokens) as tokens'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $d       = now()->subDays($i)->toDateString();
            $daily[] = [
                'date'   => $d,
                'label'  => now()->subDays($i)->format('d M'),
                'short'  => now()->subDays($i)->format('d'),
                'tokens' => (int) ($rawDaily[$d]->tokens ?? 0),
            ];
        }

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Usage/Index', [
            'logs'    => $logs,
            'summary' => $summary,
            'tenants' => $tenants,
            'filters' => $request->only('tenant_id'),
            'daily'   => $daily,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = UsageLog::with(['tenant:id,name']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $logs = $query->latest()->get();
        $csv  = "Date,Tenant,Model,Prompt,Completion,Total\n";
        foreach ($logs as $l) {
            $csv .= implode(',', [
                $l->created_at->format('Y-m-d H:i'),
                $l->tenant?->name ?? '',
                $l->model,
                $l->prompt_tokens,
                $l->completion_tokens,
                $l->total_tokens,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="platform-usage-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}