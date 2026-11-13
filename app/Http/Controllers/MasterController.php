<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\PreventiveSchedule;
use App\Models\StockMovement;
use App\Services\ItemGroupService;
use Carbon\Carbon;
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

        DB::transaction(function () use ($cleanName, $slug) {
            ItemGroup::create([
                'name' => $cleanName,
                'slug' => $slug,
                'created_by' => Auth::id(),
            ]);
        });

        return redirect()->route('items.index')
            ->with('success', "Kelompok barang \"{$cleanName}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan form create item baru.
     */
    public function create(Request $request)
    {
        $categorySuggestions = $this->itemGroupService->distinctCategorySuggestions();
        $preselectedCategory = $this->itemGroupService->cleanCategory($request->query('category'));
        $returnGroup = trim((string) ($request->query('return_group') ?: $request->query('group', '')));

        if ($preselectedCategory === null && $returnGroup !== '') {
            $allItems = Item::query()->orderBy('name')->orderBy('item_code')->get();
            $groups = $this->itemGroupService->buildGroups($allItems, false)['groups'];
            $matched = $this->itemGroupService->findGroup($groups, $returnGroup);
            if ($matched !== null) {
                $preselectedCategory = $matched['label'];
            }
        }

        $isLockedGroup = filled($returnGroup) && filled($preselectedCategory);

        return view('items.create', compact(
            'categorySuggestions',
            'preselectedCategory',
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
            'status' => 'required|in:tersedia,dipinjam,under_maintenance',
            'location' => 'required|string|max:100',
            'preventive_interval_months' => 'nullable|integer|min:1|max:60',
            'preventive_last_serviced_at' => 'nullable|date',
        ]);

        if ($validated['type'] === 'aset') {
            $validated['stock'] = 1;
        } else {
            $validated['stock'] = (int) ($validated['stock'] ?? 0);
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

        DB::transaction(function () use ($itemAttributes, $intervalMonths, $lastServicedAt) {
            $item = Item::create($itemAttributes);

            if ($item->type === ItemType::BAHAN_HABIS_PAKAI && $item->stock > 0 && Auth::id()) {
                StockMovement::create([
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'type' => StockMovementType::IN,
                    'quantity' => $item->stock,
                    'stock_before' => 0,
                    'stock_after' => $item->stock,
                    'description' => 'Stok awal pendaftaran barang habis pakai',
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

        $successMessage = $intervalMonths !== null
            ? 'Item dan jadwal maintenance berkala berhasil ditambahkan.'
            : 'Item berhasil ditambahkan.';

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
        ]);

        $validated['category'] = $this->itemGroupService->cleanCategory($validated['category'] ?? null);

        DB::transaction(function () use ($id, $validated) {
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
     * Hapus item (soft delete).
     */
    public function destroy(string $id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

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
