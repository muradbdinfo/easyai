<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

class BillingService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private NotificationService $notificationService,
    ) {}

    public function activatePlan(Tenant $tenant, Plan $plan, Payment $payment): void
    {
        // a) Update tenant
        $tenant->update([
            'plan_id'     => $plan->id,
            'token_quota' => $plan->monthly_token_limit,
            'status'      => 'active',
        ]);

        // b) Cancel old active subscriptions
        Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        // c) Create new subscription
        Subscription::create([
            'tenant_id'  => $tenant->id,
            'plan_id'    => $plan->id,
            'payment_id' => $payment->id,
            'starts_at'  => now(),
            'ends_at'    => now()->addMonth(),
            'status'     => 'active',
        ]);

        // d) Generate invoice
     $invoiceNumber = $payment->generateInvoiceNumber();
$payment->update(['invoice_number' => $invoiceNumber]);
$invoicePath = $this->invoiceService->generate($payment->fresh());

        // e) Mark payment completed
     $payment->update([
    'status'       => 'completed',
    'invoice_path' => $invoicePath,
]);

        // f) Notify tenant users (DB + email)
        try {
            $this->notificationService->paymentApproved(
                $tenant,
                $plan->name,
                $payment->amount,
                $payment->fresh(),
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('BillingService notification failed: ' . $e->getMessage());
        }
    }
}