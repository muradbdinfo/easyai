<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix agent_steps.status — change to varchar(50)
        DB::statement("ALTER TABLE `agent_steps` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'completed'");

        // Fix agent_runs.error_message — change to text
        DB::statement("ALTER TABLE `agent_runs` MODIFY `error_message` TEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `agent_steps` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'completed'");
        DB::statement("ALTER TABLE `agent_runs` MODIFY `error_message` VARCHAR(255) NULL");
    }
};