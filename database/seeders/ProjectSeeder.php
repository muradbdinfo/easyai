<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::with('users')->whereIn('slug', [
            'tenant-a', 'tenant-b',
        ])->get();

        foreach ($tenants as $tenant) {
            $admin = $tenant->users->where('role', 'admin')->first();
            if (!$admin) continue;

            Project::updateOrCreate(
                ['name' => 'General AI', 'tenant_id' => $tenant->id],
                [
                    'tenant_id'     => $tenant->id,
                    'user_id'       => $admin->id,
                    'description'   => 'General purpose AI workspace',
                    'system_prompt' => 'You are a helpful assistant.',
                    'model'         => config('ollama.model', 'llama3'),
                ]
            );

            Project::updateOrCreate(
                ['name' => 'Code Assistant', 'tenant_id' => $tenant->id],
                [
                    'tenant_id'     => $tenant->id,
                    'user_id'       => $admin->id,
                    'description'   => 'Coding help and code review',
                    'system_prompt' => 'You are a senior software engineer.',
                    'model'         => config('ollama.model', 'llama3'),
                ]
            );
        }

        $this->command->info('Projects seeded for TenantA and TenantB.');
    }
}