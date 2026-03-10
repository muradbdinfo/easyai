<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integration_files')) return;

        Schema::create('integration_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->enum('source', ['github', 'drive'])->default('github');
            $table->string('external_id');
            $table->string('name');
            $table->string('path');
            $table->longText('content');
            $table->unsignedInteger('byte_size')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unique(['tenant_id', 'source', 'external_id', 'project_id'], 'integration_files_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_files');
    }
};