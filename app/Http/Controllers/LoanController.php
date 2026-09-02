<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\LoanDuration;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $items = Item::where('status', ItemStatus::TERSEDIA)->get();
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
            'usage_duration' => ['required', Rule::enum(LoanDuration::class)],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (blank($user->phone)) {
            return redirect()->back()
                ->withErrors(['user_id' => 'Nomor WhatsApp siswa belum terdaftar.'])
                ->withInput();
        }

        $borrowedAt = now();

        DB::transaction(function () use ($validated, $borrowedAt) {
            // Kunci baris item agar tidak terjadi double-borrow
            $item = Item::query()->lockForUpdate()->findOrFail($validated['item_id']);

            if ($item->status !== ItemStatus::TERSEDIA) {
                abort(422, 'Barang tidak tersedia.');
            }

            Loan::create([
                'user_id' => $validated['user_id'],
                'item_id' => $item->id,
                'status' => 'aktif',
                'usage_duration' => $validated['usage_duration'],
                'borrowed_at' => $borrowedAt,
                'due_at' => Loan::calculateDueAt($validated['usage_duration'], $borrowedAt),
            ]);

            $item->update(['status' => ItemStatus::DIPINJAM]);
        });

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
     * Update loan di database (termasuk sinkronisasi status barang).
     */
    public function update(Request $request, string $id)
    {
        $loan = Loan::with('item')->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:aktif,menunggu_approval_kembali,selesai',
        ]);

        DB::transaction(function () use ($loan, $validated) {
            $item = $loan->item;

            if ($validated['status'] === 'selesai') {
                $loan->update([
                    'status' => $validated['status'],
                    'returned_at' => $loan->returned_at ?? now(),
                ]);
                // Barang dikembalikan → tersedia (bila sebelumnya dipinjam)
                if ($item && $item->status === ItemStatus::DIPINJAM) {
                    $item->update(['status' => ItemStatus::TERSEDIA]);
                }
            } elseif ($validated['status'] === 'aktif' && $item && $item->status !== ItemStatus::DIPINJAM) {
                $loan->update(['status' => 'aktif', 'returned_at' => null]);
                if ($item->status === ItemStatus::TERSEDIA) {
                    $item->update(['status' => ItemStatus::DIPINJAM]);
                }
            } else {
                $loan->update(['status' => $validated['status']]);
            }
        });

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
