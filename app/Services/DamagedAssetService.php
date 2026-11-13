<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Enums\TicketStatus;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Models\StockMovement;
use Illuminate\Support\Str;

class DamagedAssetService
{
    /**
     * Catat barang rusak permanen ke tabel `damaged_assets` dan langsung kurangi
     * stok / soft-delete item pada Master Aset dalam transaksi yang sedang berjalan.
     *
     * @param  array{
     *     item_id: int,
     *     quantity?: int,
     *     source: string,
     *     damage_reason: string,
     *     maintenance_ticket_id?: ?int,
     *     preventive_schedule_id?: ?int,
     *     service_type?: ?string,
     *     vendor_name?: ?string,
     *     repair_cost?: float|int,
     *     receipt_path?: ?string,
     *     recorded_by?: ?int,
     *     skip_schedule_ids?: array<int, int>
     * }  $payload
     * @return array{
     *     record: DamagedAsset,
     *     retired_qty: int,
     *     item_deleted: bool,
     *     remaining_stock: int
     * }
     */
    public function recordAndReduceMasterAsset(array $payload): array
    {
        /** @var Item $item */
        $item = Item::withTrashed()->lockForUpdate()->findOrFail($payload['item_id']);

        $requestedQty = max(1, (int) ($payload['quantity'] ?? 1));
        $alreadyTrashed = $item->trashed();
        $currentStock = $alreadyTrashed ? 0 : max(0, (int) $item->stock);
        $retireQty = $alreadyTrashed
            ? $requestedQty
            : max(1, min($requestedQty, max(1, $currentStock)));

        $damageReason = trim((string) $payload['damage_reason']);
        $recordedBy = $payload['recorded_by'] ?? auth()->id();

        $record = DamagedAsset::create([
            'item_id' => $item->id,
            'item_code' => (string) $item->item_code,
            'item_name' => (string) $item->name,
            'item_category' => $item->category,
            'item_type' => $item->type?->value ?? ItemType::ASET->value,
            'epc_tag' => $item->epc_tag,
            'location' => $item->location,
            'quantity' => $retireQty,
            'source' => (string) $payload['source'],
            'maintenance_ticket_id' => $payload['maintenance_ticket_id'] ?? null,
            'preventive_schedule_id' => $payload['preventive_schedule_id'] ?? null,
            'damage_reason' => $damageReason,
            'service_type' => $payload['service_type'] ?? null,
            'vendor_name' => filled($payload['vendor_name'] ?? null) ? trim((string) $payload['vendor_name']) : null,
            'repair_cost' => (float) ($payload['repair_cost'] ?? 0),
            'receipt_path' => $payload['receipt_path'] ?? null,
            'recorded_by' => $recordedBy,
            'damaged_at' => now(),
        ]);

        if ($alreadyTrashed) {
            return [
                'record' => $record,
                'retired_qty' => $retireQty,
                'item_deleted' => true,
                'remaining_stock' => 0,
            ];
        }

        $newStock = max(0, $currentStock - $retireQty);

        if ($item->type === ItemType::BAHAN_HABIS_PAKAI) {
            StockMovement::create([
                'item_id' => $item->id,
                'user_id' => $recordedBy,
                'type' => StockMovementType::OUT,
                'quantity' => $retireQty,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'recipient_name' => 'Barang Rusak Permanen',
                'description' => 'Pengurangan stok rusak permanen: '.Str::limit($damageReason, 220),
            ]);
        }

        if ($newStock === 0) {
            $skipIds = array_values(array_filter(array_map('intval', $payload['skip_schedule_ids'] ?? [])));

            PreventiveSchedule::query()
                ->where('item_id', $item->id)
                ->when(! empty($skipIds), fn ($q) => $q->whereNotIn('id', $skipIds))
                ->whereIn('workflow_stage', ['scheduled', 'in_maintenance', 'safe', 'needs_repair', 'ticket_created'])
                ->update([
                    'workflow_stage' => 'written_off',
                    'status' => 'paused',
                ]);

            $item->update([
                'stock' => 0,
            ]);
            $item->delete();

            return [
                'record' => $record,
                'retired_qty' => $retireQty,
                'item_deleted' => true,
                'remaining_stock' => 0,
            ];
        }

        $targetStatus = $item->status;
        if ($item->type === ItemType::ASET) {
            $excludeTicketId = $payload['maintenance_ticket_id'] ?? null;
            $hasActiveLoan = $item->activeLoans()->exists();
            $hasOtherActiveTicket = MaintenanceTicket::query()
                ->where('item_id', $item->id)
                ->when($excludeTicketId, fn ($q) => $q->where('id', '!=', $excludeTicketId))
                ->whereIn('status', [TicketStatus::OPEN->value, TicketStatus::IN_PROGRESS->value])
                ->exists();

            $targetStatus = $hasActiveLoan
                ? ItemStatus::DIPINJAM
                : ($hasOtherActiveTicket ? ItemStatus::UNDER_MAINTENANCE : ItemStatus::TERSEDIA);
        }

        $item->update([
            'stock' => $newStock,
            'status' => $targetStatus,
        ]);

        return [
            'record' => $record,
            'retired_qty' => $retireQty,
            'item_deleted' => false,
            'remaining_stock' => $newStock,
        ];
    }
}
