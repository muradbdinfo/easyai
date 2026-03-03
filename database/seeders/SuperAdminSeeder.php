<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@easyai.local'],
            [
                'name'      => 'Super Admin',
                'password'  => 'password',
                'role'      => 'superadmin',
                'tenant_id' => null,
            ]
        );

        $this->command->info('SuperAdmin seeded: admin@easyai.local / password');
    }
}