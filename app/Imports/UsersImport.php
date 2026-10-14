<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $rowCount = 0;

    /**
     * Process imported rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row['nama']) && empty($row['nis_nip'])) {
                continue;
            }

            $nisNip = trim((string) $row['nis_nip']);
            $role = strtolower(trim((string) ($row['role'] ?? 'siswa')));

            // Validasi role
            if (!in_array($role, ['siswa', 'teknisi', 'kabeng', 'kaprog'])) {
                $role = 'siswa';
            }

            $user = User::updateOrCreate(
                ['nis_nip' => $nisNip],
                [
                    'name'     => trim((string) $row['nama']),
                    'email'    => !empty($row['email']) ? trim((string) $row['email']) : null,
                    'phone'    => !empty($row['phone']) ? trim((string) $row['phone']) : null,
                    'role'     => $role,
                    'password' => bcrypt($nisNip), // default password = NIS/NIP
                ]
            );

            // Sync Spatie Role
            $spatieRole = Role::where('name', $role)->first();
            if ($spatieRole) {
                $user->syncRoles([$spatieRole]);
            }

            $this->rowCount++;
        }
    }

    /**
     * Validasi per-baris (heading row).
     */
    public function rules(): array
    {
        return [
            'nama'    => 'required|string|max:100',
            'nis_nip' => 'required|string|max:30',
            'email'   => 'nullable|email|max:100',
            'phone'   => 'nullable|string|max:20',
            'role'    => 'nullable|string|in:siswa,teknisi,kabeng,kaprog',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required'    => 'Kolom "nama" wajib diisi.',
            'nis_nip.required' => 'Kolom "nis_nip" wajib diisi.',
            'email.email'      => 'Format email tidak valid.',
            'role.in'          => 'Role harus: siswa, teknisi, kabeng, atau kaprog.',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}
