<?php

namespace App\Enums;

enum ServiceType: string
{
    case INTERNAL = 'internal';
    case VENDOR_LUAR = 'vendor_luar';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::VENDOR_LUAR => 'Vendor Luar',
        };
    }
}
