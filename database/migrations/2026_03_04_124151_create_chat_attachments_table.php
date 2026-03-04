<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip if already exists (safe re-run)
        if (Schema::hasTable('chat_attachments')) {
            return;
        }

        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')
                  ->constrained('chats')
                  ->cascadeOnDelete();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            // Plain integer — no FK to messages (avoids circular dependency)
            // messages.attachment_id → chat_attachments  (set in migration 2)
            // chat_attachments.message_id → messages     (would be circular)
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->enum('type', ['image', 'text', 'pdf', 'excel']);
            $table->string('path');
            $table->string('url')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_attachments');
    }
};