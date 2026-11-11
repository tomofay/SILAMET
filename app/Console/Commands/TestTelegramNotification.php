<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Uji koneksi Bot Telegram serta kirim pesan verifikasi ke Grup & Admin yang dikonfigurasi.';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isEnabled()) {
            $this->error('Telegram nonaktif atau TELEGRAM_BOT_TOKEN di .env belum diisi.');

            return self::FAILURE;
        }

        $botInfo = $telegram->getMe();
        if ($botInfo) {
            $username = $botInfo['username'] ?? '-';
            $this->info("Bot terhubung: @{$username} (ID: {$botInfo['id']})");
        } else {
            $this->warn('Gagal memverifikasi /getMe, tetap mencoba mengirim pesan uji...');
        }

        $recipients = $telegram->resolveRecipients('test');
        if (empty($recipients)) {
            $this->error('TELEGRAM_GROUP_CHAT_ID dan TELEGRAM_ADMIN_CHAT_ID belum diisi.');

            return self::FAILURE;
        }

        $timeWib = now()->timezone('Asia/Jakarta')->format('d M Y H:i:s') . ' WIB';
        $message = implode("\n", [
            '<b>[SILAMET — UJI KONEKSI TELEGRAM]</b>',
            '',
            'Bot notifikasi Laboratorium SIJA SMKN 1 Cibinong aktif dan terhubung.',
            '<b>Waktu Uji:</b> <code>' . $telegram->escapeHtml($timeWib) . '</code>',
            '<b>Target Aktif:</b> <code>' . $telegram->escapeHtml(implode(', ', $recipients)) . '</code>',
        ]);

        $result = $telegram->sendToChannel('test', $message);

        $this->info(sprintf(
            'Hasil pengiriman uji: %d berhasil, %d gagal (Target: %s).',
            $result['sent'],
            $result['failed'],
            implode(', ', $result['recipients'])
        ));

        return $result['sent'] > 0 ? self::SUCCESS : self::FAILURE;
    }
}

