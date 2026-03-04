<?php

namespace Database\Seeders;

use App\Models\PromptTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tenantA = Tenant::where('slug', 'tenant-a')->first();
        if (!$tenantA) return;

        $admin = $tenantA->users->where('role', 'admin')->first()
              ?? $tenantA->users->first();

        if (!$admin) return;

        $templates = [
            [
                'name'      => 'Laravel Expert',
                'content'   => 'You are a senior Laravel developer with 10 years of experience.',
                'is_shared' => true,
            ],
            [
                'name'      => 'Code Reviewer',
                'content'   => 'Review this code for bugs, security issues, and performance.',
                'is_shared' => true,
            ],
            [
                'name'      => 'SQL Helper',
                'content'   => 'Help me write optimized SQL queries.',
                'is_shared' => false,
            ],
        ];

        foreach ($templates as $tmpl) {
            PromptTemplate::updateOrCreate(
                [
                    'tenant_id' => $tenantA->id,
                    'name'      => $tmpl['name'],
                ],
                array_merge($tmpl, [
                    'tenant_id' => $tenantA->id,
                    'user_id'   => $admin->id,
                ])
            );
        }

        $this->command->info('Prompt templates seeded for TenantA.');
    }
}