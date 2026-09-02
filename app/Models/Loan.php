<?php

namespace App\Models;

use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'loans';

    protected $fillable = [
        'user_id',
        'item_id',
        'status',
        'condition_in',
        'usage_duration',
        'borrowed_at',
        'due_at',
        'returned_at',
        'overdue_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'usage_duration' => LoanDuration::class,
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Hitung tanggal jatuh tempo berdasarkan durasi pemakaian.
     * Pulang sekolah (0 hari) → batas jam 16:00 hari yang sama.
     */
    public static function calculateDueAt(string $duration, Carbon $borrowedAt): Carbon
    {
        $enum = LoanDuration::tryFrom($duration);

        if ($enum === null) {
            throw new \InvalidArgumentException("Durasi pemakaian '{$duration}' tidak dikenal.");
        }

        $dueAt = $borrowedAt->copy();

        if ($enum === LoanDuration::PULANG_SEKOLAH) {
            return $dueAt->setTime(16, 0, 0);
        }

        return $dueAt->addDays($enum->days())->endOfDay();
    }

    /**
     * Apakah pinjaman sudah melewati jatuh tempo dan belum kembali.
     */
    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::AKTIF
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
