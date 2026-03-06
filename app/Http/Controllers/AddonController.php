<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Payment;
use App\Services\AddonService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddonController extends Controller
{
    public function __construct(private AddonService $addonService) {}

    public function index()
    {
        $tenant = app('tenant');

        return Inertia::render('Addons/Index', [
            'addons'          => $this->addonService->listForTenant($tenant),
            'addon_required'  => session('addon_required'),
        ]);
    }

    public function purchase(Request $request, Addon $addon)
    {
        $tenant = app('tenant');

        abort_if(!$addon->is_active, 404);

        if ($tenant->hasAddon($addon->slug)) {
            return back()->with('info', 'You already have this add-on active.');
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cod,sslcommerz,stripe'],
        ]);

        $invoiceNumber = 'ADDON-' . date('Y') . '-' . str_pad(
            Payment::max('id') + 1, 6, '0', STR_PAD_LEFT
        );

        Payment::create([
            'tenant_id'      => $tenant->id,
            'user_id'        => $request->user()->id,
            'plan_id'        => null,
            'addon_id'       => $addon->id,
            'method'         => $validated['method'],
            'amount'         => $addon->price,
            'currency'       => $addon->currency,
            'status'         => 'pending',
            'invoice_number' => $invoiceNumber,
        ]);

        // All methods → pending admin approval (SSLCommerz/Stripe for addons can be wired later)
        return redirect()->route('addons.index')
            ->with('success', 'Purchase request submitted. Awaiting admin approval.');
    }

    public function cancel(Request $request, Addon $addon)
    {
        $tenant = app('tenant');

        $this->addonService->cancel($tenant, $addon);

        return back()->with('success', 'Add-on cancelled successfully.');
    }
}