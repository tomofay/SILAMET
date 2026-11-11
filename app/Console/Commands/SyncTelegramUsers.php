<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncTelegramUsers extends Command
{
    protected $signature = 'telegram:sync-updates';

    protected $description = 'Sinkronisasi perintah /daftar <NIS_NIP> dari bot Telegram secara lokal (getUpdates polling) untuk menautkan akun user.';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isEnabled()) {
            $this->warn('Integrasi Telegram nonaktif atau token belum dikonfigurasi.');

            return self::SUCCESS;
        }

        $lastOffset = (int) Cache::get('telegram:updates_offset', 0);
        $updates = $telegram->getUpdates($lastOffset > 0 ? $lastOffset + 1 : 0);

        if (empty($updates)) {
            $this->info('Tidak ada pembaruan pesan Telegram baru.');

            return self::SUCCESS;
        }

        $maxUpdateId = $lastOffset;
        $linked = 0;

        foreach ($updates as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            if ($updateId > $maxUpdateId) {
                $maxUpdateId = $updateId;
            }

            $message = $update['message'] ?? null;
            if (! is_array($message)) {
                continue;
            }

            $chatType = $message['chat']['type'] ?? null;
            $chatId = (string) ($message['chat']['id'] ?? '');
            $text = trim((string) ($message['text'] ?? ''));

            // Hanya proses pesan pribadi untuk penautan akun siswa/staf
            if ($chatType !== 'private' || $chatId === '' || $text === '') {
                continue;
            }

            if (preg_match('/^\/(?:daftar|start)(?:@\S+)?(?:\s+([A-Za-z0-9\-_]+))?$/i', $text, $matches)) {
                $identifier = trim((string) ($matches[1] ?? ''));

                if ($identifier === '') {
                    $telegram->sendMessage(
                        $chatId,
                        "Halo! Untuk menautkan akun <b>SILAMET</b> Anda dengan bot ini, kirim pesan:\n<code>/daftar NIS_ATAU_NIP_ANDA</code>"
                    );

                    continue;
                }

                $user = User::query()
                    ->where('nis_nip', $identifier)
                    ->orWhere('barcode_token', $identifier)
                    ->first();

                if (! $user) {
                    $telegram->sendMessage(
                        $chatId,
                        'NIS/NIP atau kode kartu <code>' . $telegram->escapeHtml($identifier) . '</code> tidak ditemukan di database SILAMET. Periksa kembali nomor Anda.'
                    );

                    continue;
                }

                // Pastikan chat_id unik tidak dipakai akun lain
                User::query()
                    ->where('telegram_chat_id', $chatId)
                    ->where('id', '!=', $user->id)
                    ->update(['telegram_chat_id' => null]);

                $user->update(['telegram_chat_id' => $chatId]);
                $linked++;

                $telegram->sendMessage(
                    $chatId,
                    'Akun Telegram Anda berhasil ditautkan ke <b>'
                    . $telegram->escapeHtml($user->name)
                    . '</b> (<code>'
                    . $telegram->escapeHtml((string) $user->nis_nip)
                    . '</code>).'
                );
            }
        }

        if ($maxUpdateId > $lastOffset) {
            Cache::forever('telegram:updates_offset', $maxUpdateId);
        }

        $this->info(sprintf('Pembaruan Telegram diproses: %d update, %d akun ditautkan.', count($updates), $linked));

        return self::SUCCESS;
    }
}

