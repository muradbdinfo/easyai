<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed')->after('tokens');
            $table->text('error_message')->nullable()->after('status');
            $table->unsignedTinyInteger('retry_count')->default(0)->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message', 'retry_count']);
        });
    }
};
