<?php

namespace App\Http\Controllers;

use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\PreventiveSchedule;
use Illuminate\Http\Request;

class DamagedAssetController extends Controller
{
    /**
     * Tampilkan daftar permanen Barang Rusak (aset/unit yang sudah tidak layak pakai
     * dan otomatis mengurangi/menghapus stok di Master Aset).
     */
    public function index(Request $request)
    {
        $source = (string) $request->query('source', 'all');
        if (! in_array($source, ['all', 'repair_ticket', 'preventive_maintenance'], true)) {
            $source = 'all';
        }

        $search = trim((string) $request->query('q', ''));

        $baseQuery = DamagedAsset::query()
            ->with(['recordedBy', 'maintenanceTicket', 'preventiveSchedule']);

        if ($source !== 'all') {
            $baseQuery->where('source', $source);
        }

        if ($search !== '') {
            $term = '%'.$search.'%';
            $baseQuery->where(function ($sub) use ($term) {
                $sub->where('item_name', 'like', $term)
                    ->orWhere('item_code', 'like', $term)
                    ->orWhere('item_category', 'like', $term)
                    ->orWhere('damage_reason', 'like', $term)
                    ->orWhere('vendor_name', 'like', $term);
            });
        }

        $damagedAssets = $baseQuery
            ->latest('damaged_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => DamagedAsset::query()->count(),
            'repair_ticket' => DamagedAsset::query()->where('source', 'repair_ticket')->count(),
            'preventive_maintenance' => DamagedAsset::query()->where('source', 'preventive_maintenance')->count(),
        ];

        $totalUnitsDamaged = (int) DamagedAsset::query()->sum('quantity');
        $repairTicketUnits = (int) DamagedAsset::query()->where('source', 'repair_ticket')->sum('quantity');
        $preventiveUnits = (int) DamagedAsset::query()->where('source', 'preventive_maintenance')->sum('quantity');

        $unscheduledMasterItemsCount = Item::query()->whereDoesntHave('preventiveSchedules')->count();
        $inMaintenanceStageCount = PreventiveSchedule::query()
            ->where('workflow_stage', 'in_maintenance')
            ->whereHas('item', fn ($q) => $q->withoutTrashed())
            ->count();
        $needsRepairStageCount = PreventiveSchedule::query()
            ->where('workflow_stage', 'needs_repair')
            ->whereHas('item', fn ($q) => $q->withoutTrashed())
            ->count();

        return view('maintenance.damaged.index', compact(
            'damagedAssets',
            'counts',
            'source',
            'search',
            'totalUnitsDamaged',
            'repairTicketUnits',
            'preventiveUnits',
            'unscheduledMasterItemsCount',
            'inMaintenanceStageCount',
            'needsRepairStageCount'
        ));
    }
}
