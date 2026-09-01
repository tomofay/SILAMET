<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * Tampilkan daftar semua loan.
     */
    public function index()
    {
        $loans = Loan::with(['user', 'item'])->latest()->paginate(15);
        return view('loans.index', compact('loans'));
    }

    /**
     * Tampilkan form create loan baru.
     */
    public function create()
    {
        $items = Item::where('status', 'tersedia')->get();
        return view('loans.create', compact('items'));
    }

    /**
     * Simpan loan baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'item_id' => 'required|exists:items,id',
        ]);

        // Cek apakah item tersedia
        $item = Item::findOrFail($validated['item_id']);
        if ($item->status !== 'tersedia') {
            return redirect()->back()
                ->withErrors(['item_id' => 'Barang tidak tersedia.'])
                ->withInput();
        }

        // Buat loan dan update status item
        $loan = Loan::create([
            'user_id' => $validated['user_id'],
            'item_id' => $validated['item_id'],
            'status' => 'aktif',
            'borrowed_at' => now(),
        ]);

        $item->update(['status' => 'dipinjam']);

        return redirect()->route('loans.index')
            ->with('success', 'Peminjaman berhasil dicatat.');
    }

    /**
     * Tampilkan detail loan.
     */
    public function show(string $id)
    {
        $loan = Loan::with(['user', 'item'])->findOrFail($id);
        return view('loans.show', compact('loan'));
    }

    /**
     * Tampilkan form edit loan.
     */
    public function edit(string $id)
    {
        $loan = Loan::findOrFail($id);
        return view('loans.edit', compact('loan'));
    }

    /**
     * Update loan di database.
     */
    public function update(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:aktif,menunggu_approval,selesai',
        ]);

        $loan->update($validated);

        return redirect()->route('loans.index')
            ->with('success', 'Status loan diperbarui.');
    }

    /**
     * Hapus loan (soft delete).
     */
    public function destroy(string $id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return redirect()->route('loans.index')
            ->with('success', 'Loan berhasil dihapus.');
    }
}
