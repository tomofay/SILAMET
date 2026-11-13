<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreventiveSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'preventive_schedules';

    protected $fillable = [
        'item_id',
        'parent_schedule_id',
        'title',
        'interval_months',
        'last_serviced_at',
        'maintenance_date',
        'quantity',
        'safe_quantity',
        'damaged_quantity',
        'next_due_date',
        'notes',
        'damage_notes',
        'status',
        'workflow_stage',
        'last_notified_stage',
        'last_notified_at',
        'created_by',
        'maintenance_ticket_id',
        'inspected_at',
        'inspected_by',
    ];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'quantity' => 'integer',
            'safe_quantity' => 'integer',
            'damaged_quantity' => 'integer',
            'last_serviced_at' => 'date',
            'maintenance_date' => 'date',
            'next_due_date' => 'date',
            'last_notified_at' => 'datetime',
            'inspected_at' => 'datetime',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function maintenanceTicket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }

    public function parentSchedule()
    {
        return $this->belongsTo(self::class, 'parent_schedule_id');
    }

    /**
     * Hitung tanggal jadwal maintenance berikutnya dari tanggal servis terakhir + interval bulan.
     */
    public static function calculateNextDueDate(Carbon|string $lastServicedAt, int $intervalMonths): Carbon
    {
        return Carbon::parse($lastServicedAt)
            ->startOfDay()
            ->addMonthsNoOverflow(max(1, $intervalMonths));
    }

    /**
     * Hitung selisih hari hingga jadwal berikutnya.
     * Nilai positif = masih tersisa N hari, 0 = hari ini, negatif = lewat jadwal.
     */
    public function daysRemaining(?Carbon $today = null): int
    {
        $reference = ($today ? $today->copy() : Carbon::today())->startOfDay();
        $due = $this->next_due_date->copy()->startOfDay();

        return (int) $reference->diffInDays($due, false);
    }

    /**
     * Klasifikasi tingkat urgensi jadwal untuk badge UI & laporan.
     */
    public function urgencyLevel(?Carbon $today = null): string
    {
        if ($this->status === 'paused') {
            return 'paused';
        }

        $days = $this->daysRemaining($today);

        if ($days < 0) {
            return 'overdue';
        }

        if ($days <= 7) {
            return 'due_soon';
        }

        if ($days <= 14) {
            return 'upcoming';
        }

        return 'normal';
    }

    /**
     * Label countdown ramah baca dalam Bahasa Indonesia.
     */
    public function countdownLabel(?Carbon $today = null): string
    {
        if ($this->status === 'paused') {
            return 'Dijeda';
        }

        $days = $this->daysRemaining($today);

        if ($days < 0) {
            return 'Terlambat '.abs($days).' hari';
        }

        if ($days === 0) {
            return 'Jadwal Hari Ini';
        }

        if ($days === 1) {
            return 'Besok (1 hari lagi)';
        }

        return $days.' hari lagi';
    }
}
