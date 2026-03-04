<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            SuperAdminSeeder::class,
            TenantSeeder::class,
            ProjectSeeder::class,
            PromptTemplateSeeder::class,
        ]);
    }
}