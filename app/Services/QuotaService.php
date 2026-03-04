<?php
namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class QuotaService
{
    public function check(Tenant $tenant): bool
    {
        if ($tenant->token_quota <= 0) return true;
        return $tenant->tokens_used < $tenant->token_quota;
    }

    public function isExceeded(Tenant $tenant): bool
    {
        return !$this->check($tenant);
    }

    public function remaining(Tenant $tenant): int
    {
        return max(0, $tenant->token_quota - $tenant->tokens_used);
    }

    public function percentUsed(Tenant $tenant): float
    {
        if ($tenant->token_quota <= 0) return 0.0;
        return round(($tenant->tokens_used / $tenant->token_quota) * 100, 2);
    }

    public function deduct(Tenant $tenant, int $tokens): void
    {
        if ($tokens <= 0) return;

        $wasBelow80 = $this->percentUsed($tenant) < 80;
        $wasBelow100 = $this->check($tenant);

        $tenant->increment('tokens_used', $tokens);
        $tenant->refresh();

        $pct = $this->percentUsed($tenant);

        // Trigger 80% warning notification
        if ($wasBelow80 && $pct >= 80 && $pct < 100) {
            try {
                $svc  = new NotificationService();
                $svc->quotaWarning($tenant);
            } catch (\Throwable) {}
        }

        // Trigger exceeded notification
        if ($wasBelow100 && !$this->check($tenant)) {
            try {
                $svc = new NotificationService();
                $svc->quotaExceeded($tenant);
            } catch (\Throwable) {}
        }
    }
}