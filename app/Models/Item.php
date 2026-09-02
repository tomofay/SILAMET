<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\ItemStatus;
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
        'type',
        'stock',
        'status',
        'location',
    ];

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
}
