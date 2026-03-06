<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantAddon;

class AddonService
{
    /**
     * Check if a tenant has an active addon by slug.
     */
    public function hasAddon(Tenant $tenant, string $slug): bool
    {
        return TenantAddon::where('tenant_id', $tenant->id)
            ->whereHas('addon', fn($q) => $q->where('slug', $slug))
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Activate an addon for a tenant (called after payment approved).
     */
    public function activate(Tenant $tenant, Addon $addon, ?Payment $payment = null): TenantAddon
    {
        $expiresAt = $addon->billing_cycle === 'monthly'
            ? now()->addMonth()
            : ($addon->billing_cycle === 'yearly' ? now()->addYear() : null);

        $tenantAddon = TenantAddon::updateOrCreate(
            ['tenant_id' => $tenant->id, 'addon_id' => $addon->id],
            [
                'payment_id'  => $payment?->id,
                'status'      => 'active',
                'starts_at'   => now(),
                'expires_at'  => $expiresAt,
                'approved_at' => now(),
            ]
        );

        return $tenantAddon;
    }

    /**
     * Cancel an addon for a tenant.
     */
    public function cancel(Tenant $tenant, Addon $addon): void
    {
        TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    /**
     * Get all addons with purchase status for a tenant.
     */
    public function listForTenant(Tenant $tenant): array
    {
        $addons = Addon::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $tenantAddonMap = TenantAddon::where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('addon_id');

        return $addons->map(function (Addon $addon) use ($tenantAddonMap) {
            $ta = $tenantAddonMap->get($addon->id);
            return [
                'id'            => $addon->id,
                'name'          => $addon->name,
                'slug'          => $addon->slug,
                'description'   => $addon->description,
                'price'         => $addon->price,
                'currency'      => $addon->currency,
                'billing_cycle' => $addon->billing_cycle,
                'features'      => $addon->features ?? [],
                'purchased'     => $ta ? $ta->isActive() : false,
                'status'        => $ta?->status ?? 'not_purchased',
                'expires_at'    => $ta?->expires_at?->toDateString(),
            ];
        })->toArray();
    }
}