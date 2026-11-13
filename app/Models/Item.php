<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'item_code',
        'epc_tag',
        'name',
        'category',
        'type',
        'stock',
        'status',
        'barcode_token',
        'location',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->barcode_token)) {
                $item->barcode_token = \App\Services\BarcodeTokenService::generateItemToken();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'status' => ItemStatus::class,
            'stock' => 'integer',
        ];
    }

    // Relationships
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(Loan::class)->where('status', LoanStatus::AKTIF);
    }

    public function tickets()
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function preventiveSchedules()
    {
        return $this->hasMany(PreventiveSchedule::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function damagedAssets()
    {
        return $this->hasMany(DamagedAsset::class, 'item_id')->latest('damaged_at');
    }
}
