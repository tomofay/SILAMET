<?php

namespace App\Enums;

enum LoanStatus: string
{
    case AKTIF = 'aktif';
    case MENUNGGU_APPROVAL = 'menunggu_approval';
    case SELESAI = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::MENUNGGU_APPROVAL => 'Menunggu Approval',
            self::SELESAI => 'Selesai',
        };
    }
}
