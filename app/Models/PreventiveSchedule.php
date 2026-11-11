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
        'title',
        'interval_months',
        'last_serviced_at',
        'next_due_date',
        'notes',
        'status',
        'last_notified_stage',
        'last_notified_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'interval_months' => 'integer',
            'last_serviced_at' => 'date',
            'next_due_date' => 'date',
            'last_notified_at' => 'datetime',
        ];
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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
            return 'Terlambat ' . abs($days) . ' hari';
        }

        if ($days === 0) {
            return 'Jadwal Hari Ini';
        }

        if ($days === 1) {
            return 'Besok (1 hari lagi)';
        }

        return $days . ' hari lagi';
    }
}

