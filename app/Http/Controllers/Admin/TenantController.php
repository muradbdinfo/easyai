<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with('plan')->withCount('users');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->latest()->paginate(20);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['plan', 'users']);

        $usage_logs = UsageLog::where('tenant_id', $tenant->id)
            ->latest()
            ->take(20)
            ->get();

        $plans = Plan::where('is_active', true)->get(['id', 'name', 'price', 'monthly_token_limit']);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant'     => $tenant,
            'plans'      => $plans,
            'usage_logs' => $usage_logs,
        ]);
    }

    public function updatePlan(Request $request, Tenant $tenant)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        $tenant->update([
            'plan_id'     => $plan->id,
            'token_quota' => $plan->monthly_token_limit,
        ]);

        return back()->with('success', "Plan updated to {$plan->name}.");
    }

    public function updateStatus(Request $request, Tenant $tenant)
    {
        $request->validate([
            'status' => ['required', 'in:active,suspended,trial'],
        ]);

        $tenant->update(['status' => $request->status]);

        return back()->with('success', "Tenant status updated to {$request->status}.");
    }
}