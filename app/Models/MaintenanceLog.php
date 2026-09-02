<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $table = 'maintenance_logs';

    protected $fillable = [
        'ticket_id',
        'handled_by',
        'action_taken',
        'service_type',
        'cost',
        'repaired_at',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'cost' => 'decimal:2',
            'repaired_at' => 'datetime',
        ];
    }

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
