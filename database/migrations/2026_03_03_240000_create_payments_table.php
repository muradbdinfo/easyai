<?php
// database/migrations/2026_03_03_240000_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->enum('method', ['cod', 'sslcommerz', 'stripe']);
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('BDT');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending');
            $table->string('transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('invoice_number')->unique()->nullable();
            $table->string('invoice_path')->nullable();
            $table->foreignId('approved_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};