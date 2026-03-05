<?php

namespace App\Http\Controllers;

use App\Models\UsageLog;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UsageController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $tid    = $tenant->id;

        $logs = UsageLog::where('tenant_id', $tid)->latest()->take(50)->get();

        $rawDaily = UsageLog::where('tenant_id', $tid)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_tokens) as tokens'), DB::raw('COUNT(*) as messages'))
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $daily[] = [
                'date'     => $d,
                'label'    => now()->subDays($i)->format('d M'),
                'short'    => now()->subDays($i)->format('d'),
                'tokens'   => (int) ($rawDaily[$d]->tokens   ?? 0),
                'messages' => (int) ($rawDaily[$d]->messages ?? 0),
            ];
        }

        $models = UsageLog::where('tenant_id', $tid)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->select('model', DB::raw('SUM(total_tokens) as tokens'), DB::raw('COUNT(*) as calls'))
            ->groupBy('model')->orderByDesc('tokens')->get();

        $summary = [
            'total_tokens_month'   => (int) UsageLog::where('tenant_id', $tid)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_tokens'),
            'total_messages_month' => (int) UsageLog::where('tenant_id', $tid)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'total_tokens_today'   => (int) UsageLog::where('tenant_id', $tid)->whereDate('created_at', today())->sum('total_tokens'),
        ];

        return Inertia::render('Usage/Index', compact('logs', 'daily', 'models', 'summary'));
    }

    public function exportCsv()
    {
        $tenant = app('tenant');
        $logs   = UsageLog::where('tenant_id', $tenant->id)->latest()->get();
        $csv    = "Date,Model,Prompt,Completion,Total\n";
        foreach ($logs as $l) {
            $csv .= "{$l->created_at->format('Y-m-d H:i')},{$l->model},{$l->prompt_tokens},{$l->completion_tokens},{$l->total_tokens}\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="usage-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}