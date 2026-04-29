<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->enum('source_type', ['file', 'url', 'github'])->default('file')->after('tenant_id');
            $table->string('source_url', 2048)->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_url']);
        });
    }
};
