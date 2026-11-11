<?php

namespace App\Jobs;

use App\Enums\GateAction;
use App\Enums\ItemStatus;
use App\Models\GateLog;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SendGateViolationTelegramJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 3;

    /**
     * @param  int|array<int, int>  $gateLogId
     * @param  array<string|int, mixed>  $context
     */
    public function __construct(
        public int|array $gateLogId,
        public array $context = []
    ) {}

    public function handle(TelegramService $telegram): void
    {
        if (! $telegram->isEnabled()) {
            return;
        }

        $ids = is_array($this->gateLogId)
            ? array_values(array_unique(array_filter(array_map('intval', $this->gateLogId), fn (int $id) => $id > 0)))
            : [(int) $this->gateLogId];

        if (empty($ids)) {
            return;
        }

        $pendingIds = array_values(array_filter(
            $ids,
            fn (int $id) => Cache::get("telegram:gate_alert_sent:{$id}") !== 'photo'
        ));

        if (empty($pendingIds)) {
            return;
        }

        $gateLogs = GateLog::query()
            ->with('item')
            ->whereIn('id', $pendingIds)
            ->orderBy('id')
            ->get()
            ->filter(function (GateLog $log) {
                if ($log->action_status !== GateAction::DENIED) {
                    return false;
                }

                // Pastikan barang aktif yang berstatus dipinjam tidak pernah dikirim ke alert pelanggaran Telegram.
                if ($log->item && ! $log->item->trashed() && $log->item->status === ItemStatus::DIPINJAM) {
                    return false;
                }

                return true;
            })
            ->values();

        if ($gateLogs->isEmpty()) {
            return;
        }

        $photoPath = $gateLogs->first(fn (GateLog $log) => filled($log->capture_image_path))?->capture_image_path;

        if (filled($photoPath)) {
            $acquiredLocks = [];
            $lockedLogs = new Collection();

            try {
                foreach ($gateLogs as $log) {
                    $photoLockKey = "telegram:gate_sending_photo:{$log->id}";
                    if (Cache::add($photoLockKey, true, now()->addSeconds(30))) {
                        $acquiredLocks[] = $photoLockKey;
                        if (Cache::get("telegram:gate_alert_sent:{$log->id}") !== 'photo') {
                            $lockedLogs->push($log);
                        }
                    }
                }

                if ($lockedLogs->isEmpty()) {
                    return;
                }

                $message = $this->buildMessage($telegram, $lockedLogs);
                foreach ($lockedLogs as $log) {
                    Cache::put("telegram:gate_alert_sent:{$log->id}", 'photo', now()->addMinutes(5));
                    Cache::forget("telegram:gate_eligible:{$log->id}");
                }

                $telegram->sendToChannel('gate', $message, $photoPath);
            } finally {
                foreach ($acquiredLocks as $lockKey) {
                    Cache::forget($lockKey);
                }
            }

            return;
        }

        // Hanya kirim alert teks fallback sekali bila foto memang belum/tidak tersedia.
        $textTargetLogs = $gateLogs->filter(
            fn (GateLog $log) => Cache::add("telegram:gate_alert_sent:{$log->id}", 'text', now()->addMinutes(5))
        )->values();

        if ($textTargetLogs->isEmpty()) {
            return;
        }

        foreach ($textTargetLogs as $log) {
            Cache::forget("telegram:gate_eligible:{$log->id}");
        }

        $message = $this->buildMessage($telegram, $textTargetLogs);
        $telegram->sendToChannel('gate', $message, null);
    }

    /**
     * @param  Collection<int, GateLog>  $gateLogs
     */
    private function buildMessage(TelegramService $telegram, Collection $gateLogs): string
    {
        if ($gateLogs->count() === 1) {
            $singleLog = $gateLogs->first();
            $singleContext = isset($this->context[$singleLog->id]) && is_array($this->context[$singleLog->id])
                ? $this->context[$singleLog->id]
                : $this->context;

            return $telegram->buildGateViolationMessage($singleLog, $singleContext);
        }

        return $telegram->buildGateBatchViolationMessage($gateLogs, $this->context);
    }
}

