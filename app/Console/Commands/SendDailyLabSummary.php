<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Enums\TicketStatus;
use App\Models\GateLog;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyLabSummary extends Command
{
    protected $signature = 'lab:daily-summary';

    protected $description = 'Kirim ringkasan operasional harian Laboratorium SIJA ke Grup Telegram.';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isEnabled() || ! config('services.telegram.daily_summary_enabled', true)) {
            $this->info('Ringkasan harian Telegram dinonaktifkan.');

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $today = Carbon::today();

        $stats = [
            'active_loans' => Loan::query()->where('status', LoanStatus::AKTIF)->count(),
            'overdue_loans' => Loan::query()
                ->where('status', LoanStatus::AKTIF)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $now)
                ->count(),
            'open_tickets' => MaintenanceTicket::query()
                ->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])
                ->count(),
            'due_maintenance' => PreventiveSchedule::query()
                ->where('status', 'scheduled')
                ->whereDate('next_due_date', '<=', $today->copy()->addDays(7))
                ->count(),
            'today_gate_denied' => GateLog::query()
                ->whereDate('created_at', $today)
                ->where('action_status', 'denied')
                ->count(),
        ];

        $message = $telegram->buildDailySummaryMessage($stats, $now);
        $delivery = $telegram->sendToChannel('daily_summary', $message);

        if ($delivery['sent'] > 0) {
            $this->info('Ringkasan harian lab berhasil dikirim ke Telegram.');
        } else {
            $this->error('Gagal mengirim ringkasan harian lab ke Telegram.');
        }

        return self::SUCCESS;
    }
}

