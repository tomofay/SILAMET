<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /**
     * Tampilkan daftar peminjaman yang sedang berlangsung (aktif / menunggu approval kembali).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status', '');

        $query = Loan::with(['user', 'item'])
            ->whereIn('status', [LoanStatus::AKTIF->value, LoanStatus::MENUNGGU_APPROVAL_KEMBALI->value]);

        if ($statusFilter !== '' && in_array($statusFilter, [LoanStatus::AKTIF->value, LoanStatus::MENUNGGU_APPROVAL_KEMBALI->value], true)) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('nis_nip', 'like', "%{$search}%");
                })->orWhereHas('item', function ($iq) use ($search) {
                    $iq->where('name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                });
            });
        }

        // Metrics for active circulation
        $activeTotal = Loan::where('status', LoanStatus::AKTIF->value)->count();
        $pendingApprovalTotal = Loan::where('status', LoanStatus::MENUNGGU_APPROVAL_KEMBALI->value)->count();
        $overdueTotal = Loan::where('status', LoanStatus::AKTIF->value)
            ->where('due_at', '<', now())
            ->count();

        $loans = $query->latest('borrowed_at')->paginate(15)->withQueryString();

        return view('loans.index', compact(
            'loans',
            'search',
            'statusFilter',
            'activeTotal',
            'pendingApprovalTotal',
            'overdueTotal'
        ));
    }

    /**
     * Tampilkan histori rekapitulasi peminjaman alat yang telah selesai.
     */
    public function history(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $conditionFilter = $request->query('condition', '');
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');

        $query = Loan::with(['user', 'item'])
            ->where('status', LoanStatus::SELESAI->value);

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('nis_nip', 'like', "%{$search}%");
                })->orWhereHas('item', function ($iq) use ($search) {
                    $iq->where('name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                });
            });
        }

        if ($conditionFilter !== '' && in_array($conditionFilter, ['bagus', 'rusak'], true)) {
            $query->where('condition_in', $conditionFilter);
        }

        if ($startDate !== '') {
            $query->whereDate('borrowed_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('borrowed_at', '<=', $endDate);
        }

        // Metrics for history
        $completedTotal = Loan::where('status', LoanStatus::SELESAI->value)->count();
        $goodConditionTotal = Loan::where('status', LoanStatus::SELESAI->value)->where('condition_in', 'bagus')->count();
        $damagedConditionTotal = Loan::where('status', LoanStatus::SELESAI->value)->where('condition_in', 'rusak')->count();

        $loans = $query->latest('returned_at')->paginate(15)->withQueryString();

        return view('loans.history', compact(
            'loans',
            'search',
            'conditionFilter',
            'startDate',
            'endDate',
            'completedTotal',
            'goodConditionTotal',
            'damagedConditionTotal'
        ));
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
            'item_id' => 'nullable|exists:items,id',
            'item_ids' => 'nullable|array|min:1',
            'item_ids.*' => 'exists:items,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:1|max:10000',
            'usage_duration' => ['required', Rule::enum(LoanDuration::class)],
        ]);

        $itemIds = [];
        if (! empty($validated['item_ids'])) {
            $itemIds = array_values(array_unique($validated['item_ids']));
        } elseif (! empty($validated['item_id'])) {
            $itemIds = [$validated['item_id']];
        } else {
            return redirect()->back()
                ->withErrors(['item_ids' => 'Pilih minimal satu barang untuk dipinjam.'])
                ->withInput();
        }

        $user = User::findOrFail($validated['user_id']);

        if (blank($user->phone)) {
            return redirect()->back()
                ->withErrors(['user_id' => 'Nomor WhatsApp siswa belum terdaftar.'])
                ->withInput();
        }

        $borrowedAt = now();
        $count = 0;

        DB::transaction(function () use ($validated, $itemIds, $borrowedAt, &$count) {
            foreach ($itemIds as $itemId) {
                // Kunci baris item agar tidak terjadi double-borrow
                $item = Item::query()->lockForUpdate()->findOrFail($itemId);

                if ($item->status !== ItemStatus::TERSEDIA) {
                    abort(422, sprintf('Barang "%s" tidak tersedia.', $item->name));
                }

                $borrowedQty = isset($validated['quantities'][$itemId]) ? max(1, (int) $validated['quantities'][$itemId]) : 1;

                Loan::create([
                    'user_id' => $validated['user_id'],
                    'item_id' => $item->id,
                    'quantity_borrowed' => $borrowedQty,
                    'status' => 'aktif',
                    'usage_duration' => $validated['usage_duration'],
                    'borrowed_at' => $borrowedAt,
                    'due_at' => Loan::calculateDueAt($validated['usage_duration'], $borrowedAt),
                ]);

                $item->update(['status' => ItemStatus::DIPINJAM]);
                $count++;
            }
        });

        $msg = $count > 1
            ? sprintf('Peminjaman %d barang berhasil dicatat.', $count)
            : 'Peminjaman berhasil dicatat.';

        return redirect()->route('loans.index')->with('success', $msg);
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

    /**
     * Sajikan foto snapshot verifikasi wajah peminjam secara aman (terproteksi auth staff).
     */
    public function photo(string $id)
    {
        $loan = Loan::findOrFail($id);
        $path = ltrim((string) $loan->capture_image_path, '/\\');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $contents = $disk->get($path);

            if ($contents !== null && $contents !== '') {
                return response($contents, 200, [
                    'Content-Type' => $this->detectImageMime($contents, $path),
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        $publicFallback = public_path('storage/'.$path);
        if (is_file($publicFallback)) {
            $contents = @file_get_contents($publicFallback);
            if ($contents !== false && $contents !== '') {
                return response($contents, 200, [
                    'Content-Type' => $this->detectImageMime($contents, $path),
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        abort(404);
    }

    private function detectImageMime(string $binary, string $path): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'image/gif';
        }

        if (strlen($binary) >= 12 && substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
