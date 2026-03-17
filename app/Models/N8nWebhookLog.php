<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class N8nWebhookLog extends Model
{
    public $timestamps = false;

    protected $table = 'n8n_webhook_logs';

    protected $fillable = [
        'tenant_id',
        'direction',
        'event',
        'url',
        'payload',
        'response_code',
        'response_body',
        'success',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'success'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
