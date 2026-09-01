<?php

namespace App\Enums;

enum ItemType: string
{
    case ASET = 'aset';
    case BAHAN_HABIS_PAKAI = 'bahan_habis_pakai';

    public function label(): string
    {
        return match ($this) {
            self::ASET => 'Aset',
            self::BAHAN_HABIS_PAKAI => 'Bahan Habis Pakai',
        };
    }
}
