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
        'quantity_borrowed',
        'status',
        'condition_in',
        'damage_note',
        'capture_image_path',
        'usage_duration',
        'borrowed_at',
        'due_at',
        'returned_at',
        'overdue_notified_at',
        'overdue_notified_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'quantity_borrowed' => 'integer',
            'usage_duration' => LoanDuration::class,
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
            'overdue_notified_count' => 'integer',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
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

    /**
     * Hitung label lama keterlambatan (misal: "2 jam 15 menit" atau "1 hari 4 jam").
     */
    public function latenessLabel(?Carbon $now = null): ?string
    {
        $reference = $now ?? Carbon::now();

        if ($this->status !== LoanStatus::AKTIF || $this->due_at === null || $this->due_at->greaterThanOrEqualTo($reference)) {
            return null;
        }

        return \App\Services\TelegramService::formatOverdueDuration($this->due_at, $reference);
    }

    /**
     * Dapatkan keterangan kerusakan saat pengembalian barang.
     */
    public function damageNote(): ?string
    {
        if (filled($this->damage_note)) {
            return $this->damage_note;
        }

        if ($this->condition_in === 'rusak') {
            $ticket = MaintenanceTicket::where('item_id', $this->item_id)
                ->where('reported_by', $this->user_id)
                ->when($this->borrowed_at, fn ($q) => $q->where('created_at', '>=', $this->borrowed_at->subMinutes(5)))
                ->latest()
                ->first();

            return $ticket?->issue_description;
        }

        return null;
    }
}
