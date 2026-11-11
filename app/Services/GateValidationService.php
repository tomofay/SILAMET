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
        // Cari exact match terlebih dahulu
        $item = Item::where('epc_tag', $epcTag)->first();

        // Jika tidak ketemu, cari partial match jika ada perbedaan prefix reader (misal 0700EE00)
        if (!$item) {
            $item = Item::whereNotNull('epc_tag')
                ->where('epc_tag', '!=', '')
                ->get()
                ->first(function ($it) use ($epcTag) {
                    $cleanDb = strtoupper((string) $it->epc_tag);
                    $cleanScan = strtoupper($epcTag);
                    return str_contains($cleanDb, $cleanScan) || str_contains($cleanScan, $cleanDb);
                });
        }

        return $this->formatItemResult($item);
    }

    /**
     * Validasi banyak EPC tag sekaligus dalam 1 query database (optimal untuk multi-tag burst).
     *
     * @param  array<int, string>  $epcTags
     * @return array<string, array<string, mixed>>
     */
    public function checkTags(array $epcTags): array
    {
        $cleanTags = array_values(array_unique(array_filter(
            array_map(fn ($tag) => trim((string) $tag), $epcTags),
            fn (string $tag) => $tag !== ''
        )));

        if (empty($cleanTags)) {
            return [];
        }

        if (count($cleanTags) === 1) {
            return [$cleanTags[0] => $this->checkTag($cleanTags[0])];
        }

        $items = Item::query()
            ->whereNotNull('epc_tag')
            ->where('epc_tag', '!=', '')
            ->get(['id', 'name', 'item_code', 'location', 'status', 'epc_tag']);

        $exactMap = [];
        $upperMap = [];
        foreach ($items as $item) {
            $raw = (string) $item->epc_tag;
            $exactMap[$raw] ??= $item;
            $upperMap[strtoupper(trim($raw))] ??= $item;
        }

        $results = [];
        foreach ($cleanTags as $epcTag) {
            $upperScan = strtoupper($epcTag);
            $matched = $exactMap[$epcTag] ?? $upperMap[$upperScan] ?? null;

            if (! $matched) {
                $matched = $items->first(function ($it) use ($upperScan) {
                    $cleanDb = strtoupper((string) $it->epc_tag);

                    return str_contains($cleanDb, $upperScan) || str_contains($upperScan, $cleanDb);
                });
            }

            $results[$epcTag] = $this->formatItemResult($matched);
        }

        return $results;
    }

    /**
     * Bentuk struktur respon validasi gate dari model Item hasil pencocokan.
     *
     * @return array<string, mixed>
     */
    private function formatItemResult(?Item $item): array
    {
        if (!$item) {
            return [
                'status' => 'denied',
                'reason' => 'EPC tag tidak ditemukan di database.',
                'item_id' => null,
                'item_code' => null,
                'item_location' => null,
                'item_status' => null,
            ];
        }

        if ($item->status === ItemStatus::DIPINJAM) {
            return [
                'status' => 'allowed',
                'message' => 'Barang dengan status "dipinjam" — akses diizinkan.',
                'item_name' => $item->name,
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'item_location' => $item->location,
                'item_status' => $item->status->value,
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
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'item_location' => $item->location,
            'item_status' => $item->status->value,
        ];
    }
}
