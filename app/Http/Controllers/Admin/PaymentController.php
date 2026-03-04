<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billingService) {}

    public function index(Request $request)
    {
        if (!Schema::hasTable('payments')) {
            return Inertia::render('Admin/Payments/Index', [
                'payments' => [],
                'filters'  => [],
            ]);
        }

        $query = Payment::with(['tenant', 'plan', 'user'])
            ->latest();

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $query->paginate(20),
            'filters'  => $request->only(['method', 'status']),
        ]);
    }

    public function approveCod(Request $request, $id)
    {
        $payment = Payment::with(['tenant', 'plan'])->findOrFail($id);

        abort_if($payment->method !== 'cod', 422, 'Not a COD payment.');
        abort_if(!$payment->isPending(), 422, 'Payment already processed.');

        $payment->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->billingService->activatePlan(
            $payment->tenant,
            $payment->plan,
            $payment
        );

        return back()->with('success', 'COD payment approved. Plan activated.');
    }
}