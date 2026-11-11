<?php

namespace Tests\Unit;

use App\Enums\GateAction;
use App\Enums\ItemStatus;
use App\Models\GateLog;
use App\Models\Item;
use App\Services\CameraService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_formats_overdue_duration_accurately_in_indonesian(): void
    {
        $now = Carbon::parse('2026-11-10 18:15:00', 'Asia/Jakarta');

        $this->assertSame('0 menit', TelegramService::formatOverdueDuration($now->copy(), $now));
        $this->assertSame('kurang dari 1 menit', TelegramService::formatOverdueDuration($now->copy()->subSeconds(30), $now));
        $this->assertSame('25 menit', TelegramService::formatOverdueDuration($now->copy()->subMinutes(25), $now));
        $this->assertSame('2 jam', TelegramService::formatOverdueDuration($now->copy()->subHours(2), $now));
        $this->assertSame('3 jam 15 menit', TelegramService::formatOverdueDuration($now->copy()->subHours(3)->subMinutes(15), $now));
        $this->assertSame('2 hari 4 jam', TelegramService::formatOverdueDuration($now->copy()->subDays(2)->subHours(4), $now));
    }

    public function test_escapes_html_and_normalizes_whatsapp_numbers(): void
    {
        $service = new TelegramService();

        $this->assertSame(
            'Switch &lt;Core&gt; &amp; &quot;Cable&quot;',
            $service->escapeHtml('Switch <Core> & "Cable"')
        );
        $this->assertSame('6281234567890', $service->formatWhatsAppNumber('0812-3456-7890'));
        $this->assertSame('6289911223344', $service->formatWhatsAppNumber('+62 899 1122 3344'));
        $this->assertNull($service->formatWhatsAppNumber(''));
    }

    public function test_resolves_unique_recipients_per_channel(): void
    {
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.admin_chat_id', '123456789');

        $service = new TelegramService();

        $this->assertSame(['-1001234567890', '123456789'], $service->resolveRecipients('gate'));
        $this->assertSame(['-1001234567890'], $service->resolveRecipients('ticket_created'));
        $this->assertSame(['123456789'], $service->resolveRecipients('admin'));

        Config::set('services.telegram.admin_chat_id', '-1001234567890');
        $this->assertSame(['-1001234567890'], $service->resolveRecipients('gate'));
    }

    public function test_send_message_and_photo_fail_safely(): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'fake-token');

        Http::fake([
            'https://api.telegram.org/botfake-token/sendMessage' => Http::sequence()
                ->push(['ok' => true, 'result' => ['message_id' => 101]], 200)
                ->push(['ok' => false, 'description' => 'Internal error'], 500)
                ->push(['ok' => true, 'result' => ['message_id' => 102]], 200),
            'https://api.telegram.org/botfake-token/sendPhoto' => Http::response(['ok' => true, 'result' => ['message_id' => 103]], 200),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('uploads/photos/gate/sample.png', 'binary-image-data');

        $service = new TelegramService();

        $this->assertTrue($service->sendMessage('-1001234567890', '<b>Halo</b>'));
        $this->assertFalse($service->sendMessage('-1001234567890', '<b>Gagal 500</b>'));
        $this->assertTrue($service->sendPhoto('-1001234567890', 'uploads/photos/gate/sample.png', '<b>Bukti Foto</b>'));
        // Fallback ke sendMessage bila file tidak ada di storage
        $this->assertTrue($service->sendPhoto('-1001234567890', 'uploads/photos/gate/missing.png', '<b>Fallback Teks</b>'));

        Http::assertSentCount(4);
    }

    public function test_camera_service_and_telegram_normalize_jpeg_extension_and_mime(): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'fake-token');

        Http::fake([
            'https://api.telegram.org/botfake-token/sendPhoto' => Http::response(['ok' => true, 'result' => ['message_id' => 205]], 200),
        ]);

        Storage::fake('public');

        $jpegBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIFfake-jpeg-payload";
        $camera = new CameraService();
        $savedPath = $camera->capture($jpegBytes, 'photos/gate');

        $this->assertStringEndsWith('.jpg', $savedPath);

        // Uji juga file legacy berekstensi .png namun berisi bytes JPEG
        Storage::disk('public')->put('uploads/photos/gate/legacy.png', $jpegBytes);

        $service = new TelegramService();
        $this->assertTrue($service->sendPhoto('-1001234567890', 'uploads/photos/gate/legacy.png', '<b>Snapshot Gate</b>'));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/sendPhoto') || ! $request->isMultipart()) {
                return false;
            }

            foreach ($request->data() as $part) {
                if (($part['name'] ?? null) === 'photo') {
                    return ($part['filename'] ?? null) === 'legacy.jpg';
                }
            }

            return false;
        });
    }

    public function test_batch_gate_violation_message_respects_telegram_caption_limit(): void
    {
        $service = new TelegramService();
        $logs = new Collection();

        for ($i = 1; $i <= 20; $i++) {
            $item = new Item([
                'name' => "Perangkat Praktikum Jaringan MikroTik & Switch Core Lab SIJA #{$i}",
                'item_code' => sprintf('SLM-LAB-%03d', $i),
                'status' => ItemStatus::TERSEDIA,
            ]);

            $log = new GateLog([
                'epc_tag_scanned' => sprintf('E28068940000%04d', $i),
                'action_status' => GateAction::DENIED,
            ]);
            $log->id = $i;
            $log->created_at = Carbon::parse('2026-11-10 12:30:00', 'Asia/Jakarta');
            $log->setRelation('item', $item);

            $logs->push($log);
        }

        $message = $service->buildGateBatchViolationMessage($logs);

        $this->assertLessThanOrEqual(1020, mb_strlen($message));
        $this->assertStringContainsString('20 ITEM', $message);
        $this->assertStringContainsString('MikroTik &amp; Switch Core', $message);
        $this->assertStringContainsString('item lainnya', $message);
        $this->assertStringContainsString('<i>Sirine alarm diaktifkan', $message);
    }
}

