<?php

namespace App\Console\Commands;

use App\Models\GateLog;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneSnapshots extends Command
{
    protected $signature = 'snapshots:prune
                            {--days=60 : Hapus file foto snapshot yang usianya lebih dari N hari}
                            {--dry-run : Tampilkan perkiraan tanpa menghapus file atau mengubah database}';

    protected $description = 'Membersihkan file foto snapshot gate & peminjaman lama dari disk publik serta mengosongkan path pada log terkait.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);
        $disk = Storage::disk('public');

        $this->info(sprintf(
            '[%s] Memindai foto snapshot lebih lama dari %d hari (sebelum %s)...',
            $dryRun ? 'DRY-RUN' : 'PRUNE',
            $days,
            $cutoff->format('Y-m-d H:i:s')
        ));

        $gateLogsCleared = 0;
        $loansCleared = 0;
        $candidatePaths = [];

        GateLog::query()
            ->whereNotNull('capture_image_path')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($logs) use (&$gateLogsCleared, &$candidatePaths, $dryRun) {
                $ids = [];
                foreach ($logs as $log) {
                    if (filled($log->capture_image_path)) {
                        $candidatePaths[$log->capture_image_path] = true;
                        $ids[] = $log->id;
                    }
                }

                if (! empty($ids)) {
                    $gateLogsCleared += count($ids);
                    if (! $dryRun) {
                        GateLog::query()->whereIn('id', $ids)->update(['capture_image_path' => null]);
                    }
                }
            });

        Loan::query()
            ->whereNotNull('capture_image_path')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($loans) use (&$loansCleared, &$candidatePaths, $dryRun) {
                $ids = [];
                foreach ($loans as $loan) {
                    if (filled($loan->capture_image_path)) {
                        $candidatePaths[$loan->capture_image_path] = true;
                        $ids[] = $loan->id;
                    }
                }

                if (! empty($ids)) {
                    $loansCleared += count($ids);
                    if (! $dryRun) {
                        Loan::query()->whereIn('id', $ids)->update(['capture_image_path' => null]);
                    }
                }
            });

        // Pastikan file yang masih dipakai oleh log lebih baru dari cutoff tidak terhapus.
        $activePaths = [];
        GateLog::query()
            ->whereNotNull('capture_image_path')
            ->where('created_at', '>=', $cutoff)
            ->pluck('capture_image_path')
            ->each(function (string $path) use (&$activePaths) {
                $activePaths[$path] = true;
            });

        Loan::query()
            ->whereNotNull('capture_image_path')
            ->where('created_at', '>=', $cutoff)
            ->pluck('capture_image_path')
            ->each(function (string $path) use (&$activePaths) {
                $activePaths[$path] = true;
            });

        // Ikut pindai file yatim (orphan) lama di direktori uploads/photos/gate & uploads/photos/loans.
        foreach (['uploads/photos/gate', 'uploads/photos/loans'] as $directory) {
            if (! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->files($directory) as $filePath) {
                if (basename($filePath) === '.gitignore' || isset($activePaths[$filePath])) {
                    continue;
                }

                try {
                    $lastModified = Carbon::createFromTimestamp($disk->lastModified($filePath));
                } catch (\Throwable) {
                    continue;
                }

                if ($lastModified->lt($cutoff)) {
                    $candidatePaths[$filePath] = true;
                }
            }
        }

        $filesDeleted = 0;
        $bytesFreed = 0;

        foreach (array_keys($candidatePaths) as $path) {
            if (isset($activePaths[$path]) || ! $disk->exists($path)) {
                continue;
            }

            try {
                $bytesFreed += (int) $disk->size($path);
            } catch (\Throwable) {
                // Abaikan jika ukuran gagal dibaca
            }

            if ($dryRun || $disk->delete($path)) {
                $filesDeleted++;
            }
        }

        $sizeLabel = $bytesFreed >= 1048576
            ? number_format($bytesFreed / 1048576, 2) . ' MB'
            : number_format($bytesFreed / 1024, 1) . ' KB';

        $this->line("GateLog dibersihkan   : {$gateLogsCleared}");
        $this->line("Loan dibersihkan      : {$loansCleared}");
        $this->line("File fisik dihapus   : {$filesDeleted} ({$sizeLabel})");

        return self::SUCCESS;
    }
}

