<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->enum('billing_type', ['flat', 'seat'])->default('flat')->after('is_active');
            $table->decimal('price_per_seat', 8, 2)->nullable()->after('billing_type');
            $table->unsignedInteger('min_seats')->default(1)->after('price_per_seat');
            $table->unsignedInteger('max_seats')->nullable()->after('min_seats');
            $table->unsignedBigInteger('token_limit_per_seat')->nullable()->after('max_seats');
        });
    }
    public function down(): void {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['billing_type','price_per_seat','min_seats','max_seats','token_limit_per_seat']);
        });
    }
};
