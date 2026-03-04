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
       Schema::table('messages', function (Blueprint $table) {
    $table->foreignId('attachment_id')
          ->nullable()
          ->after('model')
          ->constrained('chat_attachments')
          ->nullOnDelete();
    $table->boolean('has_attachment')->default(false)->after('attachment_id');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
};
