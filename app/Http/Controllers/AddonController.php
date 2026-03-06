<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Payment;
use App\Services\AddonService;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddonController extends Controller
{
    public function __construct(
        private AddonService $addonService
    ) {}

    public function index()
    {
        $tenant = app('tenant');

        return Inertia::render('Addons/Index', [
            'addons'        => $this->addonService->listForTenant($tenant),
            'addon_required' => session('addon_required'),
        ]);
    }

    public function purchase(Request $request, Addon $addon)
    {
        $tenant = app('tenant');

        abort_if(!$addon->is_active, 404);

        // Already active
        if ($tenant->hasAddon($addon->slug)) {
            return back()->with('info', 'You already have this add-on active.');
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cod,sslcommerz,stripe'],
        ]);

        // Create a pending payment
        $payment = Payment::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => $request->user()->id,
            'plan_id'        => null,
            'addon_id'       => $addon->id,
            'method'         => $validated['method'],
            'amount'         => $addon->price,
            'currency'       => $addon->currency,
            'status'         => 'pending',
            'invoice_number' => 'ADDON-' . date('Y') . '-' . str_pad(rand(1, 99999), 6, '0', STR_PAD_LEFT),
        ]);

        // COD → pending admin approval
        if ($validated['method'] === 'cod') {
            return redirect()->route('addons.index')
                ->with('success', 'Add-on purchase request submitted. Awaiting admin approval.');
        }

        // SSLCommerz / Stripe → redirect to billing flow
        return redirect()->route('billing.addon.pay', ['payment' => $payment->id]);
    }

    public function cancel(Request $request, Addon $addon)
    {
        $tenant = app('tenant');

        $this->addonService->cancel($tenant, $addon);

        return back()->with('success', 'Add-on cancelled successfully.');
    }
}