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
            ->select('id', 'item_code', 'name', 'type', 'status', 'stock', 'location', 'epc_tag')
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Detail item tertentu (bisa via id numerik atau item_code barcode/QR).
     */
    public function show(string $id)
    {
        $item = Item::with(['loans', 'activeLoans.user', 'tickets'])
            ->where(function ($query) use ($id) {
                if (is_numeric($id)) {
                    $query->where('id', $id)->orWhere('item_code', $id);
                } else {
                    $query->where('item_code', $id);
                }
            })
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $item]);
    }
}
