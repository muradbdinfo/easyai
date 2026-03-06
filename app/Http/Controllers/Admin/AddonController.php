<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Payment;
use App\Models\TenantAddon;
use App\Services\AddonService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddonController extends Controller
{
    public function __construct(private AddonService $addonService) {}

    public function index()
    {
        $addons = Addon::withCount('tenantAddons as total_purchases')
            ->withCount(['tenantAddons as active_count' => fn($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();

        $pendingPayments = Payment::with(['tenant', 'addon'])
            ->whereNotNull('addon_id')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return Inertia::render('Admin/Addons/Index', [
            'addons'          => $addons,
            'pending_payments' => $pendingPayments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'slug'          => ['required', 'string', 'max:100', 'unique:addons,slug', 'regex:/^[a-z0-9\-]+$/'],
            'description'   => ['nullable', 'string', 'max:500'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:10'],
            'billing_cycle' => ['required', 'in:monthly,yearly,one_time'],
            'features'      => ['nullable', 'array'],
            'sort_order'    => ['nullable', 'integer'],
        ]);

        Addon::create($validated);

        return back()->with('success', 'Add-on created.');
    }

    public function update(Request $request, Addon $addon)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:10'],
            'billing_cycle' => ['required', 'in:monthly,yearly,one_time'],
            'features'      => ['nullable', 'array'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['nullable', 'integer'],
        ]);

        $addon->update($validated);

        return back()->with('success', 'Add-on updated.');
    }

    public function destroy(Addon $addon)
    {
        if ($addon->activeTenants()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete add-on with active subscribers.']);
        }

        $addon->delete();

        return back()->with('success', 'Add-on deleted.');
    }

    public function approvePurchase(Request $request, Payment $payment)
    {
        abort_if(!$payment->addon_id, 404);
        abort_if($payment->status !== 'pending', 422);

        $addon   = $payment->addon;
        $tenant  = $payment->tenant;

        $this->addonService->activate($tenant, $addon, $payment);

        $payment->update([
            'status'      => 'completed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update tenant_addons approved_by
        TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return back()->with('success', "Add-on \"{$addon->name}\" approved for {$tenant->name}.");
    }

    public function revoke(Request $request, TenantAddon $tenantAddon)
    {
        $tenantAddon->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Add-on access revoked.');
    }
}