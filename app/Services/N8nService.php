<?php

namespace App\Services;

use App\Models\N8nSetting;
use App\Models\N8nWebhookLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    /**
     * Fire an outbound webhook to n8n for a given event.
     * Silently returns if tenant has no active n8n addon or event is disabled.
     *
     * @param  Tenant  $tenant
     * @param  string  $event   e.g. 'message_sent', 'assistant_replied'
     * @param  array   $payload
     */
    public function fire(Tenant $tenant, string $event, array $payload): void
    {
        // 1) Tenant must have active n8n-automation addon
        if (!$tenant->hasAddon('n8n-automation')) {
            return;
        }

        // 2) Load settings row for this tenant
        $settings = N8nSetting::where('tenant_id', $tenant->id)->first();

        if (!$settings) {
            return;
        }

        // 3) Event must be enabled
        if (!$settings->eventEnabled($event)) {
            return;
        }

        // 4) Resolve webhook URL
        $url = $settings->resolvedWebhookUrl();

        if (!$url) {
            return;
        }

        // 5) Build full payload with metadata
        $body = [
            'event'     => $event,
            'tenant_id' => $tenant->id,
            'tenant'    => $tenant->name,
            'timestamp' => now()->toIso8601String(),
            'data'      => $payload,
        ];

        // 6) HMAC signature header
        $secret    = $settings->resolvedSecret();
        $signature = $secret
            ? 'sha256=' . hash_hmac('sha256', json_encode($body), $secret)
            : null;

        // 7) Fire HTTP POST and measure duration
        $start = microtime(true);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-EasyAI-Event' => $event,
            ];

            if ($signature) {
                $headers['X-EasyAI-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $body);

            $durationMs  = (int) ((microtime(true) - $start) * 1000);
            $success     = $response->successful();
            $code        = $response->status();
            $responseBody = mb_substr($response->body(), 0, 2000);

        } catch (\Throwable $e) {
            $durationMs   = (int) ((microtime(true) - $start) * 1000);
            $success      = false;
            $code         = null;
            $responseBody = null;
            $errorMsg     = $e->getMessage();

            Log::warning("N8nService: outbound webhook failed for tenant {$tenant->id} event {$event}: {$e->getMessage()}");
        }

        // 8) Log the attempt
        N8nWebhookLog::create([
            'tenant_id'     => $tenant->id,
            'direction'     => 'outbound',
            'event'         => $event,
            'url'           => $url,
            'payload'       => $body,
            'response_code' => $code ?? null,
            'response_body' => $responseBody ?? null,
            'success'       => $success,
            'error_message' => $errorMsg ?? null,
            'duration_ms'   => $durationMs,
            'created_at'    => now(),
        ]);
    }

    /**
     * Verify an inbound callback from n8n using HMAC signature.
     *
     * @param  Tenant  $tenant
     * @param  string  $rawBody   raw request body string
     * @param  string  $signature value of X-N8n-Signature header
     */
    public function verifyCallback(Tenant $tenant, string $rawBody, string $signature): bool
    {
        $settings = N8nSetting::where('tenant_id', $tenant->id)->first();
        $secret   = $settings?->resolvedSecret();

        if (!$secret) {
            // No secret configured → accept if signature is also empty (open mode)
            return empty($signature);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Log an inbound callback.
     */
    public function logInbound(Tenant $tenant, string $event, array $payload, bool $success, ?string $error = null): void
    {
        N8nWebhookLog::create([
            'tenant_id'     => $tenant->id,
            'direction'     => 'inbound',
            'event'         => $event,
            'url'           => request()->fullUrl(),
            'payload'       => $payload,
            'response_code' => $success ? 200 : 422,
            'response_body' => null,
            'success'       => $success,
            'error_message' => $error,
            'duration_ms'   => null,
            'created_at'    => now(),
        ]);
    }

    /**
     * Get or create the settings row for a tenant.
     */
    public function settingsFor(Tenant $tenant): N8nSetting
    {
        return N8nSetting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'webhook_url'             => null,
                'callback_secret'         => null,
                'event_message_sent'      => true,
                'event_assistant_replied' => true,
                'event_payment_completed' => false,
                'event_tenant_registered' => false,
                'is_enabled'              => true,
            ]
        );
    }
}
