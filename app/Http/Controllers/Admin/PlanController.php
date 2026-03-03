<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('tenants')->orderBy('price')->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:100', 'unique:plans,name'],
            'monthly_token_limit'  => ['required', 'integer', 'min:1000'],
            'price'                => ['required', 'numeric', 'min:0'],
            'features'             => ['nullable', 'array'],
        ]);

        Plan::create($validated);

        return back()->with('success', 'Plan created.');
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'monthly_token_limit' => ['required', 'integer', 'min:1000'],
            'price'               => ['required', 'numeric', 'min:0'],
            'features'            => ['nullable', 'array'],
            'is_active'           => ['boolean'],
        ]);

        $plan->update($validated);

        return back()->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete plan with active tenants.']);
        }

        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }
}