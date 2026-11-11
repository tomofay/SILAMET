<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOverdueLoans extends Command
{
    protected $signature = 'loans:check-overdue';

    protected $description = 'Periksa peminjaman melewati jatuh tempo dan kirim pengingat Telegram beserta durasi keterlambatan.';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isEnabled()) {
            $this->warn('Integrasi Telegram nonaktif atau token bot belum dikonfigurasi.');

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $cooldownHours = max(1, (int) config('services.telegram.overdue_reminder_hours', 4));
        $cutoff = $now->copy()->subHours($cooldownHours);

        $overdueLoans = Loan::query()
            ->with(['user', 'item'])
            ->where('status', LoanStatus::AKTIF)
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('overdue_notified_at')
                    ->orWhere('overdue_notified_at', '<=', $cutoff);
            })
            ->orderBy('due_at')
            ->get();

        if ($overdueLoans->isEmpty()) {
            $this->info('Tidak ada peminjaman overdue yang perlu dinotifikasi.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($overdueLoans as $loan) {
            if ($loan->user === null || $loan->item === null) {
                $skipped++;
                $this->warn("Loan #{$loan->id} dilewati karena relasi peminjam/barang tidak ditemukan.");

                continue;
            }

            $reminderNumber = (int) ($loan->overdue_notified_count ?? 0) + 1;
            $staffMessage = $telegram->buildOverdueStaffMessage($loan, $now, $reminderNumber);
            $delivery = $telegram->sendToChannel('overdue', $staffMessage);

            $dmSent = false;
            if (filled($loan->user->telegram_chat_id)) {
                $borrowerMessage = $telegram->buildOverdueBorrowerMessage($loan, $now);
                $dmSent = $telegram->sendMessage($loan->user->telegram_chat_id, $borrowerMessage);
            }

            if ($delivery['sent'] > 0 || $dmSent) {
                $loan->update([
                    'overdue_notified_at' => $now,
                    'overdue_notified_count' => $reminderNumber,
                ]);
                $sent++;
                $lateness = TelegramService::formatOverdueDuration($loan->due_at, $now);
                $this->info("Pengingat #{$reminderNumber} terkirim: {$loan->user->name} — {$loan->item->name} ({$lateness} terlambat).");
            } else {
                $skipped++;
                $this->error("Gagal mengirim pengingat Telegram untuk Loan #{$loan->id}.");
            }
        }

        $this->info(sprintf('Selesai. Terkirim: %d, dilewati/gagal: %d.', $sent, $skipped));

        return self::SUCCESS;
    }
}

