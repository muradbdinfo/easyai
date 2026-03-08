<?php
// FILE: database/seeders/SuperAdminSeeder.php
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
                'name'      => 'Super Admin',
                'password'  => bcrypt('P@ssword'), // ← fixed: was plain text
                'role'      => 'superadmin',
                'tenant_id' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('SuperAdmin seeded: admin@murad.bd / P@ssword');
    }
}