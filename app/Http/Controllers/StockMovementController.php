<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    /**
     * Tampilkan Buku Stok Bahan Habis Pakai (Stock In / Stock Out Ledger).
     */
    public function index(Request $request)
    {
        $consumables = Item::query()
            ->where('type', ItemType::BAHAN_HABIS_PAKAI)
            ->orderBy('name')
            ->get();

        $query = StockMovement::with(['item', 'user'])->latest('created_at')->latest('id');

        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->input('item_id'));
        }

        if (in_array($request->input('type'), ['in', 'out'], true)) {
            $query->where('type', $request->input('type'));
        }

        $movements = $query->paginate(20)->withQueryString();

        $stats = [
            'consumable_count' => $consumables->count(),
            'low_stock_count' => $consumables->where('stock', '<=', 5)->count(),
            'total_in' => (int) StockMovement::where('type', StockMovementType::IN)->sum('quantity'),
            'total_out' => (int) StockMovement::where('type', StockMovementType::OUT)->sum('quantity'),
        ];

        return view('stock-movements.index', compact('consumables', 'movements', 'stats'));
    }

    /**
     * Catat mutasi stok masuk/keluar untuk barang bertipe bahan_habis_pakai secara atomik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1|max:100000',
            'recipient_name' => 'nullable|string|max:120',
            'description' => 'required|string|min:3|max:500',
        ], [
            'item_id.required' => 'Pilih bahan habis pakai terlebih dahulu.',
            'quantity.min' => 'Jumlah mutasi stok minimal 1 unit.',
            'description.required' => 'Keterangan pemakaian atau pengadaan wajib diisi.',
            'description.min' => 'Keterangan minimal 3 karakter.',
        ]);

        $movement = DB::transaction(function () use ($validated) {
            $item = Item::query()->lockForUpdate()->findOrFail($validated['item_id']);

            if ($item->type !== ItemType::BAHAN_HABIS_PAKAI) {
                throw ValidationException::withMessages([
                    'item_id' => sprintf('"%s" bertipe Aset tetap, bukan Bahan Habis Pakai.', $item->name),
                ]);
            }

            $stockBefore = (int) $item->stock;
            $qty = (int) $validated['quantity'];

            if ($validated['type'] === StockMovementType::OUT->value && $stockBefore < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf('Stok "%s" tidak mencukupi (tersisa %d unit).', $item->name, $stockBefore),
                ]);
            }

            $stockAfter = $validated['type'] === StockMovementType::IN->value
                ? $stockBefore + $qty
                : $stockBefore - $qty;

            $item->update(['stock' => $stockAfter]);

            return StockMovement::create([
                'item_id' => $item->id,
                'user_id' => Auth::id(),
                'type' => $validated['type'],
                'quantity' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'recipient_name' => filled($validated['recipient_name'] ?? null)
                    ? trim((string) $validated['recipient_name'])
                    : null,
                'description' => trim((string) $validated['description']),
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mutasi stok berhasil dicatat.',
                'data' => $movement->load(['item', 'user']),
            ]);
        }

        $typeLabel = $movement->type === StockMovementType::IN ? 'Penambahan stok masuk' : 'Pengambilan / keluar stok';

        return redirect()->back()
            ->with('success', sprintf('%s (%d unit) berhasil dicatat. Stok tersisa: %d.', $typeLabel, $movement->quantity, $movement->stock_after));
    }
}

