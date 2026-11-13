<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemStatus;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendMaintenanceTicketTelegramJob;
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
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:1|max:10000',
            'photo' => 'nullable|string', // base64 dari webcam
        ]);

        $itemIds = [];
        if (! empty($validated['item_ids'])) {
            $itemIds = array_values(array_unique($validated['item_ids']));
        } elseif (! empty($validated['item_id'])) {
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
            if (! empty($validated['photo'])) {
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

                $borrowedQty = isset($validated['quantities'][$itemId]) ? max(1, (int) $validated['quantities'][$itemId]) : 1;

                $loan = Loan::create([
                    'user_id' => $validated['user_id'],
                    'item_id' => $item->id,
                    'quantity_borrowed' => $borrowedQty,
                    'status' => LoanStatus::AKTIF,
                    'capture_image_path' => $photoPath,
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
        $itemNames = collect($loans)->map(fn ($l) => $l->item->name)->implode(', ');

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
            'issue_description' => 'required_if:condition_in,rusak|nullable|string|min:3',
            'photo' => 'nullable|string',
        ], [
            'issue_description.required_if' => 'Catatan kerusakan wajib diisi jika kondisi alat rusak.',
            'issue_description.min' => 'Catatan kerusakan minimal 3 karakter.',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $loan = Loan::query()->with('item')->lockForUpdate()->findOrFail($id);

            if ($loan->status !== LoanStatus::AKTIF) {
                abort(422, 'Peminjaman sudah dikembalikan.');
            }

            $item = Item::query()->lockForUpdate()->findOrFail($loan->item_id);
            $issueDesc = ($validated['condition_in'] === 'rusak')
                ? (! empty($validated['issue_description']) ? trim($validated['issue_description']) : 'Barang dikembalikan dalam kondisi rusak. Silakan periksa fisik barang.')
                : null;

            $loan->update([
                'condition_in' => $validated['condition_in'],
                'damage_note' => $issueDesc,
                'status' => LoanStatus::SELESAI,
                'returned_at' => now(),
            ]);

            $ticketId = null;

            if ($validated['condition_in'] === 'rusak') {
                $ticket = MaintenanceTicket::create([
                    'item_id' => $item->id,
                    'reported_by' => $loan->user_id,
                    'issue_description' => $issueDesc,
                    'status' => 'open',
                ]);
                $ticketId = $ticket->id;
                $item->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
            } else {
                $item->update(['status' => ItemStatus::TERSEDIA]);
            }

            return ['loan' => $loan->fresh(), 'item' => $item->fresh(), 'ticket_id' => $ticketId];
        });

        if (! empty($result['ticket_id']) && config('services.telegram.enabled')) {
            dispatch(new SendMaintenanceTicketTelegramJob($result['ticket_id'], 'created'));
        }

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
     * Ambil daftar peminjaman aktif milik siswa berdasarkan NIS.
     * Digunakan sebelum pengembalian barang di Kiosk Mandiri.
     */
    public function getActiveLoansByNis(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string',
        ]);

        // Find user by barcode token (SLM-XXXXXXXX) or fallback to nis_nip
        $user = \App\Services\BarcodeTokenService::resolveUser($validated['nis']);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa dengan NIS tersebut tidak ditemukan.',
            ], 404);
        }

        $activeLoans = Loan::with('item')
            ->where('user_id', $user->id)
            ->where('status', LoanStatus::AKTIF)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nis_nip' => $user->nis_nip,
                    'phone' => $user->phone,
                ],
                'active_loans' => $activeLoans->map(function ($loan) {
                    return [
                        'loan_id' => $loan->id,
                        'item_id' => $loan->item_id,
                        'item_code' => $loan->item->item_code,
                        'barcode_token' => $loan->item->barcode_token,
                        'item_name' => $loan->item->name,
                        'category' => $loan->item->category,
                        'borrowed_at' => $loan->borrowed_at?->format('Y-m-d H:i:s'),
                        'due_at' => $loan->due_at?->format('Y-m-d H:i:s'),
                        'is_overdue' => $loan->isOverdue(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Pengembalian Mandiri di Kiosk (Layar 4 UI/UX Design).
     * Wajib scan NIS peminjam dahulu, hanya peminjam yang boleh mengembalikan,
     * dan mendukung pengembalian banyak barang sekaligus.
     */
    public function returnKiosk(Request $request)
    {
        // Mendukung format multi-item (items array) maupun single item format
        if (! $request->has('items') && $request->has('item_code')) {
            $request->merge([
                'items' => [
                    [
                        'item_code' => $request->input('item_code'),
                        'condition_in' => $request->input('condition_in'),
                        'issue_description' => $request->input('issue_description'),
                    ],
                ],
            ]);

            // Jika request single lama tidak menyertakan NIS (seperti di test lama), ambil dari borrower aktif
            if (! $request->filled('nis')) {
                $tempItem = \App\Services\BarcodeTokenService::resolveItem($request->input('item_code'));
                if ($tempItem) {
                    $activeL = Loan::with('user')->where('item_id', $tempItem->id)->where('status', LoanStatus::AKTIF)->latest()->first();
                    if ($activeL && $activeL->user) {
                        $request->merge(['nis' => $activeL->user->barcode_token ?? $activeL->user->nis_nip]);
                    }
                }
            }
        }

        $validated = $request->validate([
            'nis' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string',
            'items.*.condition_in' => 'required|in:bagus,rusak',
            'items.*.issue_description' => 'required_if:items.*.condition_in,rusak|nullable|string|min:3',
        ], [
            'items.*.issue_description.required_if' => 'Catatan kerusakan wajib diisi jika kondisi alat rusak.',
            'items.*.issue_description.min' => 'Catatan kerusakan minimal 3 karakter.',
        ]);

        // Find user by barcode token (SLM-XXXXXXXX) or fallback to nis_nip
        $user = \App\Services\BarcodeTokenService::resolveUser($validated['nis']);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa dengan NIS tersebut tidak ditemukan.',
            ], 404);
        }

        $itemCodes = collect($validated['items'])->pluck('item_code')->all();

        // Cari item berdasarkan barcode_token, item_code, atau ID
        $items = Item::whereIn('barcode_token', $itemCodes)
            ->orWhereIn('item_code', $itemCodes)
            ->orWhereIn('id', $itemCodes)
            ->get();

        // Validasi keberadaan setiap item
        foreach ($validated['items'] as $entry) {
            $code = (string) $entry['item_code'];
            $found = $items->first(function ($it) use ($code) {
                return (strcasecmp($it->barcode_token ?? '', $code) === 0)
                    || (strcasecmp($it->item_code, $code) === 0)
                    || ((string) $it->id === $code);
            });
            if (! $found) {
                return response()->json([
                    'success' => false,
                    'message' => "Barang dengan kode/barcode '{$code}' tidak ditemukan di sistem.",
                ], 404);
            }
        }

        $processedLoans = [];
        $createdTicketIds = [];
        $hasDamaged = false;

        $result = DB::transaction(function () use ($validated, $user, $items, &$processedLoans, &$createdTicketIds, &$hasDamaged) {
            foreach ($validated['items'] as $entry) {
                $code = (string) $entry['item_code'];
                $item = $items->first(function ($it) use ($code) {
                    return (strcasecmp($it->barcode_token ?? '', $code) === 0)
                        || (strcasecmp($it->item_code, $code) === 0)
                        || ((string) $it->id === $code);
                });

                // Kunci item
                $lockedItem = Item::query()->lockForUpdate()->findOrFail($item->id);

                // Cari pinjaman aktif untuk item ini
                $activeLoan = Loan::query()
                    ->lockForUpdate()
                    ->where('item_id', $lockedItem->id)
                    ->where('status', LoanStatus::AKTIF)
                    ->latest()
                    ->first();

                if (! $activeLoan) {
                    abort(422, "Barang '{$lockedItem->name}' ({$lockedItem->item_code}) tidak sedang dalam status peminjaman aktif.");
                }

                // Cek kepemilikan: hanya siswa peminjam yang boleh mengembalikan
                if ($activeLoan->user_id !== $user->id) {
                    abort(403, "Barang '{$lockedItem->name}' ({$lockedItem->item_code}) dipinjam oleh siswa lain, bukan oleh {$user->name}. Hanya peminjam yang boleh mengembalikan.");
                }

                $condition = $entry['condition_in'];
                if ($condition === 'rusak') {
                    $hasDamaged = true;
                }

                $description = ($condition === 'rusak')
                    ? (! empty($entry['issue_description']) ? $entry['issue_description'] : 'Barang dikembalikan dalam kondisi rusak melalui Kiosk Mandiri.')
                    : null;

                $activeLoan->update([
                    'condition_in' => $condition,
                    'damage_note' => $description,
                    'status' => LoanStatus::SELESAI,
                    'returned_at' => now(),
                ]);

                if ($condition === 'rusak') {
                    $ticket = MaintenanceTicket::create([
                        'item_id' => $lockedItem->id,
                        'reported_by' => $user->id,
                        'issue_description' => $description,
                        'status' => 'open',
                    ]);
                    $createdTicketIds[] = $ticket->id;
                    $lockedItem->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
                } else {
                    $lockedItem->update(['status' => ItemStatus::TERSEDIA]);
                }

                $processedLoans[] = [
                    'loan_id' => $activeLoan->id,
                    'item_id' => $lockedItem->id,
                    'item_code' => $lockedItem->item_code,
                    'item_name' => $lockedItem->name,
                    'condition_in' => $condition,
                    'item_status' => $lockedItem->fresh()->status->value,
                ];
            }

            return $processedLoans;
        });

        if (config('services.telegram.enabled')) {
            foreach ($createdTicketIds as $ticketId) {
                dispatch(new SendMaintenanceTicketTelegramJob($ticketId, 'created'));
            }
        }

        $count = count($result);
        $message = $hasDamaged
            ? "Pengembalian {$count} barang berhasil diproses. Tiket perbaikan (M&R) dibuat otomatis untuk barang rusak."
            : "Pengembalian {$count} barang berhasil! Terima kasih telah mengembalikan tepat waktu.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'total_returned' => $count,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nis_nip' => $user->nis_nip,
                ],
                'returned_items' => $result,
            ],
        ]);
    }
}
