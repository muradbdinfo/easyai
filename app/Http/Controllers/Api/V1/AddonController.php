<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Payment;
use App\Services\AddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function __construct(private AddonService $addonService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $this->addonService->listForTenant($tenant),
        ]);
    }

    public function purchase(Request $request, Addon $addon): JsonResponse
    {
        $tenant = app('tenant');

        abort_if(!$addon->is_active, 404);

        if ($tenant->hasAddon($addon->slug)) {
            return response()->json([
                'success' => false,
                'message' => 'Add-on already active.',
            ], 422);
        }

        $validated = $request->validate([
            'method' => ['required', 'in:cod,sslcommerz,stripe'],
        ]);

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

        return response()->json([
            'success' => true,
            'message' => 'Purchase request submitted. Awaiting approval.',
            'data'    => [
                'payment_id' => $payment->id,
                'status'     => 'pending',
                'method'     => $validated['method'],
            ],
        ], 201);
    }

    public function cancel(Request $request, Addon $addon): JsonResponse
    {
        $tenant = app('tenant');

        $this->addonService->cancel($tenant, $addon);

        return response()->json([
            'success' => true,
            'message' => 'Add-on cancelled.',
            'data'    => null,
        ]);
    }
}