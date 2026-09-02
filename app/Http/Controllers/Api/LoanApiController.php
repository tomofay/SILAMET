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
            'item_id' => 'required|exists:items,id',
            'usage_duration' => ['required', Rule::enum(LoanDuration::class)],
            'photo' => 'nullable|string', // base64 dari webcam
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        if (blank($user->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp siswa belum terdaftar. Hubungi admin untuk melengkapi data.',
            ], 422);
        }

        $borrowedAt = now();

        $photoPath = null;
        $loan = DB::transaction(function () use ($validated, $cameraService, $borrowedAt, &$photoPath) {
            $item = Item::query()->lockForUpdate()->findOrFail($validated['item_id']);

            if ($item->status !== ItemStatus::TERSEDIA) {
                abort(422, sprintf('Barang "%s" tidak tersedia.', $item->name));
            }

            if (Loan::query()
                ->where('item_id', $item->id)
                ->where('status', LoanStatus::AKTIF)
                ->exists()) {
                abort(422, 'Barang sedang memiliki peminjaman aktif.');
            }

            if (!empty($validated['photo'])) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo']), true);
                if ($imageData === false) {
                    abort(422, 'Format snapshot tidak valid.');
                }
                $photoPath = $cameraService->capture($imageData, 'photos/loans');
            }

            $dueAt = Loan::calculateDueAt($validated['usage_duration'], $borrowedAt);

            $loan = Loan::create([
                'user_id' => $validated['user_id'],
                'item_id' => $item->id,
                'status' => LoanStatus::AKTIF,
                'usage_duration' => $validated['usage_duration'],
                'borrowed_at' => $borrowedAt,
                'due_at' => $dueAt,
            ]);

            $item->update(['status' => ItemStatus::DIPINJAM]);

            return $loan;
        });

        $item = $loan->item;

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman berhasil dicatat.',
            'data' => [
                'loan' => $loan->load('user:id,name,nis_nip,phone'),
                'item_name' => $item->name,
                'due_at' => $loan->due_at,
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
}
