<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Loan;
use App\Services\CameraService;
use Illuminate\Http\Request;

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
            'photo' => 'nullable|string', // base64 dari webcam
        ]);

        // Cek status barang
        $item = Item::findOrFail($validated['item_id']);
        if ($item->status !== 'tersedia') {
            return response()->json([
                'success' => false,
                'message' => sprintf('Barang "%s" tidak tersedia.', $item->name),
            ], 422);
        }

        // Simpan foto snapshot jika ada
        $photoPath = null;
        if (!empty($validated['photo'])) {
            // Decode base64 dan simpan ke storage
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['photo']));
            $photoPath = $cameraService->capture($imageData, 'photos/loans');
        }

        // Buat loan record
        $loan = Loan::create([
            'user_id' => $validated['user_id'],
            'item_id' => $validated['item_id'],
            'status' => 'aktif',
            'borrowed_at' => now(),
        ]);

        // Update status item menjadi dipinjam
        $item->update(['status' => 'dipinjam']);

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman berhasil dicatat.',
            'data' => [
                'loan' => $loan,
                'item_name' => $item->name,
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

        $loan = Loan::with('item')->findOrFail($id);

        // Simpan kondisi barang dikembalikan di loan
        $loan->update([
            'condition_in' => $validated['condition_in'],
            'returned_at' => now(),
        ]);

        // Update status item berdasarkan kondisi
        $item = $loan->item;
        if ($validated['condition_in'] === 'rusak') {
            // Jika rusak, otomatis buat tiket M&R
            $issueDesc = "Dikembalikan dalam kondisi rusak oleh user {$loan->user_id}. Silakan periksa fisik barang.";
            $ticket = \App\Models\MaintenanceTicket::create([
                'item_id' => $item->id,
                'reported_by' => $loan->user_id,
                'issue_description' => $issueDesc,
                'status' => 'open',
            ]);

            $item->update(['status' => 'under_maintenance']);
        } else {
            $item->update(['status' => 'tersedia']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengembalian berhasil dicatat.',
            'item_status' => $item->status,
        ]);
    }
}
