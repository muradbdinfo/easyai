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
Schema::create('chat_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
    $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('original_name');
    $table->string('stored_name');
    $table->string('mime_type');
    $table->string('extension', 10);
    $table->enum('type', ['image', 'text', 'pdf', 'excel']);
    $table->string('path');          // storage relative path
    $table->string('url')->nullable(); // public URL for images
    $table->longText('extracted_text')->nullable(); // for txt/pdf/xls
    $table->unsignedBigInteger('file_size'); // bytes
    $table->json('meta')->nullable(); // page_count, sheet_names, dimensions etc.
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_attachments');
    }
};
