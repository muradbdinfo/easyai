<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip if columns already exist (safe re-run)
        if (Schema::hasColumn('messages', 'attachment_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('attachment_id')
                  ->nullable()
                  ->after('model')
                  ->constrained('chat_attachments')
                  ->nullOnDelete();
            $table->boolean('has_attachment')
                  ->default(false)
                  ->after('attachment_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['attachment_id']);
            $table->dropColumn(['attachment_id', 'has_attachment']);
        });
    }
};