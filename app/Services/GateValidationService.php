<?php

namespace App\Services;

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
                'action' => 'denied',
                'reason' => 'EPC tag tidak ditemukan di database.',
            ];
        }

        switch ($item->status) {
            case 'dipinjam':
                return [
                    'action' => 'allowed',
                    'message' => 'Barang dengan status "dipinjam" — akses diizinkan.',
                    'item_name' => $item->name,
                ];

            default:
                return [
                    'action' => 'denied',
                    'reason' => sprintf(
                        'Barang "%s" status "%s" — tidak memiliki izin keluar.',
                        $item->name,
                        $item->status
                    ),
                    'item_name' => $item->name,
                ];
        }
    }
}
