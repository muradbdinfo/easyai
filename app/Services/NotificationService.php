<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Tenant;
use App\Models\User;

class NotificationService
{
    public function notify(Tenant $tenant, User $user, string $type, string $title, string $body = '', ?string $actionUrl = null, array $data = []): void
    {
        AppNotification::create([
            'tenant_id'  => $tenant->id,
            'user_id'    => $user->id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'data'       => $data ?: null,
        ]);
    }

    public function quotaWarning(Tenant $tenant): void
    {
        $admins = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'member'])
            ->get();

        foreach ($admins as $user) {
            // Avoid duplicate warnings in the same day
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
        $admins = User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'member'])
            ->get();

        foreach ($admins as $user) {
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

    public function paymentApproved(Tenant $tenant, string $planName, float $amount): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant, $user,
                'payment_approved',
                'Payment approved',
                "Your payment of {$amount} has been approved. Plan upgraded to {$planName}.",
                '/billing',
            );
        }
    }

    public function planChanged(Tenant $tenant, string $newPlan): void
    {
        $users = User::where('tenant_id', $tenant->id)->get();

        foreach ($users as $user) {
            $this->notify(
                $tenant, $user,
                'plan_changed',
                "Plan upgraded to {$newPlan}",
                "Your workspace is now on the {$newPlan} plan. Enjoy your new token quota!",
                '/billing',
            );
        }
    }
}