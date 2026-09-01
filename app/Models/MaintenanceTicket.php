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
        return $this->belongsTo(Item::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
