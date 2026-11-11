<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected int $rowCount = 0;
    protected array $importErrors = [];

    /**
     * Process imported rows.
     */
    public function collection(Collection $rows)
    {
        $line = 1; // Baris 1 adalah heading

        foreach ($rows as $row) {
            $line++;

            // Ubah row collection / array menjadi key yang ternormalisasi (lowercase, trim, strip underscores)
            $rowData = $this->normalizeRow($row);

            // Ekstrak nilai berdasarkan berbagai kemungkinan nama kolom
            $nama = $this->extractField($rowData, ['nama', 'name', 'nama_lengkap', 'nama_siswa', 'nama_user', 0]);
            $nisNip = $this->extractField($rowData, ['nis_nip', 'nis', 'nip', 'nisnip', 'nomor_induk', 'no_induk', 1]);
            $email = $this->extractField($rowData, ['email', 'e_mail', 'surel', 'email_address', 2]);
            $phone = $this->extractField($rowData, ['phone', 'no_hp', 'no_telp', 'telepon', 'whatsapp', 'no_wa', 3]);
            $roleRaw = $this->extractField($rowData, ['role', 'peran', 'jabatan', 'tipe', 4]);

            // Jika seluruh baris kosong, lewati
            if (empty($nama) && empty($nisNip) && empty($email) && empty($phone)) {
                continue;
            }

            // Validasi field wajib
            if (empty($nisNip)) {
                $this->importErrors[] = "Baris {$line}: Nomor NIS/NIP tidak boleh kosong.";
                continue;
            }

            if (empty($nama)) {
                $this->importErrors[] = "Baris {$line}: Nama pengguna (NIS {$nisNip}) tidak boleh kosong.";
                continue;
            }

            // Normalisasi role
            $role = strtolower(trim((string) $roleRaw));
            if (!in_array($role, ['siswa', 'teknisi', 'kabeng', 'kaprog'])) {
                $role = 'siswa';
            }

            // Normalisasi nomor telepon
            if (!empty($phone)) {
                // Bersihkan karakter non-digit selain '+' di depan
                $phone = preg_replace('/[^0-9+]/', '', (string) $phone);
                // Jika diawali 62, ubah atau biarkan format nasional
                if (str_starts_with($phone, '+62')) {
                    $phone = '0' . substr($phone, 3);
                } elseif (str_starts_with($phone, '62') && strlen($phone) > 9) {
                    $phone = '0' . substr($phone, 2);
                }
            } else {
                $phone = null;
            }

            // Normalisasi email
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Jangan gagalkan seluruh import, cukup set null atau catat peringatan jika email salah
                $email = null;
            }

            // Simpan / update user
            $user = User::updateOrCreate(
                ['nis_nip' => $nisNip],
                [
                    'name'     => $nama,
                    'email'    => !empty($email) ? $email : null,
                    'phone'    => !empty($phone) ? $phone : null,
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
     * Normalisasi array baris agar key tidak sensitif terhadap spasi, huruf besar, tanda hubung.
     */
    protected function normalizeRow($row): array
    {
        $normalized = [];
        $index = 0;

        foreach ($row as $key => $value) {
            $val = is_string($value) ? trim($value) : $value;
            
            // Simpan juga by numeric index
            $normalized[$index] = $val;

            // Simpan by clean string key
            $cleanKey = strtolower(trim(str_replace([' ', '-', '/', '.'], '_', (string) $key)));
            $normalized[$cleanKey] = $val;

            $index++;
        }

        return $normalized;
    }

    /**
     * Ekstrak field dengan toleransi beberapa alias nama kolom.
     */
    protected function extractField(array $rowData, array $aliases)
    {
        foreach ($aliases as $alias) {
            if (isset($rowData[$alias]) && $rowData[$alias] !== null && $rowData[$alias] !== '') {
                return is_string($rowData[$alias]) ? trim($rowData[$alias]) : (string) $rowData[$alias];
            }
        }
        return null;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }
}
