<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\PreventiveSchedule;
use App\Models\StockMovement;
use App\Services\ItemGroupService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    public function __construct(
        private readonly ItemGroupService $itemGroupService,
    ) {}

    /**
     * Tampilkan katalog Master Aset terkelompok (TPLINK, PC, MIKROTIK, dll) atau daftar unit lengkap saat kelompok dibuka.
     */
    public function index(Request $request)
    {
        $allItems = Item::with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
            ->orderBy('name')
            ->orderBy('item_code')
            ->get();

        $groupData = $this->itemGroupService->buildGroups($allItems, false);
        $groups = $groupData['groups'];

        $requestedGroup = trim((string) $request->query('group', ''));
        $search = trim((string) $request->query('q', ''));
        $requestedView = trim((string) $request->query('view', ''));

        $activeGroup = $requestedGroup !== ''
            ? $this->itemGroupService->findGroup($groups, $requestedGroup)
            : null;

        if ($activeGroup !== null) {
            $viewMode = 'group_detail';
            $itemIds = $activeGroup['item_ids'];
            $items = empty($itemIds)
                ? new LengthAwarePaginator([], 0, 25)
                : Item::with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
                    ->whereIn('id', $itemIds)
                    ->when($search !== '', function ($q) use ($search) {
                        $this->applySearchFilter($q, $search);
                    })
                    ->orderBy('name')
                    ->orderBy('item_code')
                    ->paginate(25)
                    ->withQueryString();
        } elseif ($search !== '' || $requestedView === 'all') {
            $viewMode = 'all';
            $items = Item::with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
                ->when($search !== '', function ($q) use ($search) {
                    $this->applySearchFilter($q, $search);
                })
                ->latest('id')
                ->paginate(20)
                ->withQueryString();
        } else {
            $viewMode = 'groups';
            $items = new LengthAwarePaginator([], 0, 15);
        }

        $totalItemCount = $allItems->count();
        $categorySuggestions = $this->itemGroupService->distinctCategorySuggestions();

        return view('items.index', compact(
            'items',
            'groups',
            'activeGroup',
            'viewMode',
            'search',
            'totalItemCount',
            'categorySuggestions'
        ));
    }

    /**
     * Tambah kelompok barang baru saja (tanpa membuat unit barang atau jadwal maintenance).
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'type' => 'nullable|in:aset,bahan_habis_pakai',
        ], [
            'name.required' => 'Nama kelompok barang wajib diisi.',
            'name.max' => 'Nama kelompok barang maksimal 80 karakter.',
        ]);

        $cleanName = $this->itemGroupService->cleanCategory($validated['name']);
        if ($cleanName === null || preg_replace('/[^a-z0-9]/i', '', $cleanName) === '') {
            throw ValidationException::withMessages([
                'name' => 'Nama kelompok barang harus mengandung minimal satu huruf atau angka.',
            ]);
        }

        $slug = $this->itemGroupService->canonicalKey($cleanName);

        $allItems = Item::query()->orderBy('name')->orderBy('item_code')->get();
        $existingGroups = $this->itemGroupService->buildGroups($allItems, false)['groups'];

        if (
            ItemGroup::where('slug', $slug)->exists()
            || $this->itemGroupService->findGroup($existingGroups, $slug) !== null
        ) {
            throw ValidationException::withMessages([
                'name' => "Kelompok barang \"{$cleanName}\" sudah ada di Master Aset.",
            ]);
        }

        $groupType = $validated['type'] ?? 'aset';

        DB::transaction(function () use ($cleanName, $slug, $groupType) {
            ItemGroup::create([
                'name' => $cleanName,
                'slug' => $slug,
                'type' => $groupType,
                'created_by' => Auth::id(),
            ]);
        });

        return redirect()->route('items.index')
            ->with('success', "Kelompok barang \"{$cleanName}\" berhasil ditambahkan.");
    }

    /**
     * Ubah nama atau tipe kelompok barang.
     */
    public function updateGroup(Request $request)
    {
        $validated = $request->validate([
            'old_slug' => 'required|string',
            'name' => 'required|string|max:80',
            'type' => 'nullable|in:aset,bahan_habis_pakai',
        ], [
            'name.required' => 'Nama kelompok barang wajib diisi.',
            'name.max' => 'Nama kelompok barang maksimal 80 karakter.',
        ]);

        $cleanName = $this->itemGroupService->cleanCategory($validated['name']);
        if ($cleanName === null || preg_replace('/[^a-z0-9]/i', '', $cleanName) === '') {
            throw ValidationException::withMessages([
                'name' => 'Nama kelompok barang harus mengandung minimal satu huruf atau angka.',
            ]);
        }

        $oldSlug = $validated['old_slug'];
        $newSlug = $this->itemGroupService->canonicalKey($cleanName);

        // Jika nama berubah, pastikan tidak bentrok dengan kelompok lain
        if ($oldSlug !== $newSlug) {
            $allItems = Item::query()->orderBy('name')->orderBy('item_code')->get();
            $existingGroups = $this->itemGroupService->buildGroups($allItems, false)['groups'];

            if (
                ItemGroup::where('slug', $newSlug)->where('slug', '!=', $oldSlug)->exists()
                || ($this->itemGroupService->findGroup($existingGroups, $newSlug) !== null && $oldSlug !== $newSlug)
            ) {
                throw ValidationException::withMessages([
                    'name' => "Nama kelompok \"{$cleanName}\" sudah digunakan oleh kelompok lain.",
                ]);
            }
        }

        DB::transaction(function () use ($cleanName, $newSlug, $oldSlug, $validated) {
            $itemGroup = ItemGroup::where('slug', $oldSlug)->first();
            $oldLabel = $oldSlug;

            if ($itemGroup) {
                $oldLabel = $itemGroup->name;
                $updateData = [
                    'name' => $cleanName,
                    'slug' => $newSlug,
                ];
                if (! empty($validated['type'])) {
                    $updateData['type'] = $validated['type'];
                }
                $itemGroup->update($updateData);
            } else {
                $allItems = Item::query()->get();
                $groups = $this->itemGroupService->buildGroups($allItems, false)['groups'];
                $matched = $this->itemGroupService->findGroup($groups, $oldSlug);
                if ($matched) {
                    $oldLabel = $matched['label'];
                }

                ItemGroup::create([
                    'name' => $cleanName,
                    'slug' => $newSlug,
                    'type' => $validated['type'] ?? 'aset',
                    'created_by' => Auth::id(),
                ]);
            }

            // Update category on items
            Item::where('category', $oldLabel)->update(['category' => $cleanName]);

            // Also update any items where canonicalKey matches oldSlug
            $items = Item::whereNotNull('category')->get();
            foreach ($items as $item) {
                if ($this->itemGroupService->canonicalKey((string) $item->category) === $oldSlug) {
                    $item->update(['category' => $cleanName]);
                }
            }
        });

        return redirect()->route('items.index')
            ->with('success', "Nama kelompok berhasil diubah menjadi \"{$cleanName}\".");
    }

    /**
     * Tampilkan form create item baru.
     */
    public function create(Request $request)
    {
        $categorySuggestions = $this->itemGroupService->distinctCategorySuggestions();
        $preselectedCategory = $this->itemGroupService->cleanCategory($request->query('category'));
        $returnGroup = trim((string) ($request->query('return_group') ?: $request->query('group', '')));
        $preselectedType = 'aset';

        if ($returnGroup !== '') {
            $allItems = Item::query()->orderBy('name')->orderBy('item_code')->get();
            $groups = $this->itemGroupService->buildGroups($allItems, false)['groups'];
            $matched = $this->itemGroupService->findGroup($groups, $returnGroup);
            if ($matched !== null) {
                if ($preselectedCategory === null) {
                    $preselectedCategory = $matched['label'];
                }
                $preselectedType = $matched['type'] ?? 'aset';
            }
        }

        $isLockedGroup = filled($returnGroup) && filled($preselectedCategory);

        return view('items.create', compact(
            'categorySuggestions',
            'preselectedCategory',
            'preselectedType',
            'returnGroup',
            'isLockedGroup'
        ));
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
            'category' => 'nullable|string|max:80',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
            'preventive_interval_months' => 'nullable|integer|min:1|max:60',
            'preventive_last_serviced_at' => 'nullable|date',
        ], [
            'item_code.required' => 'Kode barang wajib diisi.',
            'item_code.unique' => 'Kode barang ini sudah digunakan oleh barang aktif.',
            'epc_tag.unique' => 'Tag EPC RFID ini sudah terdaftar pada barang lain.',
            'name.required' => 'Nama barang wajib diisi.',
            'type.required' => 'Tipe barang wajib dipilih.',
            'location.required' => 'Lokasi penempatan barang wajib diisi.',
        ]);

        $validated['status'] = $validated['status'] ?? 'tersedia';

        if ($validated['type'] === 'aset') {
            $validated['stock'] = 1;
        } else {
            $validated['stock'] = max(1, (int) ($validated['stock'] ?? 1));
        }

        $validated['category'] = $this->itemGroupService->cleanCategory($validated['category'] ?? null);

        $itemAttributes = Arr::only($validated, [
            'item_code',
            'epc_tag',
            'name',
            'category',
            'type',
            'stock',
            'status',
            'location',
        ]);

        $intervalMonths = filled($validated['preventive_interval_months'] ?? null)
            ? (int) $validated['preventive_interval_months']
            : null;
        $lastServicedAt = filled($validated['preventive_last_serviced_at'] ?? null)
            ? Carbon::parse($validated['preventive_last_serviced_at'])->startOfDay()
            : Carbon::today();

        $existingTrashed = Item::onlyTrashed()->where('item_code', $itemAttributes['item_code'])->first();
        $isRestored = false;

        try {
            DB::transaction(function () use ($itemAttributes, $intervalMonths, $lastServicedAt, $existingTrashed, &$isRestored) {
                // If EPC tag was used on another trashed item, detach it to prevent unique index violation
                if (! empty($itemAttributes['epc_tag'])) {
                    Item::onlyTrashed()
                        ->where('epc_tag', $itemAttributes['epc_tag'])
                        ->when($existingTrashed, fn ($q) => $q->where('id', '!=', $existingTrashed->id))
                        ->update(['epc_tag' => null]);
                }

                if ($existingTrashed) {
                    $existingTrashed->restore();
                    $existingTrashed->update($itemAttributes);
                    $item = $existingTrashed;
                    $isRestored = true;
                } else {
                    $item = Item::create($itemAttributes);
                }

                if ($item->type === ItemType::BAHAN_HABIS_PAKAI && $item->stock > 0 && Auth::id()) {
                    StockMovement::create([
                        'item_id' => $item->id,
                        'user_id' => Auth::id(),
                        'type' => StockMovementType::IN,
                        'quantity' => $item->stock,
                        'stock_before' => 0,
                        'stock_after' => $item->stock,
                        'description' => $isRestored ? 'Stok pemulihan barang habis pakai' : 'Stok awal pendaftaran barang habis pakai',
                    ]);
                }

                if ($intervalMonths !== null) {
                    $nextDueDate = PreventiveSchedule::calculateNextDueDate($lastServicedAt, $intervalMonths);

                    PreventiveSchedule::create([
                        'item_id' => $item->id,
                        'title' => Str::limit('Perawatan Berkala — '.$item->name, 150, ''),
                        'interval_months' => $intervalMonths,
                        'last_serviced_at' => $lastServicedAt->toDateString(),
                        'maintenance_date' => $lastServicedAt->toDateString(),
                        'quantity' => max(1, (int) $item->stock),
                        'safe_quantity' => max(1, (int) $item->stock),
                        'damaged_quantity' => 0,
                        'next_due_date' => $nextDueDate->toDateString(),
                        'status' => 'scheduled',
                        'workflow_stage' => 'scheduled',
                        'created_by' => Auth::id(),
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'items_item_code_unique') || str_contains($msg, '1062 Duplicate entry')) {
                throw ValidationException::withMessages([
                    'item_code' => "Kode barang \"{$itemAttributes['item_code']}\" sudah terdaftar di sistem. Silakan gunakan kode unik lain.",
                ]);
            }
            if (str_contains($msg, 'items_epc_tag_unique')) {
                throw ValidationException::withMessages([
                    'epc_tag' => "Tag EPC RFID \"{$itemAttributes['epc_tag']}\" sudah terdaftar pada perangkat lain.",
                ]);
            }
            throw ValidationException::withMessages([
                'item_code' => 'Data identitas unik barang (kode atau tag EPC) bertabrakan dengan data yang sudah ada.',
            ]);
        }

        $successMessage = $isRestored
            ? "Item '{$itemAttributes['item_code']}' berhasil dipulihkan kembali ke katalog master."
            : ($intervalMonths !== null
                ? 'Item dan jadwal maintenance berkala berhasil ditambahkan.'
                : 'Item berhasil ditambahkan.');

        $returnGroup = trim((string) $request->input('return_group', ''));
        if ($returnGroup !== '') {
            return redirect()->route('items.index', ['group' => $returnGroup])
                ->with('success', $successMessage);
        }

        return redirect()->route('items.index')
            ->with('success', $successMessage);
    }

    /**
     * Tampilkan detail item.
     */
    public function show(string $id)
    {
        $item = Item::with([
            'loans.user',
            'tickets.reportedBy',
            'stockMovements.user',
            'preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date'),
        ])->findOrFail($id);

        return view('items.show', compact('item'));
    }

    /**
     * Tampilkan form edit item.
     */
    public function edit(Request $request, string $id)
    {
        $item = Item::findOrFail($id);
        $categorySuggestions = $this->itemGroupService->distinctCategorySuggestions();
        $returnGroup = trim((string) $request->query('return_group', ''));

        return view('items.edit', compact('item', 'categorySuggestions', 'returnGroup'));
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
            'category' => 'nullable|string|max:80',
            'type' => 'required|in:aset,bahan_habis_pakai',
            'stock' => 'nullable|integer|min:0',
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
        ], [
            'item_code.required' => 'Kode barang wajib diisi.',
            'item_code.unique' => 'Kode barang ini sudah digunakan oleh barang aktif lain.',
            'epc_tag.unique' => 'Tag EPC RFID ini sudah terdaftar pada barang lain.',
            'name.required' => 'Nama barang wajib diisi.',
            'type.required' => 'Tipe barang wajib dipilih.',
            'status.required' => 'Status barang wajib dipilih.',
            'location.required' => 'Lokasi penempatan barang wajib diisi.',
        ]);

        // Prevent updating item_code to match a trashed item (different item)
        if (Item::onlyTrashed()->where('item_code', $validated['item_code'])->where('id', '!=', $id)->exists()) {
            throw ValidationException::withMessages([
                'item_code' => "Kode barang \"{$validated['item_code']}\" sudah tercatat dalam riwayat barang terhapus/afkir. Gunakan kode lain.",
            ]);
        }

        $validated['category'] = $this->itemGroupService->cleanCategory($validated['category'] ?? null);

        try {
            DB::transaction(function () use ($id, $validated) {
                // If EPC tag was used on a trashed item, detach it to avoid unique key violation
                if (! empty($validated['epc_tag'])) {
                    Item::onlyTrashed()
                        ->where('epc_tag', $validated['epc_tag'])
                        ->where('id', '!=', $id)
                        ->update(['epc_tag' => null]);
                }

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
        } catch (UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'items_item_code_unique') || str_contains($msg, '1062 Duplicate entry')) {
                throw ValidationException::withMessages([
                    'item_code' => "Kode barang \"{$validated['item_code']}\" sudah terdaftar di sistem. Gunakan kode unik lain.",
                ]);
            }
            if (str_contains($msg, 'items_epc_tag_unique')) {
                throw ValidationException::withMessages([
                    'epc_tag' => "Tag EPC RFID \"{$validated['epc_tag']}\" sudah terdaftar pada perangkat lain.",
                ]);
            }
            throw ValidationException::withMessages([
                'item_code' => 'Data identitas unik barang bertabrakan dengan data lain.',
            ]);
        }

        $returnGroup = trim((string) $request->input('return_group', ''));
        if ($returnGroup !== '') {
            $redirectKey = filled($validated['category'])
                ? $this->itemGroupService->canonicalKey($validated['category'])
                : $this->itemGroupService->canonicalKey($this->itemGroupService->normalizeItemBaseName($validated['name']));

            return redirect()->route('items.index', ['group' => $redirectKey])
                ->with('success', 'Item berhasil diperbarui.');
        }

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil diperbarui.');
    }

    /**
     * Tetapkan kelompok / kategori secara massal ke beberapa barang terpilih di Master Aset.
     */
    public function bulkUpdateCategory(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'required|integer|exists:items,id',
            'category' => 'nullable|string|max:80',
        ], [
            'item_ids.required' => 'Pilih minimal satu barang terlebih dahulu.',
            'item_ids.min' => 'Pilih minimal satu barang terlebih dahulu.',
        ]);

        $cleanedCategory = $this->itemGroupService->cleanCategory($validated['category'] ?? null);

        $updatedCount = DB::transaction(function () use ($validated, $cleanedCategory) {
            return Item::query()
                ->whereIn('id', $validated['item_ids'])
                ->update(['category' => $cleanedCategory]);
        });

        if ($cleanedCategory !== null) {
            return redirect()->route('items.index', ['group' => $this->itemGroupService->canonicalKey($cleanedCategory)])
                ->with('success', "{$updatedCount} barang dimasukkan ke kelompok \"{$cleanedCategory}\".");
        }

        return redirect()->route('items.index')
            ->with('success', "Kelompok pada {$updatedCount} barang dikosongkan (otomatis berdasarkan nama).");
    }

    /**
     * Hapus item (soft delete jika berelasi riwayat, force delete jika belum pernah digunakan).
     */
    public function destroy(string $id)
    {
        $item = Item::findOrFail($id);

        $hasHistory = $item->loans()->exists()
            || $item->tickets()->exists()
            || $item->preventiveSchedules()->exists()
            || $item->stockMovements()->exists()
            || DamagedAsset::where('item_id', $item->id)->exists();

        if ($hasHistory) {
            $item->delete();
        } else {
            $item->forceDelete();
        }

        return redirect()->route('items.index')
            ->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Cetak label barcode barang (halaman print-friendly).
     */
    public function printBarcodes(Request $request)
    {
        if ($request->has('ids')) {
            $idList = array_filter(array_map('intval', explode(',', (string) $request->get('ids', ''))));
            $items = empty($idList)
                ? collect()
                : Item::whereIn('id', $idList)->orderBy('name')->orderBy('item_code')->get();
        } else {
            $items = Item::orderBy('name')->orderBy('item_code')->get();
        }

        return view('items.barcodes', compact('items'));
    }

    private function applySearchFilter($query, string $search): void
    {
        $compact = preg_replace('/[^a-zA-Z0-9]/', '', $search);

        $query->where(function ($inner) use ($search, $compact) {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('item_code', 'like', "%{$search}%")
                ->orWhere('epc_tag', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");

            if ($compact !== '' && $compact !== $search) {
                $inner->orWhere('name', 'like', "%{$compact}%")
                    ->orWhere('category', 'like', "%{$compact}%");
            }
        });
    }
}
