<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $starter    = Plan::where('name', 'Starter')->first();
        $pro        = Plan::where('name', 'Pro')->first();
        $enterprise = Plan::where('name', 'Enterprise')->first();

        // ── Tenant A — Starter, active ────────────────────────────
        $tenantA = Tenant::updateOrCreate(
            ['slug' => 'tenant-a'],
            [
                'name'        => 'Tenant A Corp',
                'plan_id'     => $starter->id,
                'token_quota' => $starter->monthly_token_limit,
                'tokens_used' => 0,
                'status'      => 'active',
            ]
        );

        $adminA = User::updateOrCreate(
            ['email' => 'admin@tenant-a.local'],
            [
                'name'      => 'Admin A',
                'password'  => bcrypt('password'),
                'role'      => 'admin',
                'tenant_id' => $tenantA->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member1@tenant-a.local'],
            [
                'name'      => 'Member A1',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantA->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member2@tenant-a.local'],
            [
                'name'      => 'Member A2',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantA->id,
            ]
        );

        // ── Tenant B — Pro, active ────────────────────────────────
        $tenantB = Tenant::updateOrCreate(
            ['slug' => 'tenant-b'],
            [
                'name'        => 'Tenant B Ltd',
                'plan_id'     => $pro->id,
                'token_quota' => $pro->monthly_token_limit,
                'tokens_used' => 0,
                'status'      => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@tenant-b.local'],
            [
                'name'      => 'Admin B',
                'password'  => bcrypt('password'),
                'role'      => 'admin',
                'tenant_id' => $tenantB->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member1@tenant-b.local'],
            [
                'name'      => 'Member B1',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantB->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member2@tenant-b.local'],
            [
                'name'      => 'Member B2',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantB->id,
            ]
        );

        // ── Tenant C — Enterprise, suspended ─────────────────────
        $tenantC = Tenant::updateOrCreate(
            ['slug' => 'tenant-c'],
            [
                'name'        => 'Tenant C Inc',
                'plan_id'     => $enterprise->id,
                'token_quota' => $enterprise->monthly_token_limit,
                'tokens_used' => 0,
                'status'      => 'suspended',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@tenant-c.local'],
            [
                'name'      => 'Admin C',
                'password'  => bcrypt('password'),
                'role'      => 'admin',
                'tenant_id' => $tenantC->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member1@tenant-c.local'],
            [
                'name'      => 'Member C1',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantC->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'member2@tenant-c.local'],
            [
                'name'      => 'Member C2',
                'password'  => bcrypt('password'),
                'role'      => 'member',
                'tenant_id' => $tenantC->id,
            ]
        );

        $this->command->info('Tenants seeded:');
        $this->command->info('  TenantA (Starter/active): admin@tenant-a.local / password');
        $this->command->info('  TenantB (Pro/active):     admin@tenant-b.local / password');
        $this->command->info('  TenantC (Enterprise/suspended): admin@tenant-c.local / password');
    }
}