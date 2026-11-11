<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;

class ItemApiController extends Controller
{
    /**
     * Tampilkan daftar items tersedia (publik — tanpa auth).
     */
    public function index()
    {
        $items = Item::where('status', ItemStatus::TERSEDIA)
            ->select('id', 'item_code', 'barcode_token', 'name', 'type', 'status', 'stock', 'location', 'epc_tag')
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Detail item tertentu (bisa via barcode_token ITM-..., item_code, id numerik, atau epc_tag).
     */
    public function show(string $id)
    {
        $resolved = \App\Services\BarcodeTokenService::resolveItem($id);

        if (!$resolved) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan kode atau barcode '{$id}' tidak ditemukan.",
            ], 404);
        }

        $item = Item::with(['loans', 'activeLoans.user', 'tickets'])->findOrFail($resolved->id);

        return response()->json(['success' => true, 'data' => $item]);
    }
}
