<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const DRIVER_LOG = 'log';
    private const DRIVER_FONNTE = 'fonnte';

    /**
     * Kirim pesan WhatsApp ke satu nomor.
     * Nomor target otomatis dinormalisasi ke format internasional (62xxx).
     */
    public function send(string $phone, string $message): bool
    {
        $driver = config('services.whatsapp.driver', self::DRIVER_LOG);
        $phone = $this->normalizePhone($phone);

        if (blank($phone)) {
            Log::warning('[WhatsApp] Nomor tujuan kosong.', ['phone' => $phone]);

            return false;
        }

        if ($driver === self::DRIVER_FONNTE) {
            return $this->sendViaFonnte($phone, $message);
        }

        return $this->logMessage($phone, $message);
    }

    /**
     * Kirim pesan overdue (otomatis memakai template baku).
     */
    public function sendOverdueNotification(string $phone, string $studentName, string $itemName, string $dueAt): bool
    {
        $message = sprintf(
            "⚠️ *PENGINGAT PENGEMBALIAN ALAT LAB*\n\n".
            "Halo *%s*,\n\n".
            "Kami mendeteksi alat lab yang Anda pinjam belum dikembalikan:\n".
            "• Barang: *%s*\n".
            "• Batas pengembalian: *%s*\n\n".
            "Mohon segera kembalikan ke laboratorium SIJA. ".
            "Terima kasih 🙏",
            $studentName,
            $itemName,
            $dueAt
        );

        return $this->send($phone, $message);
    }

    private function sendViaFonnte(string $phone, string $message): bool
    {
        $token = config('services.whatsapp.token');

        if (blank($token)) {
            Log::error('[WhatsApp] Token Fonnte belum dikonfigurasi.');

            return false;
        }

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                ]);

            $ok = $response->successful();

            if (! $ok) {
                Log::error('[WhatsApp] Gagal mengirim via Fonnte.', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Exception saat mengirim via Fonnte.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function logMessage(string $phone, string $message): bool
    {
        Log::info('[WhatsApp][log-driver] Pesan siap dikirim.', [
            'to' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}
