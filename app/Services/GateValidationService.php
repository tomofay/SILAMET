<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Models\Item;

class GateValidationService
{
    /**
     * Validasi EPC tag saat melintasi pintu gate.
     * Return: allowed atau denied berdasarkan status barang.
     */
    public function checkTag(string $epcTag): array
    {
        $item = Item::where('epc_tag', $epcTag)->first();

        if (!$item) {
            return [
                'status' => 'denied',
                'reason' => 'EPC tag tidak ditemukan di database.',
            ];
        }

        if ($item->status === ItemStatus::DIPINJAM) {
            return [
                'status' => 'allowed',
                'message' => 'Barang dengan status "dipinjam" — akses diizinkan.',
                'item_name' => $item->name,
            ];
        }

        return [
            'status' => 'denied',
            'reason' => sprintf(
                'Barang "%s" status "%s" — tidak memiliki izin keluar.',
                $item->name,
                $item->status->value
            ),
            'item_name' => $item->name,
        ];
    }
}
