<?php

namespace App\Services;

use App\Models\Tenant;

class QuotaService
{
    /**
     * Check if tenant has tokens remaining.
     */
    public function check(Tenant $tenant): bool
    {
        // If no quota set (0 = unlimited)
        if ($tenant->token_quota <= 0) {
            return true;
        }

        return $tenant->tokens_used < $tenant->token_quota;
    }

    /**
     * Deduct tokens from tenant.
     */
    public function deduct(Tenant $tenant, int $tokens): void
    {
        $tenant->increment('tokens_used', $tokens);
    }

    /**
     * Get remaining tokens.
     */
    public function remaining(Tenant $tenant): int
    {
        if ($tenant->token_quota <= 0) {
            return PHP_INT_MAX;
        }

        return max(0, $tenant->token_quota - $tenant->tokens_used);
    }

    /**
     * Get percent used (0-100).
     */
    public function percentUsed(Tenant $tenant): float
    {
        if ($tenant->token_quota <= 0) {
            return 0.0;
        }

        return round(
            min(100, ($tenant->tokens_used / $tenant->token_quota) * 100),
            2
        );
    }

    /**
     * Check if quota is exceeded.
     */
    public function isExceeded(Tenant $tenant): bool
    {
        return !$this->check($tenant);
    }
}