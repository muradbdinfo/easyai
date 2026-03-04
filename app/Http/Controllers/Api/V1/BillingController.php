<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $plans,
        ]);
    }

    public function currentPlan(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $tenant->load('plan');

        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'tenant'       => $tenant,
                'plan'         => $tenant->plan,
                'subscription' => $subscription,
            ],
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $tenant   = app('tenant');
        $payments = Payment::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $payments->items(),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'total'        => $payments->total(),
                'per_page'     => $payments->perPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    public function downloadInvoice(Request $request, Payment $payment): \Illuminate\Http\Response
    {
        $tenant = app('tenant');
        abort_if($payment->tenant_id !== $tenant->id, 403);
        abort_if(!$payment->invoice_path, 404);

        $path = storage_path('app/' . $payment->invoice_path);
        abort_if(!file_exists($path), 404);

        return response()->download($path, $payment->invoice_number . '.pdf');
    }
}