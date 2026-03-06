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
        // ── Tenants: logo + default_model ─────────────────────────
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->string('default_model')->default('llama3')->after('logo_path');
        });

        // ── Users: notification preferences ───────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'default_model']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
