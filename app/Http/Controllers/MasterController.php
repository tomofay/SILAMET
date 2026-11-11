<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    /**
     * Tampilkan daftar semua items (aset & bahan).
     */
    public function index()
    {
        $items = Item::latest()->paginate(15);
        return view('items.index', compact('items'));
    }

    /**
     * Tampilkan form create item baru.
     */
    public function create()
    {
        return view('items.create');
    }

    /**
     * Simpan item baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', Rule::unique('items', 'item_code')->whereNull('deleted_at')],
            'epc_tag' => ['nullable', 'string', 'max:100', Rule::unique('items', 'epc_tag')->whereNull('deleted_at')],
            'name' => 'required|string|max:100',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:0',
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
        ]);

        // Set default stock untuk aset
        if ($validated['type'] === 'aset') {
            $validated['stock'] = 1;
        } else {
            $validated['stock'] = (int) ($validated['stock'] ?? 0);
        }

        DB::transaction(function () use ($validated) {
            $item = Item::create($validated);

            if ($item->type === ItemType::BAHAN_HABIS_PAKAI && $item->stock > 0 && Auth::id()) {
                StockMovement::create([
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'type' => StockMovementType::IN,
                    'quantity' => $item->stock,
                    'stock_before' => 0,
                    'stock_after' => $item->stock,
                    'description' => 'Stok awal pendaftaran barang habis pakai',
                ]);
            }
        });

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail item.
     */
    public function show(string $id)
    {
        $item = Item::with(['loans.user', 'tickets.reportedBy', 'stockMovements.user'])->findOrFail($id);
        return view('items.show', compact('item'));
    }

    /**
     * Tampilkan form edit item.
     */
    public function edit(string $id)
    {
        $item = Item::findOrFail($id);
        return view('items.edit', compact('item'));
    }

    /**
     * Update item di database.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', Rule::unique('items', 'item_code')->ignore($id)->whereNull('deleted_at')],
            'epc_tag' => ['nullable', 'string', 'max:100', Rule::unique('items', 'epc_tag')->ignore($id)->whereNull('deleted_at')],
            'name' => 'required|string|max:100',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:0',
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($id, $validated) {
            $item = Item::query()->lockForUpdate()->findOrFail($id);
            $oldStock = (int) $item->stock;

            if ($validated['type'] === 'aset') {
                $validated['stock'] = 1;
            } elseif (! array_key_exists('stock', $validated) || $validated['stock'] === null) {
                $validated['stock'] = $oldStock;
            }

            $item->update($validated);
            $newStock = (int) $item->stock;

            if ($item->type === ItemType::BAHAN_HABIS_PAKAI && $newStock !== $oldStock && Auth::id()) {
                $diff = abs($newStock - $oldStock);
                StockMovement::create([
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'type' => $newStock > $oldStock ? StockMovementType::IN : StockMovementType::OUT,
                    'quantity' => $diff,
                    'stock_before' => $oldStock,
                    'stock_after' => $newStock,
                    'description' => 'Penyesuaian stok melalui edit Master Aset',
                ]);
            }
        });

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Hapus item (soft delete).
     */
    public function destroy(string $id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Cetak label barcode barang (halaman print-friendly).
     */
    public function printBarcodes(Request $request)
    {
        $ids = $request->get('ids');

        if ($ids) {
            $items = Item::whereIn('id', explode(',', $ids))->get();
        } else {
            $items = Item::all();
        }

        return view('items.barcodes', compact('items'));
    }
}
