<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'addon_id')) {
                $table->foreignId('addon_id')
                      ->nullable()
                      ->after('plan_id')
                      ->constrained('addons')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Addon::class);
            $table->dropColumn('addon_id');
        });
    }
};