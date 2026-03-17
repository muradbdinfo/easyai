<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@murad.bd'],
            [
                'name'               => 'Super Admin',
                'password'           => bcrypt('P@ssword'),
                'role'               => 'superadmin',
                'tenant_id'          => null,
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]
        );

        $this->command->info('SuperAdmin seeded: admin@murad.bd / P@ssword');
    }
}