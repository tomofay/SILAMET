<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

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
            'item_code' => 'required|string|max:50|unique:items,item_code',
            'epc_tag' => 'nullable|string|max:100|unique:items,epc_tag',
            'name' => 'required|string|max:100',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:1',
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
        ]);

        // Set default stock untuk aset
        if ($validated['type'] === 'aset') {
            $validated['stock'] = 1;
        }

        Item::create($validated);

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail item.
     */
    public function show(string $id)
    {
        $item = Item::with(['loans', 'tickets'])->findOrFail($id);
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
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'item_code' => "required|string|max:50|unique:items,item_code,$id",
            'epc_tag' => "nullable|string|max:100|unique:items,epc_tag,$id",
            'name' => 'required|string|max:100',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:1',
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
        ]);

        $item->update($validated);

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
}
