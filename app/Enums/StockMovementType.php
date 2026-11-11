<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Stok Masuk',
            self::OUT => 'Pemakaian / Keluar',
        };
    }
}

