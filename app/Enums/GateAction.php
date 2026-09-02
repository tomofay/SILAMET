<?php

namespace App\Enums;

enum GateAction: string
{
    case ALLOWED = 'allowed';
    case DENIED = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWED => 'Diizinkan',
            self::DENIED => 'Ditolak',
        };
    }
}
