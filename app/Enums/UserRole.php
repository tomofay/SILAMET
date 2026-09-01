<?php

namespace App\Enums;

enum UserRole: string
{
    case SISWA = 'siswa';
    case TEKNISI = 'teknisi';
    case KABENG = 'kabeng';
    case KAPROG = 'kaprog';

    public function label(): string
    {
        return match ($this) {
            self::SISWA => 'Siswa',
            self::TEKNISI => 'Teknisi',
            self::KABENG => 'Kepala Bengkel',
            self::KAPROG => 'Kepala Program',
        };
    }
}
