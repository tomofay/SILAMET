<?php

namespace App\Enums;

enum ItemStatus: string
{
    case TERSEDIA = 'tersedia';
    case DIPINJAM = 'dipinjam';
    case UNDER_MAINTENANCE = 'under_maintenance';

    public function label(): string
    {
        return match ($this) {
            self::TERSEDIA => 'Tersedia',
            self::DIPINJAM => 'Dipinjam',
            self::UNDER_MAINTENANCE => 'Under Maintenance',
        };
    }
}
