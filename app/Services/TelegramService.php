<?php

namespace App\Services;

use App\Models\GateLog;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot';

    /**
     * Cek apakah integrasi Telegram aktif dan memiliki bot token.
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.telegram.enabled', false)
            && filled(config('services.telegram.bot_token'));
    }

    /**
     * Escape karakter khusus HTML agar aman untuk parse_mode=HTML Telegram.
     */
    public function escapeHtml(?string $text): string
    {
        return htmlspecialchars((string) ($text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Normalisasi nomor telepon Indonesia (08xxx -> 628xxx) untuk link WhatsApp.
     */
    public function formatWhatsAppNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Format durasi keterlambatan deterministik dalam Bahasa Indonesia.
     */
    public static function formatOverdueDuration(Carbon $dueAt, ?Carbon $now = null): string
    {
        $reference = $now ? $now->copy() : Carbon::now();

        if ($dueAt->greaterThanOrEqualTo($reference)) {
            return '0 menit';
        }

        $totalMinutes = (int) $dueAt->diffInMinutes($reference);

        if ($totalMinutes < 1) {
            return 'kurang dari 1 menit';
        }

        $days = intdiv($totalMinutes, 1440);
        $remainingMinutes = $totalMinutes % 1440;
        $hours = intdiv($remainingMinutes, 60);
        $minutes = $remainingMinutes % 60;

        if ($days > 0) {
            return $hours > 0 ? "{$days} hari {$hours} jam" : "{$days} hari";
        }

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours} jam {$minutes} menit" : "{$hours} jam";
        }

        return "{$minutes} menit";
    }

    /**
     * Tentukan daftar chat_id tujuan berdasarkan channel notifikasi.
     *
     * @return array<int, string>
     */
    public function resolveRecipients(string $channel): array
    {
        $groupId = trim((string) config('services.telegram.group_chat_id', ''));
        $adminId = trim((string) config('services.telegram.admin_chat_id', ''));

        $targets = match ($channel) {
            'group' => [$groupId !== '' ? $groupId : $adminId],
            'admin' => [$adminId !== '' ? $adminId : $groupId],
            'ticket_created', 'ticket_resolved', 'daily_summary' => [$groupId !== '' ? $groupId : $adminId],
            'gate', 'overdue', 'maintenance', 'all', 'test' => [$groupId, $adminId],
            default => [$groupId, $adminId],
        };

        return array_values(array_unique(array_filter($targets, fn (string $id) => $id !== '')));
    }

    /**
     * Siapkan HTTP client Telegram dengan timeout dan optional CA bundle path yang aman.
     */
    private function client(?int $timeoutSeconds = null): PendingRequest
    {
        $timeout = max(2, $timeoutSeconds ?? (int) config('services.telegram.timeout', 5));
        $request = Http::timeout($timeout);

        $caBundle = trim((string) config('services.telegram.ca_bundle', ''));
        if ($caBundle !== '' && is_file($caBundle)) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        return $request;
    }

    /**
     * Kirim pesan teks berformat HTML ke satu chat_id.
     */
    public function sendMessage(string|int $chatId, string $htmlMessage, array $options = []): bool
    {
        $chatId = trim((string) $chatId);

        if (! $this->isEnabled() || $chatId === '') {
            return false;
        }

        $token = trim((string) config('services.telegram.bot_token'));

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $htmlMessage,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options);

            $response = $this->client()
                ->asJson()
                ->post(self::API_BASE . $token . '/sendMessage', $payload);

            $ok = $response->successful() && (bool) $response->json('ok', false);

            if (! $ok) {
                Log::warning('[Telegram] Gagal mengirim pesan.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::warning('[Telegram] Exception saat sendMessage.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Kirim foto snapshot via multipart/form-data dari disk public Laravel.
     * Otomatis fallback ke sendMessage bila file tidak ada atau upload gagal.
     */
    public function sendPhoto(string|int $chatId, string $storageRelativePath, string $captionHtml): bool
    {
        $chatId = trim((string) $chatId);

        if (! $this->isEnabled() || $chatId === '') {
            return false;
        }

        $disk = Storage::disk('public');

        if ($storageRelativePath === '' || ! $disk->exists($storageRelativePath)) {
            Log::warning('[Telegram] File foto tidak ditemukan di disk public, fallback ke teks.', [
                'chat_id' => $chatId,
                'path' => $storageRelativePath,
            ]);

            return $this->sendMessage($chatId, $captionHtml);
        }

        $contents = $disk->get($storageRelativePath);
        if ($contents === '' || $contents === null) {
            Log::warning('[Telegram] File foto kosong, fallback ke teks.', [
                'chat_id' => $chatId,
                'path' => $storageRelativePath,
            ]);

            return $this->sendMessage($chatId, $captionHtml);
        }

        $token = trim((string) config('services.telegram.bot_token'));
        $timeout = max(5, (int) config('services.telegram.timeout', 5) + 5);
        $uploadMeta = $this->resolvePhotoUploadMetadata($contents, $storageRelativePath);

        $safeCaption = $captionHtml;
        $useHtml = true;

        // Batas caption Telegram sendPhoto adalah 1024 karakter.
        if (mb_strlen($safeCaption) > 1020) {
            $safeCaption = Str::limit(strip_tags($captionHtml), 1000);
            $useHtml = false;
        }

        try {
            $formFields = [
                'chat_id' => $chatId,
                'caption' => $safeCaption,
            ];

            if ($useHtml) {
                $formFields['parse_mode'] = 'HTML';
            }

            $attachHeaders = $uploadMeta['mime'] !== null
                ? ['Content-Type' => $uploadMeta['mime']]
                : [];

            $response = $this->client($timeout)
                ->attach('photo', $contents, $uploadMeta['filename'], $attachHeaders)
                ->post(self::API_BASE . $token . '/sendPhoto', $formFields);

            $ok = $response->successful() && (bool) $response->json('ok', false);

            if (! $ok) {
                Log::warning('[Telegram] Gagal mengirim foto, fallback ke teks.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->sendMessage(
                    $chatId,
                    $captionHtml . "\n\n<i>(Lampiran foto gagal diunggah — cek Gate Logs di web admin)</i>"
                );
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[Telegram] Exception saat sendPhoto, fallback ke teks.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendMessage(
                $chatId,
                $captionHtml . "\n\n<i>(Lampiran foto gagal diunggah — cek Gate Logs di web admin)</i>"
            );
        }
    }

    /**
     * Pastikan nama file dan Content-Type lampiran selaras dengan format biner asli gambar.
     *
     * @return array{filename: string, mime: ?string}
     */
    private function resolvePhotoUploadMetadata(string $contents, string $storageRelativePath): array
    {
        $base = basename($storageRelativePath);
        $stem = pathinfo($base, PATHINFO_FILENAME) ?: 'gate_snapshot';

        $detected = null;
        if (str_starts_with($contents, "\xFF\xD8\xFF")) {
            $detected = ['ext' => 'jpg', 'mime' => 'image/jpeg'];
        } elseif (str_starts_with($contents, "\x89PNG\r\n\x1a\n")) {
            $detected = ['ext' => 'png', 'mime' => 'image/png'];
        } elseif (str_starts_with($contents, 'GIF87a') || str_starts_with($contents, 'GIF89a')) {
            $detected = ['ext' => 'gif', 'mime' => 'image/gif'];
        } elseif (strlen($contents) >= 12 && substr($contents, 0, 4) === 'RIFF' && substr($contents, 8, 4) === 'WEBP') {
            $detected = ['ext' => 'webp', 'mime' => 'image/webp'];
        }

        if ($detected === null) {
            return [
                'filename' => $base,
                'mime' => null,
            ];
        }

        return [
            'filename' => $stem . '.' . $detected['ext'],
            'mime' => $detected['mime'],
        ];
    }

    /**
     * Broadcast notifikasi ke seluruh chat_id pada channel tertentu.
     *
     * @return array{sent: int, failed: int, recipients: array<int, string>}
     */
    public function sendToChannel(string $channel, string $htmlMessage, ?string $photoStoragePath = null): array
    {
        $recipients = $this->resolveRecipients($channel);
        $sent = 0;
        $failed = 0;

        if (! $this->isEnabled() || empty($recipients)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'recipients' => [],
            ];
        }

        foreach ($recipients as $chatId) {
            $ok = filled($photoStoragePath)
                ? $this->sendPhoto($chatId, (string) $photoStoragePath, $htmlMessage)
                : $this->sendMessage($chatId, $htmlMessage);

            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'recipients' => $recipients,
        ];
    }

    /**
     * Verifikasi identitas bot Telegram (getMe).
     */
    public function getMe(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $token = trim((string) config('services.telegram.bot_token'));

        try {
            $response = $this->client()->get(self::API_BASE . $token . '/getMe');
            if ($response->successful() && $response->json('ok') === true) {
                return $response->json('result');
            }
        } catch (\Throwable $e) {
            Log::warning('[Telegram] Exception saat getMe.', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Ambil pembaruan pesan masuk (getUpdates) untuk sinkronisasi /daftar lokal tanpa webhook publik.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $limit = 50): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $token = trim((string) config('services.telegram.bot_token'));

        try {
            $params = ['limit' => $limit];
            if ($offset > 0) {
                $params['offset'] = $offset;
            }

            $response = $this->client()->get(self::API_BASE . $token . '/getUpdates', $params);
            if ($response->successful() && $response->json('ok') === true) {
                return (array) $response->json('result', []);
            }
        } catch (\Throwable $e) {
            Log::warning('[Telegram] Exception saat getUpdates.', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Format pesan alert pelanggaran Gate Security UHF RFID.
     */
    public function buildGateViolationMessage(GateLog $gateLog, array $context = []): string
    {
        $rawItem = $gateLog->item;
        $item = ($rawItem && ! $rawItem->trashed()) ? $rawItem : null;
        $itemName = $item?->name ?? ($context['item_name'] ?? 'EPC Tag Tidak Terdaftar');
        $itemCode = $item?->item_code ?? ($context['item_code'] ?? null);
        $itemLocation = $item?->location ?? ($context['item_location'] ?? null);
        $itemStatus = $item?->status?->label() ?? ($context['item_status'] ?? 'Tidak Terdaftar');
        $reason = $context['reason'] ?? 'Aset melintasi gerbang tanpa izin peminjaman aktif.';
        $timeWib = ($gateLog->created_at ?? now())->timezone('Asia/Jakarta')->format('d M Y H:i:s') . ' WIB';

        $lines = [
            '<b>[PELANGGARAN GATE SECURITY LAB SIJA]</b>',
            '',
            '<b>Waktu:</b> <code>' . $this->escapeHtml($timeWib) . '</code>',
            '<b>Barang:</b> ' . $this->escapeHtml(Str::limit($itemName, 120)),
        ];

        if (filled($itemCode)) {
            $lines[] = '<b>Kode Aset:</b> <code>' . $this->escapeHtml($itemCode) . '</code>';
        }

        $lines[] = '<b>EPC Tag:</b> <code>' . $this->escapeHtml($gateLog->epc_tag_scanned) . '</code>';

        if (filled($itemLocation)) {
            $lines[] = '<b>Lokasi Asal:</b> ' . $this->escapeHtml(Str::limit($itemLocation, 100));
        }

        $lines[] = '<b>Status Barang:</b> <code>' . $this->escapeHtml((string) $itemStatus) . '</code>';
        $lines[] = '<b>Keterangan:</b> ' . $this->escapeHtml(Str::limit($reason, 220));
        $lines[] = '';
        $lines[] = '<i>Sirine alarm diaktifkan. Harap petugas segera memeriksa pintu laboratorium.</i>';

        return implode("\n", $lines);
    }

    /**
     * Format pesan alert terpadu saat beberapa tag ilegal terdeteksi bersamaan (multi-tag burst).
     * Menjaga total panjang tetap di bawah batas 1024 karakter Telegram sendPhoto sambil mempertahankan HTML valid.
     *
     * @param  Collection<int, GateLog>  $gateLogs
     * @param  array<int, array<string, mixed>>  $contextsByLogId
     */
    public function buildGateBatchViolationMessage(Collection $gateLogs, array $contextsByLogId = []): string
    {
        if ($gateLogs->count() <= 1) {
            $singleLog = $gateLogs->first();
            if (! $singleLog) {
                return '<b>[PELANGGARAN GATE SECURITY LAB SIJA]</b>';
            }

            return $this->buildGateViolationMessage($singleLog, $contextsByLogId[$singleLog->id] ?? []);
        }

        $firstLog = $gateLogs->first();
        $timeWib = ($firstLog->created_at ?? now())->timezone('Asia/Jakarta')->format('d M Y H:i:s') . ' WIB';
        $totalCount = $gateLogs->count();

        $headerLines = [
            '<b>[PELANGGARAN GATE SECURITY LAB SIJA — ' . $totalCount . ' ITEM]</b>',
            '',
            '<b>Waktu:</b> <code>' . $this->escapeHtml($timeWib) . '</code>',
            '<b>Total Tag Ditolak:</b> <code>' . $totalCount . '</code> item bersamaan',
            '',
        ];

        $footer = "\n\n<i>Sirine alarm diaktifkan. Harap petugas segera memeriksa pintu laboratorium.</i>";
        $overflowTemplate = "\n<i>... dan %d item lainnya (lihat Gate Logs web admin).</i>";

        // Sisakan margin aman agar total < 1020 karakter batas sendPhoto HTML Telegram.
        $maxBodyBudget = 960 - mb_strlen($footer) - mb_strlen(sprintf($overflowTemplate, $totalCount));
        $rendered = implode("\n", $headerLines);
        $includedCount = 0;

        foreach ($gateLogs->values() as $idx => $gateLog) {
            $context = $contextsByLogId[$gateLog->id] ?? [];
            $rawItem = $gateLog->item;
            $item = ($rawItem && ! $rawItem->trashed()) ? $rawItem : null;
            $itemName = $item?->name ?? ($context['item_name'] ?? 'EPC Tag Tidak Terdaftar');
            $itemCode = $item?->item_code ?? ($context['item_code'] ?? null);
            $itemStatus = $item?->status?->label() ?? ($context['item_status'] ?? 'Tidak Terdaftar');

            $number = $idx + 1;
            $codePart = filled($itemCode) ? ' (<code>' . $this->escapeHtml($itemCode) . '</code>)' : '';
            $entry = $number . '. <b>' . $this->escapeHtml(Str::limit($itemName, 60)) . '</b>' . $codePart
                . "\n   EPC: <code>" . $this->escapeHtml($gateLog->epc_tag_scanned) . '</code> · Status: <code>' . $this->escapeHtml((string) $itemStatus) . '</code>';

            $prefix = $includedCount > 0 ? "\n" : '';
            if (mb_strlen($rendered . $prefix . $entry) > $maxBodyBudget) {
                break;
            }

            $rendered .= $prefix . $entry;
            $includedCount++;
        }

        $remaining = $totalCount - $includedCount;
        if ($remaining > 0) {
            $rendered .= sprintf($overflowTemplate, $remaining);
        }

        return $rendered . $footer;
    }

    /**
     * Format pesan pengingat keterlambatan peminjaman untuk Grup/Admin Lab.
     */
    public function buildOverdueStaffMessage(Loan $loan, Carbon $now, int $reminderNumber): string
    {
        $user = $loan->user;
        $item = $loan->item;
        $lateness = self::formatOverdueDuration($loan->due_at, $now);
        $dueText = $loan->due_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB';

        $waNormalized = $this->formatWhatsAppNumber($user?->phone);
        $phoneDisplay = filled($user?->phone)
            ? ($waNormalized ? '<a href="https://wa.me/' . $waNormalized . '">' . $this->escapeHtml($user->phone) . '</a>' : $this->escapeHtml($user->phone))
            : '<i>Tidak terdaftar</i>';

        $nisPart = filled($user?->nis_nip) ? ' (<code>' . $this->escapeHtml($user->nis_nip) . '</code>)' : '';
        $codePart = filled($item?->item_code) ? ' (<code>' . $this->escapeHtml($item->item_code) . '</code>)' : '';

        $lines = [
            '<b>[PENGINGAT KETERLAMBATAN PEMINJAMAN]</b> (<code>#' . $reminderNumber . '</code>)',
            '',
            '<b>Peminjam:</b> ' . $this->escapeHtml($user?->name ?? 'Tidak diketahui') . $nisPart,
            '<b>Kontak WA:</b> ' . $phoneDisplay,
            '<b>Barang:</b> ' . $this->escapeHtml(Str::limit($item?->name ?? '-', 120)) . $codePart,
        ];

        if (filled($item?->location)) {
            $lines[] = '<b>Lokasi Rak:</b> ' . $this->escapeHtml($item->location);
        }

        $lines[] = '<b>Batas Kembali:</b> <code>' . $this->escapeHtml($dueText) . '</code>';
        $lines[] = '<b>Lama Terlambat:</b> <b>' . $this->escapeHtml($lateness) . ' terlambat</b>';
        $lines[] = '';
        $lines[] = '<i>Mohon hubungi peminjam untuk segera mengembalikan alat ke Lab SIJA.</i>';

        return implode("\n", $lines);
    }

    /**
     * Format pesan DM pribadi ke siswa/peminjam apabila telegram_chat_id sudah tertaut.
     */
    public function buildOverdueBorrowerMessage(Loan $loan, Carbon $now): string
    {
        $user = $loan->user;
        $item = $loan->item;
        $lateness = self::formatOverdueDuration($loan->due_at, $now);
        $dueText = $loan->due_at->copy()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB';
        $codePart = filled($item?->item_code) ? ' (<code>' . $this->escapeHtml($item->item_code) . '</code>)' : '';

        return implode("\n", [
            '<b>[PENGINGAT PENGEMBALIAN ALAT LAB SIJA]</b>',
            '',
            'Halo <b>' . $this->escapeHtml($user?->name ?? 'Siswa') . '</b>,',
            'Alat laboratorium yang Anda pinjam sudah melewati batas pengembalian:',
            '',
            '<b>Barang:</b> ' . $this->escapeHtml(Str::limit($item?->name ?? '-', 120)) . $codePart,
            '<b>Batas Kembali:</b> <code>' . $this->escapeHtml($dueText) . '</code>',
            '<b>Status Keterlambatan:</b> <b>' . $this->escapeHtml($lateness) . ' terlambat</b>',
            '',
            'Harap segera kembalikan ke Laboratorium SIJA melalui Kiosk Mandiri atau temui teknisi lab.',
        ]);
    }

    /**
     * Format pesan pengingat jadwal preventive maintenance (H-7, H-3, H-1, Hari-H, atau Lewat Jadwal).
     */
    public function buildPreventiveMaintenanceMessage(PreventiveSchedule $schedule, int $daysRemaining): string
    {
        $item = $schedule->item;
        $dueFormatted = $schedule->next_due_date->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y');

        if ($daysRemaining < 0) {
            $statusLine = 'TERLAMBAT ' . abs($daysRemaining) . ' HARI dari jadwal';
            $header = '<b>[PERAWATAN PREVENTIF TERLAMBAT]</b>';
        } elseif ($daysRemaining === 0) {
            $statusLine = 'JADWAL HARI INI';
            $header = '<b>[JADWAL PERAWATAN PREVENTIF HARI INI]</b>';
        } else {
            $statusLine = 'Sisa ' . $daysRemaining . ' hari lagi (H-' . $daysRemaining . ')';
            $header = '<b>[PENGINGAT PERAWATAN PREVENTIF ALAT]</b>';
        }

        $codePart = filled($item?->item_code) ? ' (<code>' . $this->escapeHtml($item->item_code) . '</code>)' : '';

        $lines = [
            $header,
            '',
            '<b>Alat:</b> ' . $this->escapeHtml(Str::limit($item?->name ?? '-', 120)) . $codePart,
            '<b>Lokasi:</b> ' . $this->escapeHtml($item?->location ?? '-'),
            '<b>Jadwal Maintenance:</b> <code>' . $this->escapeHtml($dueFormatted) . '</code>',
            '<b>Countdown:</b> <b>' . $this->escapeHtml($statusLine) . '</b>',
            '<b>Kegiatan:</b> ' . $this->escapeHtml(Str::limit($schedule->title, 150)),
        ];

        if (filled($schedule->notes)) {
            $lines[] = '<b>Catatan Teknis:</b> ' . $this->escapeHtml(Str::limit($schedule->notes, 250));
        }

        $lines[] = '';
        $lines[] = '<i>Silakan lakukan perawatan &amp; catat selesai pada menu Maintenance &amp; Repair SILAMET.</i>';

        return implode("\n", $lines);
    }

    /**
     * Format pesan alert tiket kerusakan baru atau tiket selesai diperbaiki.
     */
    public function buildMaintenanceTicketMessage(MaintenanceTicket $ticket, string $event = 'created'): string
    {
        $item = $ticket->item;
        $reporter = $ticket->reportedBy;
        $codePart = filled($item?->item_code) ? ' (<code>' . $this->escapeHtml($item->item_code) . '</code>)' : '';
        $timeWib = ($ticket->updated_at ?? $ticket->created_at ?? now())->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB';

        if ($event === 'resolved') {
            return implode("\n", [
                '<b>[ALAT SELESAI DIPERBAIKI &amp; SIAP DIPINJAM]</b>',
                '',
                '<b>Tiket:</b> <code>#' . $ticket->id . '</code>',
                '<b>Alat:</b> ' . $this->escapeHtml(Str::limit($item?->name ?? '-', 120)) . $codePart,
                '<b>Lokasi:</b> ' . $this->escapeHtml($item?->location ?? '-'),
                '<b>Status Barang:</b> <code>TERSEDIA</code>',
                '<b>Waktu Selesai:</b> <code>' . $this->escapeHtml($timeWib) . '</code>',
            ]);
        }

        $reporterName = $reporter?->name ?? 'Siswa Kiosk';
        $reporterNis = filled($reporter?->nis_nip) ? ' (<code>' . $this->escapeHtml($reporter->nis_nip) . '</code>)' : '';

        return implode("\n", [
            '<b>[LAPORAN KERUSAKAN ALAT BARU]</b>',
            '',
            '<b>Tiket:</b> <code>#' . $ticket->id . '</code>',
            '<b>Alat:</b> ' . $this->escapeHtml(Str::limit($item?->name ?? '-', 120)) . $codePart,
            '<b>Lokasi:</b> ' . $this->escapeHtml($item?->location ?? '-'),
            '<b>Pelapor:</b> ' . $this->escapeHtml($reporterName) . $reporterNis,
            '<b>Kendala:</b> ' . $this->escapeHtml(Str::limit($ticket->issue_description, 300)),
            '<b>Status Barang:</b> <code>UNDER_MAINTENANCE (Terkunci dari Kiosk)</code>',
            '<b>Waktu Lapor:</b> <code>' . $this->escapeHtml($timeWib) . '</code>',
        ]);
    }

    /**
     * Format pesan ringkasan operasional harian Laboratorium SIJA.
     *
     * @param  array<string, int>  $stats
     */
    public function buildDailySummaryMessage(array $stats, Carbon $date): string
    {
        $dateLabel = $date->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y');

        return implode("\n", [
            '<b>[RINGKASAN HARIAN LAB SIJA — SILAMET]</b>',
            '<b>Tanggal:</b> <code>' . $this->escapeHtml($dateLabel) . '</code>',
            '',
            '<b>Peminjaman Aktif:</b> <code>' . (int) ($stats['active_loans'] ?? 0) . '</code> unit',
            '<b>Peminjaman Terlambat:</b> <code>' . (int) ($stats['overdue_loans'] ?? 0) . '</code> unit',
            '<b>Tiket Rusak Aktif (M&amp;R):</b> <code>' . (int) ($stats['open_tickets'] ?? 0) . '</code> tiket',
            '<b>Jadwal Preventif (&lt;= 7 Hari):</b> <code>' . (int) ($stats['due_maintenance'] ?? 0) . '</code> jadwal',
            '<b>Pelanggaran Gate Hari Ini:</b> <code>' . (int) ($stats['today_gate_denied'] ?? 0) . '</code> kejadian',
            '',
            '<i>Pantau detail operasional melalui dashboard web admin SILAMET.</i>',
        ]);
    }
}

