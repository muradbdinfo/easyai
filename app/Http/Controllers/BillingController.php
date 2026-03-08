<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class BillingController extends Controller
{
    public function __construct(
        private BillingService $billingService,
        private NotificationService $notificationService,
    ) {}

    // ── Index ─────────────────────────────────────────────────────
    public function index()
    {
        $tenant   = app('tenant');
        $payments = Payment::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->paginate(10);

        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        return Inertia::render('Billing/Index', [
            'payments'     => $payments,
            'subscription' => $subscription,
            'current_plan' => $tenant->plan,
        ]);
    }

    // ── Plans list ────────────────────────────────────────────────
    public function plans()
    {
        $tenant = app('tenant');
        $plans  = Plan::where('is_active', true)->orderBy('price')->get();

        return Inertia::render('Billing/Plans', [
            'plans'        => $plans,
            'current_plan' => $tenant->plan,
        ]);
    }

    // ── Select payment method ─────────────────────────────────────
    public function selectPlan(Plan $plan)
    {
        return Inertia::render('Billing/SelectPayment', [
            'plan'        => $plan,
            'cod_enabled' => config('billing.cod_enabled', true),
        ]);
    }

    // ── COD ───────────────────────────────────────────────────────
    public function processCod(Request $request, Plan $plan)
    {
        $tenant = app('tenant');

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'user_id'   => $request->user()->id,
            'plan_id'   => $plan->id,
            'method'    => 'cod',
            'amount'    => $plan->price,
            'currency'  => 'BDT',
            'status'    => 'pending',
        ]);

        // Notify admin (DB + email)
        try {
            $this->notificationService->adminPaymentReceived($payment->load(['tenant', 'plan']));
        } catch (\Throwable $e) {
            Log::warning('COD admin notification failed: ' . $e->getMessage());
        }

        return redirect()->route('billing.index')
            ->with('success', 'COD request submitted. Admin will approve within 24 hours.');
    }

    // ── SSLCommerz: initiate ──────────────────────────────────────
    public function processSslcommerz(Request $request, Plan $plan)
    {
        $tenant = app('tenant');

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'user_id'   => $request->user()->id,
            'plan_id'   => $plan->id,
            'method'    => 'sslcommerz',
            'amount'    => $plan->price,
            'currency'  => 'BDT',
            'status'    => 'pending',
        ]);

        $postData = [
            'store_id'         => config('sslcommerz.store_id'),
            'store_passwd'     => config('sslcommerz.store_pass'),
            'total_amount'     => $plan->price,
            'currency'         => 'BDT',
            'tran_id'          => 'EASYAI-' . $payment->id . '-' . time(),
            'success_url'      => route('billing.sslcommerz.success'),
            'fail_url'         => route('billing.sslcommerz.fail'),
            'cancel_url'       => route('billing.sslcommerz.cancel'),
            'ipn_url'          => route('billing.sslcommerz.ipn'),
            'cus_name'         => $tenant->name,
            'cus_email'        => $request->user()->email,
            'cus_phone'        => '01700000000',
            'cus_add1'         => 'Dhaka',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'shipping_method'  => 'NO',
            'product_name'     => 'EasyAI ' . $plan->name . ' Plan',
            'product_category' => 'Software',
            'product_profile'  => 'non-physical-goods',
            'value_a'          => $payment->id,
        ];

        $apiUrl = config('sslcommerz.apiDomain') . config('sslcommerz.apiUrl');

        try {
            $response = Http::asForm()->post($apiUrl, $postData);
            $data     = $response->json();

            if (isset($data['GatewayPageURL']) && $data['status'] === 'SUCCESS') {
                $payment->update(['transaction_id' => $postData['tran_id']]);
                return redirect($data['GatewayPageURL']);
            }

            Log::error('SSLCommerz init failed', $data);
            return back()->withErrors(['error' => 'Payment gateway error. Please try again.']);

        } catch (\Throwable $e) {
            Log::error('SSLCommerz exception: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not connect to payment gateway.']);
        }
    }

    // ── SSLCommerz: success callback ──────────────────────────────
    public function sslSuccess(Request $request)
    {
        $paymentId = $request->input('value_a');
        $tranId    = $request->input('tran_id');
        $valId     = $request->input('val_id');

        $payment = Payment::find($paymentId);

        if (!$payment || $payment->status === 'completed') {
            return redirect()->route('billing.index');
        }

        $validationUrl = config('sslcommerz.apiDomain')
            . config('sslcommerz.validationUrl')
            . '?val_id=' . $valId
            . '&store_id=' . config('sslcommerz.store_id')
            . '&store_passwd=' . config('sslcommerz.store_pass')
            . '&format=json';

        try {
            $response = Http::get($validationUrl);
            $data     = $response->json();

            if (isset($data['status']) && $data['status'] === 'VALID') {
                $payment->update([
                    'transaction_id'   => $tranId,
                    'gateway_response' => $data,
                ]);

                $this->billingService->activatePlan(
                    $payment->tenant,
                    $payment->plan,
                    $payment
                );

                // Notify admin (DB + email)
                try {
                    $this->notificationService->adminPaymentReceived($payment->fresh()->load(['tenant', 'plan']));
                } catch (\Throwable $e) {
                    Log::warning('SSL admin notification failed: ' . $e->getMessage());
                }

                return redirect()->route('billing.index')
                    ->with('success', 'Payment successful! Plan activated.');
            }

        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation error: ' . $e->getMessage());
        }

        $payment->update(['status' => 'failed']);
        return redirect()->route('billing.index')
            ->withErrors(['error' => 'Payment validation failed.']);
    }

    // ── SSLCommerz: fail / cancel ─────────────────────────────────
    public function sslFail(Request $request)
    {
        if ($id = $request->input('value_a')) {
            Payment::find($id)?->update(['status' => 'failed']);
        }
        return redirect()->route('billing.index')
            ->withErrors(['error' => 'Payment failed. Please try again.']);
    }

    public function sslCancel(Request $request)
    {
        if ($id = $request->input('value_a')) {
            Payment::find($id)?->update(['status' => 'failed']);
        }
        return redirect()->route('billing.index')
            ->withErrors(['error' => 'Payment cancelled.']);
    }

    // ── SSLCommerz IPN ────────────────────────────────────────────
    public function sslIpn(Request $request)
    {
        Log::info('SSLCommerz IPN received', $request->all());
        return response('IPN received', 200);
    }

    // ── Stripe: initiate ──────────────────────────────────────────
    public function processStripe(Request $request, Plan $plan)
    {
        $tenant = app('tenant');

        Stripe::setApiKey(config('cashier.secret'));

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'user_id'   => $request->user()->id,
            'plan_id'   => $plan->id,
            'method'    => 'stripe',
            'amount'    => $plan->price,
            'currency'  => 'USD',
            'status'    => 'pending',
        ]);

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => ['name' => 'EasyAI ' . $plan->name . ' Plan'],
                        'unit_amount'  => (int)($plan->price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'                => 'payment',
                'success_url'         => route('billing.stripe.success')
                                        . '?session_id={CHECKOUT_SESSION_ID}'
                                        . '&payment_id=' . $payment->id,
                'cancel_url'          => route('billing.plans'),
                'client_reference_id' => $payment->id,
                'metadata'            => [
                    'payment_id' => $payment->id,
                    'tenant_id'  => $tenant->id,
                    'plan_id'    => $plan->id,
                ],
            ]);

            $payment->update(['transaction_id' => $session->id]);

            return redirect($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe error: ' . $e->getMessage());
            $payment->update(['status' => 'failed']);
            return back()->withErrors(['error' => 'Stripe error: ' . $e->getMessage()]);
        }
    }

    // ── Stripe: success ───────────────────────────────────────────
    public function stripeSuccess(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $sessionId = $request->input('session_id');

        $payment = Payment::find($paymentId);

        if (!$payment || $payment->status === 'completed') {
            return redirect()->route('billing.index');
        }

        try {
            Stripe::setApiKey(config('cashier.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $payment->update(['gateway_response' => (array) $session]);

                $this->billingService->activatePlan(
                    $payment->tenant,
                    $payment->plan,
                    $payment
                );

                // Notify admin (DB + email)
                try {
                    $this->notificationService->adminPaymentReceived($payment->fresh()->load(['tenant', 'plan']));
                } catch (\Throwable $e) {
                    Log::warning('Stripe admin notification failed: ' . $e->getMessage());
                }

                return redirect()->route('billing.index')
                    ->with('success', 'Payment successful! Plan activated.');
            }

        } catch (\Throwable $e) {
            Log::error('Stripe success error: ' . $e->getMessage());
        }

        $payment->update(['status' => 'failed']);
        return redirect()->route('billing.index')
            ->withErrors(['error' => 'Payment verification failed.']);
    }

    // ── Stripe: webhook ───────────────────────────────────────────
    public function stripeWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            Stripe::setApiKey(config('cashier.secret'));
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('cashier.webhook.secret')
            );
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response('Webhook error', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session   = $event->data->object;
            $paymentId = $session->metadata->payment_id ?? null;

            if ($paymentId) {
                $payment = Payment::find($paymentId);
                if ($payment && $payment->isPending()) {
                    $this->billingService->activatePlan(
                        $payment->tenant,
                        $payment->plan,
                        $payment
                    );
                    // Admin notification via webhook (email only, safe)
                    try {
                        $this->notificationService->adminPaymentReceived($payment->fresh()->load(['tenant', 'plan']));
                    } catch (\Throwable) {}
                }
            }
        }

        return response('OK', 200);
    }

    // ── Download invoice ──────────────────────────────────────────
    public function downloadInvoice(Payment $payment)
    {
        $tenant = app('tenant');
        abort_if($payment->tenant_id !== $tenant->id, 403);
        abort_if(!$payment->invoice_path, 404);

        $path = storage_path('app/' . $payment->invoice_path);
        abort_if(!file_exists($path), 404);

        return response()->download($path, $payment->invoice_number . '.pdf');
    }
}