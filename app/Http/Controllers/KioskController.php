<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    /**
     * Tampilkan antarmuka Kiosk Layanan Mandiri.
     */
    public function index()
    {
        $availableItems = Item::where('status', ItemStatus::TERSEDIA)
            ->select('id', 'item_code', 'name', 'type', 'location')
            ->orderBy('name')
            ->get();

        return view('kiosk.index', [
            'availableItems' => $availableItems,
        ]);
    }
}
