<?php

namespace App\Console\Commands;

use App\Models\PreventiveSchedule;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDueMaintenance extends Command
{
    protected $signature = 'maintenance:check-due';

    protected $description = 'Periksa jadwal perawatan preventif alat yang mendekati tenggat (H-7, H-3, H-1, Hari-H, Overdue) dan kirim notifikasi Telegram.';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isEnabled()) {
            $this->warn('Integrasi Telegram nonaktif atau token bot belum dikonfigurasi.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        $schedules = PreventiveSchedule::query()
            ->with('item')
            ->where('status', 'scheduled')
            ->whereDate('next_due_date', '<=', $today->copy()->addDays(7))
            ->orderBy('next_due_date')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal maintenance preventif yang mendekati jatuh tempo.');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->item === null) {
                $skipped++;

                continue;
            }

            $daysRemaining = $schedule->daysRemaining($today);
            $stage = $this->resolveStage($daysRemaining);

            if ($stage === null || ! $this->shouldNotify($schedule, $stage, $now)) {
                $skipped++;

                continue;
            }

            $message = $telegram->buildPreventiveMaintenanceMessage($schedule, $daysRemaining);
            $delivery = $telegram->sendToChannel('maintenance', $message);

            if ($delivery['sent'] > 0) {
                $schedule->update([
                    'last_notified_stage' => $stage,
                    'last_notified_at' => $now,
                ]);
                $sent++;
                $this->info("Notifikasi {$stage} terkirim untuk {$schedule->item->name} ({$schedule->title}).");
            } else {
                $skipped++;
                $this->error("Gagal mengirim notifikasi untuk jadwal #{$schedule->id}.");
            }
        }

        $this->info(sprintf('Selesai. Terkirim: %d, dilewati/sudah ternotifikasi: %d.', $sent, $skipped));

        return self::SUCCESS;
    }

    private function resolveStage(int $daysRemaining): ?string
    {
        if ($daysRemaining < 0) {
            return 'overdue';
        }

        if ($daysRemaining === 0) {
            return 'h0';
        }

        if ($daysRemaining === 1) {
            return 'h1';
        }

        if ($daysRemaining <= 3) {
            return 'h3';
        }

        if ($daysRemaining <= 7) {
            return 'h7';
        }

        return null;
    }

    private function shouldNotify(PreventiveSchedule $schedule, string $stage, Carbon $now): bool
    {
        if ($stage !== 'overdue') {
            return $schedule->last_notified_stage !== $stage;
        }

        if ($schedule->last_notified_stage !== 'overdue' || $schedule->last_notified_at === null) {
            return true;
        }

        // Ulangi alert overdue maintenance maksimal setiap 3 hari sampai teknisi menandai servis selesai.
        return $schedule->last_notified_at->lte($now->copy()->subDays(3));
    }
}

