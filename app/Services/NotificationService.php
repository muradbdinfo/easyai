<?php

// FILE: app/Services/NotificationService.php

namespace App\Services;

use App\Mail\PaymentApprovedMail;
use App\Mail\PaymentSubmittedMail;
use App\Mail\TenantActivatedMail;
use App\Mail\TenantSuspendedMail;
use App\Models\AppNotification;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // ── Core DB notification ───────────────────────────────────────

    public function notify(
        ?Tenant $tenant,
        User $user,
        string $type,
        string $title,
        string $body = '',
        ?string $actionUrl = null,
        array $data = []
    ): void {
        AppNotification::create([
            'tenant_id'  => $tenant?->id,
            'user_id'    => $user->id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'data'       => $data ?: null,
        ]);
    }

    // ── Email helper ───────────────────────────────────────────────

    private function sendEmail(User $user, object $mailable): void
    {
        try {
            Mail::to($user->email, $user->name)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('NotificationService email failed: ' . $e->getMessage());
        }
    }

    // ── Admin: new user registered ─────────────────────────────────

    public function newUserRegistered(User $newUser): void
    {
        $superadmins = User::where('role', 'superadmin')->get();

        foreach ($superadmins as $admin) {
            $this->notify(
                null,
                $admin,
                'new_user_registered',
                'New user registered: ' . $newUser->name,
                $newUser->email . ' signed up and is on a 14-day Starter trial.',
                '/tenants',
            );
        }
    }

    // ── Tenant: activated by admin ─────────────────────────────────

    public function tenantActivated(Tenant $tenant): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant,
                $user,
                'tenant_activated',
                'Your workspace has been activated!',
                'Great news — your EasyAI workspace "' . $tenant->name . '" is now active. You can use all features on your current plan.',
                '/dashboard',
            );
            $this->sendEmail($user, new TenantActivatedMail($tenant));
        }
    }

    // ── Tenant: suspended by admin ─────────────────────────────────

    public function tenantSuspended(Tenant $tenant): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant,
                $user,
                'tenant_suspended',
                'Your workspace has been suspended',
                'Your EasyAI workspace "' . $tenant->name . '" has been suspended by the admin. Please contact support for assistance.',
                '/billing',
            );
            $this->sendEmail($user, new TenantSuspendedMail($tenant));
        }
    }

    // ── Admin: payment submitted ───────────────────────────────────

    public function adminPaymentReceived(Payment $payment): void
    {
        $superadmins = User::where('role', 'superadmin')->get();

        foreach ($superadmins as $admin) {
            $this->notify(
                null,
                $admin,
                'payment_received',
                'New payment: ' . $payment->tenant->name,
                strtoupper($payment->method) . ' — ' . $payment->currency . ' ' . number_format($payment->amount, 2) . ' for ' . $payment->plan->name . ' plan.',
                '/payments',
            );

            $this->sendEmail($admin, new PaymentSubmittedMail($payment));
        }
    }

    // ── User: payment approved / plan activated ────────────────────

    public function paymentApproved(Tenant $tenant, string $planName, float $amount, ?Payment $payment = null): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant,
                $user,
                'payment_approved',
                'Payment approved — ' . $planName . ' plan activated',
                'Your payment of ' . number_format($amount, 2) . ' has been approved. Your workspace is now on the ' . $planName . ' plan.',
                '/billing',
            );

            if ($payment) {
                $this->sendEmail($user, new PaymentApprovedMail($payment));
            }
        }
    }

    // ── Quota warnings ─────────────────────────────────────────────

    public function quotaWarning(Tenant $tenant): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $exists = AppNotification::where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('type', 'quota_warning')
                ->whereDate('created_at', today())
                ->exists();

            if (!$exists) {
                $this->notify(
                    $tenant, $user,
                    'quota_warning',
                    'Token quota at 80%',
                    'You have used 80% of your monthly token quota. Consider upgrading your plan.',
                    '/billing/plans',
                );
            }
        }
    }

    public function quotaExceeded(Tenant $tenant): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $exists = AppNotification::where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('type', 'quota_exceeded')
                ->whereDate('created_at', today())
                ->exists();

            if (!$exists) {
                $this->notify(
                    $tenant, $user,
                    'quota_exceeded',
                    'Token quota exceeded',
                    'Your monthly token quota is exhausted. Upgrade to continue using EasyAI.',
                    '/billing/plans',
                );
            }
        }
    }

    public function planChanged(Tenant $tenant, string $newPlan): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant, $user,
                'plan_changed',
                'Plan upgraded to ' . $newPlan,
                'Your workspace is now on the ' . $newPlan . ' plan. Enjoy your new token quota!',
                '/billing',
            );
        }
    }
}