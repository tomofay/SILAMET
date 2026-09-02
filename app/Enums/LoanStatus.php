<?php

namespace App\Enums;

enum LoanStatus: string
{
    case AKTIF = 'aktif';
    case MENUNGGU_APPROVAL_KEMBALI = 'menunggu_approval_kembali';
    case SELESAI = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::MENUNGGU_APPROVAL_KEMBALI => 'Menunggu Approval Kembali',
            self::SELESAI => 'Selesai',
        };
    }
}
