<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamagedAsset extends Model
{
    use HasFactory;

    protected $table = 'damaged_assets';

    protected $fillable = [
        'item_id',
        'item_code',
        'item_name',
        'item_category',
        'item_type',
        'epc_tag',
        'location',
        'quantity',
        'source',
        'maintenance_ticket_id',
        'preventive_schedule_id',
        'damage_reason',
        'service_type',
        'vendor_name',
        'repair_cost',
        'receipt_path',
        'recorded_by',
        'damaged_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'repair_cost' => 'decimal:2',
            'damaged_at' => 'datetime',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function maintenanceTicket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }

    public function preventiveSchedule()
    {
        return $this->belongsTo(PreventiveSchedule::class, 'preventive_schedule_id')->withTrashed();
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'repair_ticket' => 'Tiket Perbaikan',
            'preventive_maintenance' => 'Maintenance Preventif',
            default => ucfirst(str_replace('_', ' ', (string) $this->source)),
        };
    }

    public function typeLabel(): string
    {
        return $this->item_type === 'bahan_habis_pakai' ? 'Bahan Habis Pakai' : 'Aset';
    }
}
