<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class N8nSetting extends Model
{
    protected $table = 'n8n_settings';

    protected $fillable = [
        'tenant_id',
        'webhook_url',
        'callback_secret',
        'event_new_chat',
        'event_message_sent',
        'event_assistant_replied',
        'event_payment_completed',
        'event_tenant_registered',
        'is_enabled',
    ];

    protected $casts = [
        'event_new_chat'          => 'boolean',
        'event_message_sent'      => 'boolean',
        'event_assistant_replied' => 'boolean',
        'event_payment_completed' => 'boolean',
        'event_tenant_registered' => 'boolean',
        'is_enabled'              => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if a specific event is enabled.
     * Event key examples: message_sent, assistant_replied, payment_completed, tenant_registered
     */
    public function eventEnabled(string $eventKey): bool
    {
        $column = 'event_' . $eventKey;
        return $this->is_enabled && ($this->{$column} ?? false);
    }

    /**
     * Resolve effective webhook URL:
     * tenant's own URL if set, otherwise fall back to .env N8N_WEBHOOK_URL
     */
    public function resolvedWebhookUrl(): ?string
    {
        return $this->webhook_url ?: config('services.n8n.webhook_url');
    }

    /**
     * Resolve effective callback secret.
     */
    public function resolvedSecret(): ?string
    {
        return $this->callback_secret ?: config('services.n8n.callback_secret');
    }
}
