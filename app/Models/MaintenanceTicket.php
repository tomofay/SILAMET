<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $table = 'maintenance_tickets';

    protected $fillable = [
        'item_id',
        'reported_by',
        'issue_description',
        'status',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'ticket_id')->orderByDesc('id');
    }

    public function latestLog()
    {
        return $this->hasOne(MaintenanceLog::class, 'ticket_id')->latestOfMany();
    }

    public function preventiveSchedule()
    {
        return $this->hasOne(PreventiveSchedule::class, 'maintenance_ticket_id');
    }

    public function damagedAssets()
    {
        return $this->hasMany(DamagedAsset::class, 'maintenance_ticket_id');
    }
}
