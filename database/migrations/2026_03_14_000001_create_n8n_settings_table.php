<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n8n_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Webhook URL — tenant's own n8n or null to use platform default from .env
            $table->string('webhook_url')->nullable();

            // HMAC secret used to sign outbound payloads + verify callbacks
            $table->string('callback_secret', 128)->nullable();

            // Toggle which events fire outbound webhooks
            $table->boolean('event_message_sent')->default(true);
            $table->boolean('event_assistant_replied')->default(true);
            $table->boolean('event_payment_completed')->default(false);
            $table->boolean('event_tenant_registered')->default(false);

            // Master on/off switch
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_settings');
    }
};
