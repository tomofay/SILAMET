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
     * Detail item tertentu.
     */
    public function show(string $id)
    {
        $item = Item::with(['loans', 'tickets'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $item]);
    }
}
