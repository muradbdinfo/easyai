<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('seats')->default(1)->after('payment_id');
            $table->unsignedBigInteger('token_quota')->default(0)->after('seats');
        });
    }
    public function down(): void {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['seats','token_quota']);
        });
    }
};
