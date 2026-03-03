<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\UsageLog;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants'  => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'trial_tenants'  => Tenant::where('status', 'trial')->count(),
            'tokens_today'   => UsageLog::whereDate('created_at', today())->sum('total_tokens'),
            'revenue_month'  => Schema::hasTable('payments')
                ? \DB::table('payments')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount')
                : 0,
        ];

        $recent_tenants = Tenant::with('plan')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'slug', 'status', 'plan_id', 'tokens_used', 'token_quota', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'stats'          => $stats,
            'recent_tenants' => $recent_tenants,
        ]);
    }
}