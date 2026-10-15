<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemStatus;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\CameraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanApiController extends Controller
{
    /**
     * Buat loan baru dari Kiosk — scan NIS + item + snapshot wajah.
     */
    public function store(Request $request, CameraService $cameraService)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'item_id' => 'nullable|exists:items,id',
            'item_ids' => 'nullable|array|min:1',
            'item_ids.*' => 'exists:items,id',
            'usage_duration' => ['required', Rule::enum(LoanDuration::class)],
            'photo' => 'nullable|string', // base64 dari webcam
        ]);

        $itemIds = [];
        if (!empty($validated['item_ids'])) {
            $itemIds = array_values(array_unique($validated['item_ids']));
        } elseif (!empty($validated['item_id'])) {
            $itemIds = [$validated['item_id']];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu barang untuk dipinjam.',
            ], 422);
        }

        $user = User::query()->findOrFail($validated['user_id']);

        if (blank($user->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp siswa belum terdaftar. Hubungi admin untuk melengkapi data.',
            ], 422);
        }

        $borrowedAt = now();

        $photoPath = null;
        $loans = DB::transaction(function () use ($validated, $itemIds, $cameraService, $borrowedAt, &$photoPath) {
            if (!empty($validated['photo'])) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo']), true);
                if ($imageData === false) {
                    abort(422, 'Format snapshot tidak valid.');
                }
                $photoPath = $cameraService->capture($imageData, 'photos/loans');
            }

            $dueAt = Loan::calculateDueAt($validated['usage_duration'], $borrowedAt);
            $createdLoans = [];

            foreach ($itemIds as $itemId) {
                $item = Item::query()->lockForUpdate()->findOrFail($itemId);

                if ($item->status !== ItemStatus::TERSEDIA) {
                    abort(422, sprintf('Barang "%s" tidak tersedia (status: %s).', $item->name, $item->status->value));
                }

                if (Loan::query()
                    ->where('item_id', $item->id)
                    ->where('status', LoanStatus::AKTIF)
                    ->exists()) {
                    abort(422, sprintf('Barang "%s" sedang memiliki peminjaman aktif.', $item->name));
                }

                $loan = Loan::create([
                    'user_id' => $validated['user_id'],
                    'item_id' => $item->id,
                    'status' => LoanStatus::AKTIF,
                    'usage_duration' => $validated['usage_duration'],
                    'borrowed_at' => $borrowedAt,
                    'due_at' => $dueAt,
                ]);

                $item->update(['status' => ItemStatus::DIPINJAM]);
                $createdLoans[] = $loan;
            }

            return $createdLoans;
        });

        $firstLoan = $loans[0];
        $itemNames = collect($loans)->map(fn($l) => $l->item->name)->implode(', ');

        return response()->json([
            'success' => true,
            'message' => count($loans) > 1 
                ? sprintf('Peminjaman %d barang berhasil dicatat.', count($loans)) 
                : 'Peminjaman berhasil dicatat.',
            'data' => [
                'loan' => $firstLoan->load('user:id,name,nis_nip,phone'),
                'loans' => $loans,
                'total_items' => count($loans),
                'item_name' => $itemNames,
                'due_at' => $firstLoan->due_at,
                'photo_path' => $photoPath,
            ],
        ]);
    }

    /**
     * Kembalikan barang via Kiosk.
     */
    public function return(string $id, Request $request, CameraService $cameraService)
    {
        $validated = $request->validate([
            'condition_in' => 'required|in:bagus,rusak',
            'photo' => 'nullable|string',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $loan = Loan::query()->with('item')->lockForUpdate()->findOrFail($id);

            if ($loan->status !== LoanStatus::AKTIF) {
                abort(422, 'Peminjaman sudah dikembalikan.');
            }

            $item = Item::query()->lockForUpdate()->findOrFail($loan->item_id);
            $loan->update([
                'condition_in' => $validated['condition_in'],
                'status' => LoanStatus::SELESAI,
                'returned_at' => now(),
            ]);

            if ($validated['condition_in'] === 'rusak') {
                MaintenanceTicket::create([
                    'item_id' => $item->id,
                    'reported_by' => $loan->user_id,
                    'issue_description' => 'Barang dikembalikan dalam kondisi rusak. Silakan periksa fisik barang.',
                    'status' => 'open',
                ]);
                $item->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
            } else {
                $item->update(['status' => ItemStatus::TERSEDIA]);
            }

            return ['loan' => $loan->fresh(), 'item' => $item->fresh()];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengembalian berhasil dicatat.',
            'data' => [
                'loan' => $result['loan'],
                'item_status' => $result['item']->status->value,
            ],
        ]);
    }

    /**
     * Pengembalian Mandiri di Kiosk (Layar 4 UI/UX Design).
     * Mendukung pengembalian 1 barang maupun banyak barang sekaligus dari peminjam yang sama.
     */
    public function returnKiosk(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'nullable|string',
            'item_codes' => 'nullable|array|min:1',
            'item_codes.*' => 'string',
            'condition_in' => 'required|in:bagus,rusak',
            'issue_description' => 'nullable|string',
        ]);

        $itemCodes = [];
        if (!empty($validated['item_codes'])) {
            $itemCodes = array_values(array_unique($validated['item_codes']));
        } elseif (!empty($validated['item_code'])) {
            $itemCodes = [$validated['item_code']];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pilih atau scan minimal satu kode barang.',
            ], 422);
        }

        // Cari semua barang berdasarkan kode
        $items = Item::whereIn('item_code', $itemCodes)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Barang dengan kode tersebut tidak ditemukan.',
            ], 404);
        }

        // Kumpulkan pinjaman aktif untuk setiap barang
        $activeLoans = Loan::with(['item', 'user'])
            ->whereIn('item_id', $items->pluck('id'))
            ->where('status', LoanStatus::AKTIF)
            ->get();

        if ($activeLoans->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada barang yang sedang dalam status peminjaman aktif.',
            ], 422);
        }

        // Verifikasi bahwa semua barang dipinjam oleh peminjam (siswa) yang sama
        $borrowerIds = $activeLoans->pluck('user_id')->unique();
        if ($borrowerIds->count() > 1) {
            $borrowerNames = $activeLoans->map(fn($l) => $l->item->name . ' (' . ($l->user->name ?? 'User') . ')')->implode(', ');
            return response()->json([
                'success' => false,
                'message' => 'Barang yang dikembalikan berasal dari peminjam yang berbeda: ' . $borrowerNames . '. Harap kembalikan barang terpisah per peminjam.',
            ], 422);
        }

        $borrower = $activeLoans->first()->user;

        $returnedLoans = DB::transaction(function () use ($activeLoans, $validated) {
            $results = [];

            foreach ($activeLoans as $activeLoan) {
                $loan = Loan::query()->lockForUpdate()->findOrFail($activeLoan->id);
                $lockedItem = Item::query()->lockForUpdate()->findOrFail($activeLoan->item_id);

                $loan->update([
                    'condition_in' => $validated['condition_in'],
                    'status' => LoanStatus::SELESAI,
                    'returned_at' => now(),
                ]);

                if ($validated['condition_in'] === 'rusak') {
                    $description = $validated['issue_description']
                        ? $validated['issue_description']
                        : sprintf('Barang "%s" dikembalikan dalam kondisi rusak melalui Kiosk Mandiri.', $lockedItem->name);

                    MaintenanceTicket::create([
                        'item_id' => $lockedItem->id,
                        'reported_by' => $loan->user_id,
                        'issue_description' => $description,
                        'status' => 'open',
                    ]);
                    $lockedItem->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
                } else {
                    $lockedItem->update(['status' => ItemStatus::TERSEDIA]);
                }

                $results[] = $loan;
            }

            return $results;
        });

        $count = count($returnedLoans);
        $itemNames = $activeLoans->map(fn($l) => $l->item->name)->implode(', ');

        $message = $validated['condition_in'] === 'rusak'
            ? sprintf('%d barang berhasil dikembalikan dengan catatan rusak. Tiket perbaikan (M&R) telah dibuat otomatis.', $count)
            : sprintf('%d barang (%s) berhasil dikembalikan oleh %s! Terima kasih.', $count, $itemNames, $borrower?->name ?? 'Siswa');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'total_returned' => $count,
                'borrower_name' => $borrower?->name ?? 'Siswa',
                'item_names' => $itemNames,
                'condition' => $validated['condition_in'],
            ],
        ]);
    }
}
