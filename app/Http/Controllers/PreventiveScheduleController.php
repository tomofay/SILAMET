<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\TicketStatus;
use App\Jobs\SendMaintenanceTicketTelegramJob;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Services\DamagedAssetService;
use App\Services\ItemGroupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PreventiveScheduleController extends Controller
{
    public function __construct(
        private readonly ItemGroupService $itemGroupService,
        private readonly DamagedAssetService $damagedAssetService,
    ) {}

    /**
     * Halaman 1: Pembuatan Jadwal Maintenance Barang (pilih 1 nama/kelompok barang Master Aset, tanggal, dll).
     */
    public function index(Request $request)
    {
        $masterItems = Item::query()
            ->with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
            ->orderBy('name')
            ->orderBy('item_code')
            ->get();

        $unscheduledItems = $masterItems
            ->filter(fn (Item $item) => $item->preventiveSchedules->isEmpty())
            ->values();

        $unscheduledAsetItems = $unscheduledItems
            ->filter(fn (Item $item) => $item->type === ItemType::ASET)
            ->values();

        $workflowCounts = $this->workflowCounts($masterItems->count(), $unscheduledItems->count());

        $coverageStats = [
            'total_items' => $masterItems->count(),
            'unscheduled_items' => $unscheduledItems->count(),
            'unscheduled_aset_items' => $unscheduledAsetItems->count(),
        ];

        $groupData = $this->itemGroupService->buildGroups($masterItems, true, false);
        $itemGroups = $groupData['groups'];

        $requestedItemId = $request->query('item_id');
        $preselectedItemId = is_numeric($requestedItemId) && $masterItems->contains('id', (int) $requestedItemId)
            ? (int) $requestedItemId
            : null;

        $preselectedGroupKey = '';
        $requestedGroup = trim((string) $request->query('group', ''));
        if ($requestedGroup !== '') {
            $matchedGroup = $this->itemGroupService->findGroup($itemGroups, $requestedGroup);
            if ($matchedGroup !== null) {
                $preselectedGroupKey = $matchedGroup['key'];
            }
        }

        if ($preselectedGroupKey === '' && $preselectedItemId !== null) {
            $preselectedGroupKey = $groupData['item_to_group'][$preselectedItemId] ?? '';
        }

        return view('maintenance.schedules.index', compact(
            'masterItems',
            'unscheduledItems',
            'itemGroups',
            'coverageStats',
            'workflowCounts',
            'preselectedItemId',
            'preselectedGroupKey'
        ));
    }

    /**
     * Halaman 2: Barang Sedang Di-Maintenance (pemilahan barang aman vs mengalami kerusakan).
     */
    public function inProgress(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $query = PreventiveSchedule::query()
            ->with(['item', 'createdBy'])
            ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
            ->where('workflow_stage', 'in_maintenance')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('item_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('maintenance_date')
            ->latest('id');

        $schedules = $query->paginate(15)->withQueryString();
        $workflowCounts = $this->workflowCounts();

        return view('maintenance.schedules.in-progress', compact('schedules', 'workflowCounts', 'search'));
    }

    /**
     * Halaman 3: Barang Aman Hasil Maintenance & Pengingat Preventif Berikutnya.
     */
    public function safe(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $today = Carbon::today();
        $dueSoonLimit = $today->copy()->addDays(7);

        $baseQuery = PreventiveSchedule::query()
            ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
            ->whereIn('workflow_stage', ['safe', 'scheduled']);

        $query = (clone $baseQuery)
            ->with(['item', 'inspectedBy'])
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('next_due_date')
            ->latest('inspected_at');

        if ($filter === 'overdue') {
            $query->where('status', 'scheduled')->whereDate('next_due_date', '<', $today);
        } elseif ($filter === 'due_soon') {
            $query->where('status', 'scheduled')
                ->whereDate('next_due_date', '>=', $today)
                ->whereDate('next_due_date', '<=', $dueSoonLimit);
        } elseif ($filter === 'paused') {
            $query->where('status', 'paused');
        }

        $schedules = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'overdue' => (clone $baseQuery)->where('status', 'scheduled')->whereDate('next_due_date', '<', $today)->count(),
            'due_soon' => (clone $baseQuery)->where('status', 'scheduled')
                ->whereDate('next_due_date', '>=', $today)
                ->whereDate('next_due_date', '<=', $dueSoonLimit)
                ->count(),
            'paused' => (clone $baseQuery)->where('status', 'paused')->count(),
        ];

        $workflowCounts = $this->workflowCounts();

        return view('maintenance.schedules.safe', compact('schedules', 'counts', 'filter', 'workflowCounts'));
    }

    /**
     * Halaman 4: Barang Perlu Diperbaiki (dapat diajukan langsung ke Tiket Kerusakan M&R).
     */
    public function repairs(Request $request)
    {
        $filter = $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'pending', 'submitted'], true)) {
            $filter = 'all';
        }

        $query = PreventiveSchedule::query()
            ->with(['item', 'inspectedBy', 'maintenanceTicket'])
            ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
            ->whereIn('workflow_stage', ['needs_repair', 'ticket_created'])
            ->orderByRaw("CASE WHEN workflow_stage = 'needs_repair' THEN 0 ELSE 1 END")
            ->latest('inspected_at')
            ->latest('id');

        if ($filter === 'pending') {
            $query->where('workflow_stage', 'needs_repair');
        } elseif ($filter === 'submitted') {
            $query->where('workflow_stage', 'ticket_created');
        }

        $schedules = $query->paginate(15)->withQueryString();
        $workflowCounts = $this->workflowCounts();

        return view('maintenance.schedules.repairs', compact('schedules', 'filter', 'workflowCounts'));
    }

    public function create(Request $request)
    {
        $items = Item::query()
            ->with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
            ->orderBy('name')
            ->orderBy('item_code')
            ->get();

        $requestedItemId = $request->query('item_id');
        $preselectedItemId = is_numeric($requestedItemId) && $items->contains('id', (int) $requestedItemId)
            ? (int) $requestedItemId
            : null;

        return view('maintenance.schedules.create', compact('items', 'preselectedItemId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'selected_group' => 'nullable|string|max:120',
            'item_id' => 'required_without_all:item_ids,selected_group|nullable|exists:items,id',
            'item_ids' => 'required_without_all:item_id,selected_group|nullable|array|min:1',
            'item_ids.*' => 'required|integer|exists:items,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:1|max:10000',
            'group_label' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:150',
            'interval_months' => 'nullable|integer|min:1|max:60',
            'maintenance_date' => 'required_without:last_serviced_at|nullable|date',
            'last_serviced_at' => 'required_without:maintenance_date|nullable|date',
            'quantity' => 'nullable|integer|min:1|max:10000',
            'status' => 'nullable|in:scheduled,paused',
            'notes' => 'nullable|string|max:2000',
        ], [
            'item_id.required_without_all' => 'Pilih paket nama barang dari Master Aset terlebih dahulu.',
            'item_id.exists' => 'Barang Master Aset yang dipilih tidak ditemukan.',
            'item_ids.required_without_all' => 'Pilih paket nama barang dari Master Aset terlebih dahulu.',
            'item_ids.min' => 'Paket barang terpilih belum memiliki unit untuk dimaintenance.',
            'maintenance_date.required_without' => 'Tanggal jadwal maintenance wajib diisi.',
            'last_serviced_at.required_without' => 'Tanggal jadwal maintenance wajib diisi.',
            'quantity.min' => 'Jumlah barang yang dimaintenance minimal 1.',
        ]);

        $targetIds = collect($validated['item_ids'] ?? [])
            ->when(empty($validated['item_ids']) && ! empty($validated['item_id']), fn ($c) => $c->push((int) $validated['item_id']))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $resolvedGroupLabel = filled($validated['group_label'] ?? null)
            ? trim((string) $validated['group_label'])
            : null;

        if ($targetIds->isEmpty() && filled($validated['selected_group'] ?? null)) {
            $allItems = Item::query()
                ->orderBy('name')
                ->orderBy('item_code')
                ->get();
            $groupData = $this->itemGroupService->buildGroups($allItems, true);
            $matchedGroup = $this->itemGroupService->findGroup($groupData['groups'], $validated['selected_group']);

            if ($matchedGroup !== null) {
                $targetIds = collect($matchedGroup['item_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $resolvedGroupLabel ??= (string) $matchedGroup['label'];
            }
        }

        $targetItems = Item::query()
            ->whereIn('id', $targetIds)
            ->orderBy('name')
            ->orderBy('item_code')
            ->get();

        if ($targetItems->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_group' => 'Paket atau barang Master Aset yang dipilih tidak ditemukan.',
            ]);
        }

        $effectiveDate = Carbon::parse($validated['maintenance_date'] ?? $validated['last_serviced_at'])->toDateString();
        $intervalMonths = (int) ($validated['interval_months'] ?? 3);
        $nextDueDate = PreventiveSchedule::calculateNextDueDate($effectiveDate, $intervalMonths);
        $customTitle = filled($validated['title'] ?? null) ? trim((string) $validated['title']) : null;
        $quantities = $validated['quantities'] ?? [];
        $status = $validated['status'] ?? 'scheduled';
        $notes = filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null;

        $createdCount = DB::transaction(function () use (
            $targetItems,
            $customTitle,
            $resolvedGroupLabel,
            $intervalMonths,
            $effectiveDate,
            $nextDueDate,
            $quantities,
            $validated,
            $status,
            $notes
        ) {
            $count = 0;

            foreach ($targetItems as $item) {
                $qty = isset($quantities[$item->id])
                    ? max(1, (int) $quantities[$item->id])
                    : ($targetItems->count() === 1 && isset($validated['quantity'])
                        ? max(1, (int) $validated['quantity'])
                        : max(1, (int) $item->stock));

                $title = $customTitle !== null
                    ? Str::limit($customTitle, 150, '')
                    : ($resolvedGroupLabel !== null
                        ? Str::limit('Maintenance — '.$resolvedGroupLabel, 150, '')
                        : Str::limit('Maintenance — '.$item->name, 150, ''));

                PreventiveSchedule::create([
                    'item_id' => $item->id,
                    'title' => $title,
                    'interval_months' => $intervalMonths,
                    'last_serviced_at' => $effectiveDate,
                    'maintenance_date' => $effectiveDate,
                    'quantity' => $qty,
                    'safe_quantity' => 0,
                    'damaged_quantity' => 0,
                    'next_due_date' => $nextDueDate->toDateString(),
                    'status' => $status,
                    'workflow_stage' => 'in_maintenance',
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                ]);

                $count++;
            }

            return $count;
        });

        if ($request->boolean('go_to_progress')) {
            if ($createdCount > 1) {
                $groupText = $resolvedGroupLabel !== null
                    ? '"'.$resolvedGroupLabel."\" ({$createdCount} unit/item)"
                    : "{$createdCount} barang Master Aset";

                return redirect()->route('maintenance.schedules.in-progress')
                    ->with('success', "Seluruh paket {$groupText} berhasil dimasukkan ke tahap Sedang Di-Maintenance. Silakan pilah kondisi tiap unit.");
            }

            $singleItem = $targetItems->first();
            $singleQty = isset($quantities[$singleItem->id])
                ? max(1, (int) $quantities[$singleItem->id])
                : (int) ($validated['quantity'] ?? max(1, (int) $singleItem->stock));

            return redirect()->route('maintenance.schedules.in-progress')
                ->with('success', "Jadwal maintenance \"{$singleItem->name}\" ({$singleQty} unit) masuk ke tahap Sedang Di-Maintenance. Silakan pilah hasilnya setelah pemeriksaan.");
        }

        return redirect()->route('maintenance.schedules.index')
            ->with('success', 'Jadwal perawatan preventif berhasil dibuat.');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:all,aset',
            'interval_months' => 'required|integer|min:1|max:60',
            'maintenance_date' => 'required_without:last_serviced_at|nullable|date',
            'last_serviced_at' => 'required_without:maintenance_date|nullable|date',
            'quantity_mode' => 'nullable|in:master_stock,fixed',
            'fixed_quantity' => 'nullable|integer|min:1|max:10000',
            'title_prefix' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:2000',
        ]);

        $scope = $validated['scope'];
        $intervalMonths = (int) $validated['interval_months'];
        $effectiveDate = Carbon::parse($validated['maintenance_date'] ?? $validated['last_serviced_at'])->toDateString();
        $nextDueDate = PreventiveSchedule::calculateNextDueDate($effectiveDate, $intervalMonths)->toDateString();
        $quantityMode = $validated['quantity_mode'] ?? 'master_stock';
        $fixedQuantity = max(1, (int) ($validated['fixed_quantity'] ?? 1));
        $prefix = trim((string) ($validated['title_prefix'] ?? ''));
        if ($prefix === '') {
            $prefix = 'Maintenance Berkala';
        }
        $notes = filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null;
        $workflowStage = $request->boolean('go_to_progress') ? 'in_maintenance' : 'scheduled';

        $createdCount = DB::transaction(function () use (
            $scope,
            $intervalMonths,
            $effectiveDate,
            $nextDueDate,
            $quantityMode,
            $fixedQuantity,
            $prefix,
            $notes,
            $workflowStage
        ) {
            $itemsToSchedule = Item::query()
                ->when($scope === 'aset', fn ($q) => $q->where('type', ItemType::ASET->value))
                ->whereDoesntHave('preventiveSchedules')
                ->lockForUpdate()
                ->orderBy('name')
                ->orderBy('item_code')
                ->get();

            $created = 0;

            foreach ($itemsToSchedule as $item) {
                $qty = $quantityMode === 'fixed'
                    ? $fixedQuantity
                    : max(1, (int) $item->stock);

                PreventiveSchedule::create([
                    'item_id' => $item->id,
                    'title' => Str::limit("{$prefix} — {$item->name}", 150, ''),
                    'interval_months' => $intervalMonths,
                    'last_serviced_at' => $effectiveDate,
                    'maintenance_date' => $effectiveDate,
                    'quantity' => $qty,
                    'safe_quantity' => $workflowStage === 'scheduled' ? $qty : 0,
                    'damaged_quantity' => 0,
                    'next_due_date' => $nextDueDate,
                    'status' => 'scheduled',
                    'workflow_stage' => $workflowStage,
                    'notes' => $notes,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            }

            return $created;
        });

        if ($createdCount === 0) {
            $emptyMessage = $scope === 'aset'
                ? 'Seluruh barang bertipe Aset di Master Aset sudah memiliki jadwal maintenance.'
                : 'Seluruh barang di Master Aset sudah memiliki jadwal maintenance.';

            return redirect()->route($request->boolean('go_to_progress') ? 'maintenance.schedules.in-progress' : 'maintenance.schedules.index')
                ->with('success', $emptyMessage);
        }

        if ($request->boolean('go_to_progress')) {
            return redirect()->route('maintenance.schedules.in-progress')
                ->with('success', "Berhasil menjadwalkan {$createdCount} barang Master Aset ke tahap Sedang Di-Maintenance.");
        }

        return redirect()->route('maintenance.schedules.index')
            ->with('success', "Berhasil membuat {$createdCount} jadwal maintenance barang Master Aset.");
    }

    /**
     * Pilah barang yang sedang di-maintenance menjadi Aman dan/atau Mengalami Kerusakan.
     */
    public function triage(Request $request, string $id)
    {
        $validated = $request->validate([
            'mode' => 'nullable|in:all_safe,all_damaged,split',
            'damage_disposition' => 'nullable|in:needs_repair,broken',
            'damaged_quantity' => 'nullable|integer|min:0|max:10000',
            'safe_quantity' => 'nullable|integer|min:0|max:10000',
            'damage_notes' => 'nullable|string|max:2000',
            'safe_notes' => 'nullable|string|max:2000',
        ]);

        $damageDisposition = ($validated['damage_disposition'] ?? 'needs_repair') === 'broken'
            ? 'broken'
            : 'needs_repair';

        $outcome = DB::transaction(function () use ($id, $validated, $request, $damageDisposition) {
            $schedule = PreventiveSchedule::query()->with('item')->lockForUpdate()->findOrFail($id);
            $totalQty = max(1, (int) $schedule->quantity);
            $mode = $validated['mode'] ?? null;

            if ($mode === 'all_safe') {
                $safeQty = $totalQty;
                $damagedQty = 0;
            } elseif ($mode === 'all_damaged') {
                $safeQty = 0;
                $damagedQty = $totalQty;
            } else {
                $damagedQty = (int) ($validated['damaged_quantity'] ?? $totalQty);

                if ($request->filled('safe_quantity')) {
                    $safeQty = (int) $validated['safe_quantity'];
                } else {
                    $safeQty = $totalQty - $damagedQty;
                }

                if ($damagedQty < 0 || $damagedQty > $totalQty || $safeQty < 0 || ($safeQty + $damagedQty) !== $totalQty) {
                    throw ValidationException::withMessages([
                        'damaged_quantity' => "Total jumlah aman + rusak harus sama dengan {$totalQty} unit.",
                    ]);
                }
            }

            $damageNotes = trim((string) ($validated['damage_notes'] ?? ''));
            if ($damagedQty > 0 && $damageNotes === '') {
                throw ValidationException::withMessages([
                    'damage_notes' => 'Catatan kerusakan wajib diisi untuk barang yang mengalami kerusakan.',
                ]);
            }

            $safeNotes = trim((string) ($validated['safe_notes'] ?? ''));
            $baseDate = ($schedule->maintenance_date ?? Carbon::today())->toDateString();
            $nextDueDate = PreventiveSchedule::calculateNextDueDate(
                $baseDate,
                max(1, (int) $schedule->interval_months)
            )->toDateString();

            $itemName = $schedule->item?->name ?? 'Barang';

            // Kasus 1: Seluruh barang aman -> pindah ke Page Barang Aman
            if ($damagedQty === 0) {
                $schedule->update([
                    'workflow_stage' => 'safe',
                    'safe_quantity' => $totalQty,
                    'damaged_quantity' => 0,
                    'damage_notes' => null,
                    'notes' => $safeNotes !== '' ? $safeNotes : $schedule->notes,
                    'status' => 'scheduled',
                    'last_serviced_at' => $baseDate,
                    'next_due_date' => $nextDueDate,
                    'last_notified_stage' => null,
                    'last_notified_at' => null,
                    'inspected_at' => now(),
                    'inspected_by' => auth()->id(),
                ]);

                return [
                    'route' => 'maintenance.schedules.safe',
                    'message' => "\"{$itemName}\" ({$totalQty} unit) dinyatakan Aman dan dipindahkan ke halaman Barang Aman.",
                ];
            }

            // Kasus 2: Seluruh barang rusak -> Perlu Diperbaiki ATAU langsung Rusak Permanen (kurangi Master Aset)
            if ($safeQty === 0) {
                if ($damageDisposition === 'broken') {
                    $writeOff = $schedule->item_id
                        ? $this->damagedAssetService->recordAndReduceMasterAsset([
                            'item_id' => $schedule->item_id,
                            'quantity' => $totalQty,
                            'source' => 'preventive_maintenance',
                            'preventive_schedule_id' => $schedule->id,
                            'damage_reason' => $damageNotes,
                            'skip_schedule_ids' => [$schedule->id],
                        ])
                        : null;

                    $schedule->update([
                        'workflow_stage' => 'written_off',
                        'safe_quantity' => 0,
                        'damaged_quantity' => $totalQty,
                        'damage_notes' => $damageNotes,
                        'status' => 'paused',
                        'inspected_at' => now(),
                        'inspected_by' => auth()->id(),
                    ]);

                    $retiredQty = (int) ($writeOff['retired_qty'] ?? $totalQty);
                    $msg = ! empty($writeOff['item_deleted'])
                        ? "\"{$itemName}\" ({$retiredQty} unit) dinyatakan Rusak Permanen, dicatat di Barang Rusak, dan dihapus dari Master Aset."
                        : "\"{$itemName}\" ({$retiredQty} unit) dinyatakan Rusak Permanen dan masuk ke Barang Rusak. Stok Master Aset berkurang menjadi ".($writeOff['remaining_stock'] ?? 0).' unit.';

                    return [
                        'route' => 'maintenance.damaged.index',
                        'message' => $msg,
                    ];
                }

                $schedule->update([
                    'workflow_stage' => 'needs_repair',
                    'safe_quantity' => 0,
                    'damaged_quantity' => $totalQty,
                    'damage_notes' => $damageNotes,
                    'status' => 'paused',
                    'inspected_at' => now(),
                    'inspected_by' => auth()->id(),
                ]);

                return [
                    'route' => 'maintenance.schedules.repairs',
                    'message' => "\"{$itemName}\" ({$totalQty} unit) mengalami kerusakan dan masuk ke halaman Perlu Diperbaiki.",
                ];
            }

            // Kasus 3: Sebagian aman & sebagian rusak -> pecah bersih ke Aman + (Perlu Diperbaiki atau Barang Rusak Permanen)
            $schedule->update([
                'workflow_stage' => 'safe',
                'quantity' => $safeQty,
                'safe_quantity' => $safeQty,
                'damaged_quantity' => 0,
                'damage_notes' => null,
                'notes' => $safeNotes !== '' ? $safeNotes : $schedule->notes,
                'status' => 'scheduled',
                'last_serviced_at' => $baseDate,
                'next_due_date' => $nextDueDate,
                'last_notified_stage' => null,
                'last_notified_at' => null,
                'inspected_at' => now(),
                'inspected_by' => auth()->id(),
            ]);

            if ($damageDisposition === 'broken') {
                $brokenSplit = PreventiveSchedule::create([
                    'item_id' => $schedule->item_id,
                    'parent_schedule_id' => $schedule->id,
                    'title' => $schedule->title,
                    'interval_months' => $schedule->interval_months,
                    'last_serviced_at' => $baseDate,
                    'maintenance_date' => $baseDate,
                    'next_due_date' => $nextDueDate,
                    'quantity' => $damagedQty,
                    'safe_quantity' => 0,
                    'damaged_quantity' => $damagedQty,
                    'notes' => $schedule->notes,
                    'damage_notes' => $damageNotes,
                    'status' => 'paused',
                    'workflow_stage' => 'written_off',
                    'created_by' => $schedule->created_by,
                    'inspected_at' => now(),
                    'inspected_by' => auth()->id(),
                ]);

                $writeOff = $schedule->item_id
                    ? $this->damagedAssetService->recordAndReduceMasterAsset([
                        'item_id' => $schedule->item_id,
                        'quantity' => $damagedQty,
                        'source' => 'preventive_maintenance',
                        'preventive_schedule_id' => $brokenSplit->id,
                        'damage_reason' => $damageNotes,
                        'skip_schedule_ids' => [$schedule->id, $brokenSplit->id],
                    ])
                    : null;

                $retiredQty = (int) ($writeOff['retired_qty'] ?? $damagedQty);
                $assetImpact = ! empty($writeOff['item_deleted'])
                    ? 'dan dihapus dari Master Aset.'
                    : 'Stok Master Aset tersisa '.($writeOff['remaining_stock'] ?? 0).' unit.';

                return [
                    'route' => 'maintenance.damaged.index',
                    'message' => "Hasil pemilahan \"{$itemName}\" disimpan: {$safeQty} unit masuk ke Barang Aman, sedangkan {$retiredQty} unit masuk ke Barang Rusak Permanen ({$assetImpact})",
                ];
            }

            PreventiveSchedule::create([
                'item_id' => $schedule->item_id,
                'parent_schedule_id' => $schedule->id,
                'title' => $schedule->title,
                'interval_months' => $schedule->interval_months,
                'last_serviced_at' => $baseDate,
                'maintenance_date' => $baseDate,
                'next_due_date' => $nextDueDate,
                'quantity' => $damagedQty,
                'safe_quantity' => 0,
                'damaged_quantity' => $damagedQty,
                'notes' => $schedule->notes,
                'damage_notes' => $damageNotes,
                'status' => 'paused',
                'workflow_stage' => 'needs_repair',
                'created_by' => $schedule->created_by,
                'inspected_at' => now(),
                'inspected_by' => auth()->id(),
            ]);

            return [
                'route' => 'maintenance.schedules.repairs',
                'message' => "Hasil pemilahan \"{$itemName}\" disimpan: {$safeQty} unit masuk ke Barang Aman dan {$damagedQty} unit masuk ke Perlu Diperbaiki.",
            ];
        });

        return redirect()->route($outcome['route'])->with('success', $outcome['message']);
    }

    /**
     * Tandai beberapa item sekaligus sebagai Aman pada halaman Sedang Di-Maintenance.
     */
    public function bulkTriageSafe(Request $request)
    {
        $validated = $request->validate([
            'schedule_ids' => 'required|array|min:1',
            'schedule_ids.*' => 'required|integer|exists:preventive_schedules,id',
        ], [
            'schedule_ids.required' => 'Pilih minimal satu barang yang akan dinyatakan Aman.',
            'schedule_ids.min' => 'Pilih minimal satu barang yang akan dinyatakan Aman.',
        ]);

        $updatedCount = DB::transaction(function () use ($validated) {
            $schedules = PreventiveSchedule::query()
                ->whereIn('id', $validated['schedule_ids'])
                ->where('workflow_stage', 'in_maintenance')
                ->lockForUpdate()
                ->get();

            $count = 0;

            foreach ($schedules as $schedule) {
                $totalQty = max(1, (int) $schedule->quantity);
                $baseDate = ($schedule->maintenance_date ?? Carbon::today())->toDateString();
                $nextDueDate = PreventiveSchedule::calculateNextDueDate(
                    $baseDate,
                    max(1, (int) $schedule->interval_months)
                )->toDateString();

                $schedule->update([
                    'workflow_stage' => 'safe',
                    'safe_quantity' => $totalQty,
                    'damaged_quantity' => 0,
                    'damage_notes' => null,
                    'status' => 'scheduled',
                    'last_serviced_at' => $baseDate,
                    'next_due_date' => $nextDueDate,
                    'last_notified_stage' => null,
                    'last_notified_at' => null,
                    'inspected_at' => now(),
                    'inspected_by' => auth()->id(),
                ]);

                $count++;
            }

            return $count;
        });

        return redirect()->route('maintenance.schedules.safe')
            ->with('success', "{$updatedCount} barang dinyatakan Aman dan dipindahkan ke halaman Barang Aman.");
    }

    /**
     * Ajukan barang hasil temuan rusak pada halaman Perlu Diperbaiki menjadi Tiket Kerusakan M&R resmi.
     */
    public function submitTicket(Request $request, string $id)
    {
        $validated = $request->validate([
            'issue_description' => 'required|string|max:2000',
        ], [
            'issue_description.required' => 'Deskripsi kendala kerusakan untuk tiket wajib diisi.',
        ]);

        $ticketResult = DB::transaction(function () use ($id, $validated) {
            $schedule = PreventiveSchedule::query()->with('item')->lockForUpdate()->findOrFail($id);

            if ($schedule->workflow_stage === 'ticket_created' || $schedule->maintenance_ticket_id !== null) {
                return ['duplicate' => true, 'ticket' => $schedule->maintenanceTicket];
            }

            $description = trim((string) $validated['issue_description']);

            $ticket = MaintenanceTicket::create([
                'item_id' => $schedule->item_id,
                'reported_by' => auth()->id(),
                'issue_description' => $description,
                'status' => TicketStatus::OPEN,
            ]);

            if ($schedule->item_id) {
                $item = Item::query()->lockForUpdate()->find($schedule->item_id);
                if ($item && $item->status !== ItemStatus::UNDER_MAINTENANCE) {
                    $item->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
                }
            }

            $schedule->update([
                'workflow_stage' => 'ticket_created',
                'maintenance_ticket_id' => $ticket->id,
                'damage_notes' => $description,
                'status' => 'paused',
            ]);

            return [
                'duplicate' => false,
                'ticket' => $ticket,
                'item_name' => $schedule->item?->name ?? 'Barang',
            ];
        });

        if ($ticketResult['duplicate']) {
            return redirect()->route('maintenance.schedules.repairs')
                ->with('error', 'Barang ini sudah diajukan ke tiket kerusakan sebelumnya.');
        }

        $ticket = $ticketResult['ticket'];

        if (config('services.telegram.enabled')) {
            dispatch(new SendMaintenanceTicketTelegramJob($ticket->id, 'created'));
        }

        return redirect()->route('maintenance.index', ['status' => 'open'])
            ->with('success', "Tiket pengajuan barang rusak #{$ticket->id} untuk \"{$ticketResult['item_name']}\" berhasil dibuat.");
    }

    /**
     * Nyatakan unit pada tahap Perlu Diperbaiki sebagai Rusak Permanen -> masuk Barang Rusak & kurangi Master Aset.
     */
    public function markBroken(Request $request, string $id)
    {
        $validated = $request->validate([
            'damage_reason' => 'nullable|string|max:2000',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $schedule = PreventiveSchedule::query()->with('item')->lockForUpdate()->findOrFail($id);

            if ($schedule->workflow_stage === 'written_off' || DamagedAsset::where('preventive_schedule_id', $schedule->id)->exists()) {
                return ['blocked' => 'Barang ini sudah dicatat sebagai Barang Rusak Permanen sebelumnya.'];
            }

            if ($schedule->workflow_stage === 'ticket_created' || $schedule->maintenance_ticket_id !== null) {
                return ['blocked' => 'Barang ini sudah diajukan ke Tiket Kerusakan #'.($schedule->maintenance_ticket_id ?: '-').'. Selesaikan melalui tiket tersebut dengan memilih hasil Rusak Permanen.'];
            }

            $qty = max(1, (int) ($schedule->damaged_quantity ?: $schedule->quantity));
            $reason = filled($validated['damage_reason'] ?? null)
                ? trim((string) $validated['damage_reason'])
                : (trim((string) $schedule->damage_notes) ?: 'Rusak permanen hasil pengecekan maintenance.');
            $itemName = $schedule->item?->name ?? 'Barang';

            $writeOff = $schedule->item_id
                ? $this->damagedAssetService->recordAndReduceMasterAsset([
                    'item_id' => $schedule->item_id,
                    'quantity' => $qty,
                    'source' => 'preventive_maintenance',
                    'preventive_schedule_id' => $schedule->id,
                    'damage_reason' => $reason,
                    'skip_schedule_ids' => [$schedule->id],
                ])
                : null;

            $schedule->update([
                'workflow_stage' => 'written_off',
                'status' => 'paused',
                'damage_notes' => $reason,
                'inspected_at' => now(),
                'inspected_by' => auth()->id(),
            ]);

            return [
                'blocked' => null,
                'item_name' => $itemName,
                'write_off' => $writeOff,
                'qty' => (int) ($writeOff['retired_qty'] ?? $qty),
            ];
        });

        if (! empty($result['blocked'])) {
            return redirect()->route('maintenance.schedules.repairs')
                ->with('error', $result['blocked']);
        }

        $writeOff = $result['write_off'];
        $msg = ! empty($writeOff['item_deleted'])
            ? "\"{$result['item_name']}\" ({$result['qty']} unit) dipindahkan ke Barang Rusak Permanen dan dikeluarkan dari Master Aset."
            : "\"{$result['item_name']}\" ({$result['qty']} unit) dipindahkan ke Barang Rusak Permanen. Stok Master Aset berkurang menjadi ".($writeOff['remaining_stock'] ?? 0).' unit.';

        return redirect()->route('maintenance.damaged.index')
            ->with('success', $msg);
    }

    /**
     * Mulai ulang sesi maintenance untuk barang yang sudah aman / jadwal preventif jatuh tempo.
     */
    public function restart(Request $request, string $id)
    {
        $validated = $request->validate([
            'maintenance_date' => 'nullable|date',
            'quantity' => 'nullable|integer|min:1|max:10000',
        ]);

        $itemName = DB::transaction(function () use ($id, $validated) {
            $schedule = PreventiveSchedule::query()->with('item')->lockForUpdate()->findOrFail($id);
            $effectiveDate = ! empty($validated['maintenance_date'])
                ? Carbon::parse($validated['maintenance_date'])->toDateString()
                : Carbon::today()->toDateString();

            $qty = (int) ($validated['quantity'] ?? max(1, (int) $schedule->quantity));

            $schedule->update([
                'workflow_stage' => 'in_maintenance',
                'maintenance_date' => $effectiveDate,
                'quantity' => $qty,
                'safe_quantity' => 0,
                'damaged_quantity' => 0,
                'damage_notes' => null,
                'inspected_at' => null,
                'inspected_by' => null,
            ]);

            return $schedule->item?->name ?? 'Barang';
        });

        return redirect()->route('maintenance.schedules.in-progress')
            ->with('success', "\"{$itemName}\" dimasukkan kembali ke tahap Sedang Di-Maintenance.");
    }

    public function edit(string $id)
    {
        $schedule = PreventiveSchedule::with('item')->findOrFail($id);
        $items = Item::query()
            ->with(['preventiveSchedules' => fn ($q) => $q->orderBy('next_due_date')])
            ->orderBy('name')
            ->orderBy('item_code')
            ->get();

        return view('maintenance.schedules.edit', compact('schedule', 'items'));
    }

    public function update(Request $request, string $id)
    {
        $schedule = PreventiveSchedule::findOrFail($id);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'title' => 'required|string|max:150',
            'interval_months' => 'required|integer|min:1|max:60',
            'last_serviced_at' => 'required|date',
            'maintenance_date' => 'nullable|date',
            'quantity' => 'nullable|integer|min:1|max:10000',
            'status' => 'required|in:scheduled,paused',
            'notes' => 'nullable|string|max:2000',
        ]);

        $nextDueDate = PreventiveSchedule::calculateNextDueDate(
            $validated['last_serviced_at'],
            (int) $validated['interval_months']
        );

        $dueChanged = $schedule->next_due_date?->toDateString() !== $nextDueDate->toDateString();
        $updatedQty = (int) ($validated['quantity'] ?? $schedule->quantity ?? 1);

        $qtyFields = ['quantity' => $updatedQty];
        if (in_array($schedule->workflow_stage, ['safe', 'scheduled'], true)) {
            $qtyFields['safe_quantity'] = $updatedQty;
            $qtyFields['damaged_quantity'] = 0;
        } elseif (in_array($schedule->workflow_stage, ['needs_repair', 'ticket_created'], true)) {
            $qtyFields['safe_quantity'] = 0;
            $qtyFields['damaged_quantity'] = $updatedQty;
        }

        $schedule->update(array_merge([
            'item_id' => $validated['item_id'],
            'title' => $validated['title'],
            'interval_months' => (int) $validated['interval_months'],
            'last_serviced_at' => $validated['last_serviced_at'],
            'maintenance_date' => $validated['maintenance_date'] ?? $schedule->maintenance_date ?? $validated['last_serviced_at'],
            'next_due_date' => $nextDueDate->toDateString(),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'last_notified_stage' => $dueChanged ? null : $schedule->last_notified_stage,
            'last_notified_at' => $dueChanged ? null : $schedule->last_notified_at,
        ], $qtyFields));

        $redirectRoute = match ($schedule->workflow_stage) {
            'in_maintenance' => 'maintenance.schedules.in-progress',
            'needs_repair', 'ticket_created' => 'maintenance.schedules.repairs',
            default => 'maintenance.schedules.safe',
        };

        return redirect()->route($redirectRoute)
            ->with('success', 'Jadwal perawatan preventif berhasil diperbarui.');
    }

    public function markServiced(Request $request, string $id)
    {
        $validated = $request->validate([
            'serviced_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $nextDueLabel = DB::transaction(function () use ($id, $validated) {
            $schedule = PreventiveSchedule::query()->lockForUpdate()->findOrFail($id);

            $servicedAt = ! empty($validated['serviced_at'])
                ? Carbon::parse($validated['serviced_at'])->startOfDay()
                : Carbon::today();

            $nextDueDate = PreventiveSchedule::calculateNextDueDate(
                $servicedAt,
                (int) $schedule->interval_months
            );

            $schedule->update([
                'last_serviced_at' => $servicedAt->toDateString(),
                'maintenance_date' => $servicedAt->toDateString(),
                'next_due_date' => $nextDueDate->toDateString(),
                'workflow_stage' => 'safe',
                'safe_quantity' => max(1, (int) $schedule->quantity),
                'damaged_quantity' => 0,
                'status' => 'scheduled',
                'inspected_at' => now(),
                'inspected_by' => auth()->id(),
                'notes' => array_key_exists('notes', $validated) && filled($validated['notes'])
                    ? $validated['notes']
                    : $schedule->notes,
                'last_notified_stage' => null,
                'last_notified_at' => null,
            ]);

            return $nextDueDate->translatedFormat('d M Y');
        });

        if ($request->boolean('stay_safe')) {
            return redirect()->route('maintenance.schedules.safe')
                ->with('success', "Servis berkala dicatat. Jadwal berikutnya otomatis ditetapkan ke {$nextDueLabel}.");
        }

        return redirect()->route('maintenance.schedules.index')
            ->with('success', "Servis berkala dicatat. Jadwal berikutnya otomatis ditetapkan ke {$nextDueLabel}.");
    }

    public function destroy(Request $request, string $id)
    {
        $schedule = PreventiveSchedule::findOrFail($id);
        $stage = $schedule->workflow_stage;
        $schedule->delete();

        $redirectRoute = match ($stage) {
            'in_maintenance' => 'maintenance.schedules.in-progress',
            'needs_repair', 'ticket_created' => 'maintenance.schedules.repairs',
            'safe', 'scheduled' => 'maintenance.schedules.safe',
            default => 'maintenance.schedules.index',
        };

        return redirect()->route($redirectRoute)
            ->with('success', 'Data jadwal maintenance berhasil dihapus.');
    }

    /**
     * Ringkasan jumlah item per tahap workflow untuk navigasi step tabs.
     *
     * @return array<string, int>
     */
    private function workflowCounts(?int $totalMaster = null, ?int $unscheduledMaster = null): array
    {
        $today = Carbon::today();
        $dueSoonLimit = $today->copy()->addDays(7);

        return [
            'total_master' => $totalMaster ?? Item::query()->count(),
            'unscheduled_master' => $unscheduledMaster ?? Item::query()->whereDoesntHave('preventiveSchedules')->count(),
            'in_maintenance' => PreventiveSchedule::query()
                ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
                ->where('workflow_stage', 'in_maintenance')
                ->count(),
            'safe' => PreventiveSchedule::query()
                ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
                ->whereIn('workflow_stage', ['safe', 'scheduled'])
                ->count(),
            'needs_repair' => PreventiveSchedule::query()
                ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
                ->where('workflow_stage', 'needs_repair')
                ->count(),
            'ticket_created' => PreventiveSchedule::query()
                ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
                ->where('workflow_stage', 'ticket_created')
                ->count(),
            'due_safe' => PreventiveSchedule::query()
                ->whereHas('item', fn ($iq) => $iq->withoutTrashed())
                ->whereIn('workflow_stage', ['safe', 'scheduled'])
                ->where('status', 'scheduled')
                ->whereDate('next_due_date', '<=', $dueSoonLimit)
                ->count(),
            'damaged_units' => (int) DamagedAsset::query()->sum('quantity'),
        ];
    }
}
