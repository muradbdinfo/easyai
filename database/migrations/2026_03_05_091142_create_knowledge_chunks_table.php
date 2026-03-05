<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
        $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->text('content');
        $table->unsignedInteger('chunk_index')->default(0);
        $table->timestamps();
        $table->fullText('content');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
