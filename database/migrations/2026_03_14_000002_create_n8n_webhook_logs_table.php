<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n8n_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Direction: outbound = EasyAI → n8n | inbound = n8n → EasyAI
            $table->enum('direction', ['outbound', 'inbound']);

            // Event name e.g. message.sent, assistant.replied, callback.received
            $table->string('event', 80);

            // The URL we called (outbound) or the route hit (inbound)
            $table->string('url')->nullable();

            // Payload sent or received (JSON)
            $table->json('payload')->nullable();

            // HTTP response code we got back (outbound) or sent (inbound)
            $table->unsignedSmallInteger('response_code')->nullable();

            // Response body (first 2000 chars)
            $table->text('response_body')->nullable();

            // null = pending, true = success, false = failed
            $table->boolean('success')->nullable();

            // Error message if failed
            $table->string('error_message')->nullable();

            // How long the HTTP call took in milliseconds
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_webhook_logs');
    }
};
