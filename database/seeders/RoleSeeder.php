<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create default roles
        $roles = [
            'siswa' => 'User biasa — akses Kiosk layanan mandiri',
            'teknisi' => 'Teknisi laboratorium — bisa manage maintenance & repair',
            'kabeng' => 'Kepala Bengkel — monitoring dashboard, log gate, approve jadwal preventive',
            'kaprog' => 'Kepala Program — melihat semua laporan inventaris, keuangan M&R, rekapitulasi aset',
        ];

        foreach ($roles as $name => $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('✅ 4 Roles berhasil ditambahkan: siswa, teknisi, kabeng, kaprog');
    }
}
