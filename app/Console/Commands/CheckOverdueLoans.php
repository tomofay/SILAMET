<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOverdueLoans extends Command
{
    protected $signature = 'loans:check-overdue';

    protected $description = 'Kirim notifikasi WhatsApp ke siswa yang belum mengembalikan barang melewati jatuh tempo.';

    public function handle(WhatsAppService $whatsapp): int
    {
        // Peminjaman aktif yang sudah lewat jatuh tempo dan belum pernah dinotifikasi.
        $overdueLoans = Loan::query()
            ->with(['user', 'item'])
            ->where('status', LoanStatus::AKTIF)
            ->whereNotNull('due_at')
            ->where('due_at', '<', Carbon::now())
            ->whereNull('overdue_notified_at')
            ->get();

        if ($overdueLoans->isEmpty()) {
            $this->info('Tidak ada peminjaman overdue yang perlu dinotifikasi.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($overdueLoans as $loan) {
            $user = $loan->user;
            $item = $loan->item;

            if ($user === null || blank($user->phone) || $item === null) {
                $skipped++;
                $this->warn("Loan #{$loan->id} dilewati: siswa/barang tidak valid atau nomor kosong.");
                continue;
            }

            $ok = $whatsapp->sendOverdueNotification(
                $user->phone,
                $user->name,
                $item->name,
                $loan->due_at->translatedFormat('d M Y H:i')
            );

            if ($ok) {
                $loan->update(['overdue_notified_at' => Carbon::now()]);
                $sent++;
                $this->info("Notifikasi terkirim ke {$user->name} ({$user->phone}) untuk barang {$item->name}.");
            } else {
                $skipped++;
                $this->error("Gagal mengirim notifikasi ke {$user->phone} (loan #{$loan->id}).");
            }
        }

        $this->info(sprintf('Selesai. Terkirim: %d, dilewati/gagal: %d.', $sent, $skipped));

        return self::SUCCESS;
    }
}
