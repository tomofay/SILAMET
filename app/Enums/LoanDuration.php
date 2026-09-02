<?php

namespace App\Enums;

enum LoanDuration: string
{
    case PULANG_SEKOLAH = 'pulang_sekolah';
    case SATU_HARI = '1_hari';
    case DUA_HARI = '2_hari';
    case TIGA_HARI = '3_hari';
    case SATU_MINGGU = '1_minggu';

    public function label(): string
    {
        return match ($this) {
            self::PULANG_SEKOLAH => 'Pulang Sekolah',
            self::SATU_HARI => '1 Hari',
            self::DUA_HARI => '2 Hari',
            self::TIGA_HARI => '3 Hari',
            self::SATU_MINGGU => '1 Minggu',
        };
    }

    /**
     * Jumlah hari tambahan pemakaian. 0 = harus kembali hari yang sama.
     */
    public function days(): int
    {
        return match ($this) {
            self::PULANG_SEKOLAH => 0,
            self::SATU_HARI => 1,
            self::DUA_HARI => 2,
            self::TIGA_HARI => 3,
            self::SATU_MINGGU => 7,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
