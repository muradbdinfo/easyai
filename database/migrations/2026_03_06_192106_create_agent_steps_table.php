<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->integer('step_number');
            $table->text('thought')->nullable();
            $table->string('tool_name')->nullable();
            $table->json('tool_input')->nullable();
            $table->longText('tool_output')->nullable();
            $table->enum('status', ['thinking', 'tool_call', 'tool_result', 'final_answer', 'error'])->default('thinking');
            $table->integer('tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_steps');
    }
};