<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin — role kabeng (akses penuh dashboard & monitoring)
        $user = User::updateOrCreate(
            ['nis_nip' => 'ADM001'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'email' => 'admin@silamet.id',
                'role' => 'kabeng',
            ]
        );

        // Assign Spatie Role
        $user->assignRole('kabeng');

        $this->command->info('✅ Superadmin berhasil dibuat: admin@silamet.id / password (role: kabeng)');
    }
}
