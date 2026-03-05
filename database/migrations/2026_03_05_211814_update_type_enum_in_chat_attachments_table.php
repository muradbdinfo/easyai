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
    DB::statement("ALTER TABLE chat_attachments MODIFY COLUMN type ENUM('image','text','pdf','excel','document')");
}

public function down(): void
{
    DB::statement("ALTER TABLE chat_attachments MODIFY COLUMN type ENUM('image','text','pdf','excel')");
}
};
