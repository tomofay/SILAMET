<?php

namespace Tests\Feature\Api;

use App\Jobs\SendGateViolationTelegramJob;
use App\Models\GateLog;
use App\Models\Item;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GateTelegramAlertTest extends TestCase
{
    use RefreshDatabase;

    private function enableFakeTelegram(): void
    {
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'test-gate-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.admin_chat_id', '123456789');
        Config::set('services.telegram.gate_cooldown_seconds', 15);
    }

    public function test_allowed_gate_scan_does_not_send_telegram_alert(): void
    {
        $this->enableFakeTelegram();
        Http::fake();

        Item::factory()->borrowed()->create(['epc_tag' => 'EPC-ALLOWED-01']);

        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-ALLOWED-01',
        ])->assertOk()->assertJsonPath('status', 'allowed');

        Http::assertNothingSent();
    }

    public function test_denied_gate_scan_without_photo_sends_telegram_text_alert(): void
    {
        $this->enableFakeTelegram();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        Item::factory()->available()->create([
            'name' => 'MikroTik RB951Ui',
            'item_code' => 'SLM-MK-01',
            'epc_tag' => 'EPC-DENIED-TEXT',
            'location' => 'Rak Jaringan 1',
        ]);

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-DENIED-TEXT',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied')
            ->assertJsonStructure(['gate_log_id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && str_contains((string) $request['text'], 'MikroTik RB951Ui')
                && str_contains((string) $request['text'], 'SLM-MK-01');
        });
        Http::assertSentCount(2); // Group + Admin
    }

    public function test_denied_gate_scan_with_photo_sends_telegram_multipart_photo(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        Item::factory()->underMaintenance()->create([
            'name' => 'Fusion Splicer AI-9',
            'epc_tag' => 'EPC-DENIED-PHOTO',
        ]);

        $fakeBase64 = 'data:image/png;base64,' . base64_encode('fake-jpeg-bytes');

        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-DENIED-PHOTO',
            'photo' => $fakeBase64,
        ])->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendPhoto');
        });
        Http::assertSentCount(2);
    }

    public function test_second_stage_photo_upload_attaches_to_existing_gate_log_without_duplicate(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 3]], 200),
        ]);

        Item::factory()->available()->create([
            'epc_tag' => 'EPC-TWO-STAGE',
        ]);

        $first = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-TWO-STAGE',
        ])->assertOk();

        $gateLogId = $first->json('gate_log_id');
        $this->assertNotNull($gateLogId);

        $second = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-TWO-STAGE',
            'gate_log_id' => $gateLogId,
            'photo' => 'data:image/png;base64,' . base64_encode('stage2-image'),
        ]);

        $second->assertOk()
            ->assertJsonPath('photo_attached', true)
            ->assertJsonPath('gate_log_id', $gateLogId);

        $this->assertSame(1, GateLog::where('epc_tag_scanned', 'EPC-TWO-STAGE')->count());
        $this->assertNotNull(GateLog::find($gateLogId)->capture_image_path);

        // Karena mode pengujian menggunakan queue sync, fallback tahap-1 mengirim teks dan tahap-2 tetap menyusul mengirim foto.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
        Http::assertSentCount(4);
    }

    public function test_async_second_stage_photo_sends_photo_and_suppresses_delayed_text_fallback(): void
    {
        $this->enableFakeTelegram();
        Config::set('queue.default', 'database');
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]], 200),
        ]);

        Item::factory()->available()->create([
            'name' => 'Switch Manageable Lab SIJA',
            'epc_tag' => 'EPC-ASYNC-RACE',
        ]);

        // Tahap 1: scan tanpa foto menjadwalkan job fallback (delay 4s) di tabel jobs
        $first = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-ASYNC-RACE',
        ])->assertOk();

        $gateLogId = $first->json('gate_log_id');
        Http::assertNothingSent();

        // Tahap 2: webcam mengirim JPEG 1 detik kemudian
        $jpegBase64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFstage2");
        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-ASYNC-RACE',
            'gate_log_id' => $gateLogId,
            'photo' => $jpegBase64,
        ])->assertOk()->assertJsonPath('photo_attached', true);

        $queuedJobs = DB::table('jobs')->orderByDesc('id')->get();
        $this->assertCount(2, $queuedJobs);

        $telegram = app(TelegramService::class);

        // Eksekusi job tahap ke-2 (immediate) terlebih dahulu, disusul job fallback tahap ke-1
        foreach ($queuedJobs as $row) {
            $payload = json_decode($row->payload, true);
            /** @var SendGateViolationTelegramJob $job */
            $job = unserialize($payload['data']['command']);
            $job->handle($telegram);
        }

        // Hanya sendPhoto (Group + Admin = 2 request) yang terkirim; fallback tahap-1 tidak mengirim duplikat.
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage'));
    }

    public function test_gate_cooldown_suppresses_duplicate_telegram_spam_within_window(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 4]], 200),
        ]);

        Item::factory()->available()->create([
            'epc_tag' => 'EPC-COOLDOWN-TEST',
        ]);

        // Scan 1: kirim ke group + admin (2 HTTP calls)
        $this->postJson('/api/v1/gate/check-tag', ['epc_tag' => 'EPC-COOLDOWN-TEST'])->assertOk();
        // Scan 2 langsung menyusul beserta tahap-2 foto: tetap tercatat di gate_logs tapi tidak spam Telegram
        $scan2 = $this->postJson('/api/v1/gate/check-tag', ['epc_tag' => 'EPC-COOLDOWN-TEST'])->assertOk();
        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-COOLDOWN-TEST',
            'gate_log_id' => $scan2->json('gate_log_id'),
            'photo' => 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFcooldown"),
        ])->assertOk()->assertJsonPath('photo_attached', true);

        $this->assertSame(2, GateLog::where('epc_tag_scanned', 'EPC-COOLDOWN-TEST')->count());
        Http::assertSentCount(2);
    }

    public function test_multi_tag_burst_with_photo_saves_one_image_and_sends_one_consolidated_alert(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 11]], 200),
        ]);

        Item::factory()->borrowed()->create([
            'name' => 'Tang Krimping Valid',
            'epc_tag' => 'EPC-MULTI-ALLOW',
        ]);
        Item::factory()->available()->create([
            'name' => 'MikroTik RB750Gr3',
            'item_code' => 'SLM-MK-10',
            'epc_tag' => 'EPC-MULTI-DENY-1',
        ]);
        Item::factory()->underMaintenance()->create([
            'name' => 'Optical Power Meter',
            'item_code' => 'SLM-OPM-02',
            'epc_tag' => 'EPC-MULTI-DENY-2',
        ]);

        $jpegBase64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFburstframe");

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tags' => [
                'EPC-MULTI-ALLOW',
                'EPC-MULTI-DENY-1',
                'EPC-MULTI-DENY-2',
                'EPC-MULTI-UNKNOWN',
            ],
            'photo' => $jpegBase64,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied')
            ->assertJsonPath('trigger_sirene', true)
            ->assertJsonPath('photo_attached', true)
            ->assertJsonPath('batch_count', 4)
            ->assertJsonPath('allowed_count', 1)
            ->assertJsonPath('denied_count', 3);

        $this->assertSame(4, GateLog::count());

        $allowLog = GateLog::where('epc_tag_scanned', 'EPC-MULTI-ALLOW')->first();
        $denyLogs = GateLog::where('action_status', 'denied')->get();

        $this->assertNull($allowLog->capture_image_path);
        $this->assertCount(3, $denyLogs);

        $uniquePaths = $denyLogs->pluck('capture_image_path')->filter()->unique()->values();
        $this->assertCount(1, $uniquePaths);
        $this->assertCount(1, Storage::disk('public')->allFiles('uploads/photos/gate'));

        // Hanya 1 pesan terpadu + 1 foto dikirim ke 2 chat tujuan (Group + Admin = 2 request, bukan 6!)
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/sendPhoto') || ! $request->isMultipart()) {
                return false;
            }

            foreach ($request->data() as $part) {
                if (($part['name'] ?? null) === 'caption') {
                    return str_contains((string) $part['contents'], '3 ITEM')
                        && str_contains((string) $part['contents'], 'MikroTik RB750Gr3')
                        && str_contains((string) $part['contents'], 'Optical Power Meter');
                }
            }

            return false;
        });
    }

    public function test_async_multi_tag_second_stage_attaches_one_photo_to_all_denied_logs(): void
    {
        $this->enableFakeTelegram();
        Config::set('queue.default', 'database');
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 15]], 200),
        ]);

        Item::factory()->available()->create(['name' => 'Switch L2', 'epc_tag' => 'EPC-M2-1']);
        Item::factory()->available()->create(['name' => 'Router AP', 'epc_tag' => 'EPC-M2-2']);

        $stage1 = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tags' => ['EPC-M2-1', 'EPC-M2-2'],
        ])->assertOk()
            ->assertJsonPath('status', 'denied')
            ->assertJsonPath('trigger_capture', true);

        $deniedIds = $stage1->json('denied_gate_log_ids');
        $this->assertCount(2, $deniedIds);

        $jpegBase64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFstage2multi");
        $this->postJson('/api/v1/gate/check-tag', [
            'gate_log_ids' => $deniedIds,
            'photo' => $jpegBase64,
        ])->assertOk()->assertJsonPath('photo_attached', true);

        $queuedJobs = DB::table('jobs')->orderByDesc('id')->get();
        $this->assertCount(2, $queuedJobs);

        $telegram = app(TelegramService::class);
        foreach ($queuedJobs as $row) {
            $payload = json_decode($row->payload, true);
            /** @var SendGateViolationTelegramJob $job */
            $job = unserialize($payload['data']['command']);
            $job->handle($telegram);
        }

        // 1 file foto dipakai bersama kedua log, 1 batch alert foto ke Group & Admin
        $this->assertCount(1, Storage::disk('public')->allFiles('uploads/photos/gate'));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage'));
    }

    public function test_multi_tag_burst_filters_recently_cooled_down_tags_individually(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 21]], 200),
        ]);

        Item::factory()->available()->create(['name' => 'Tang Krimping Lama', 'epc_tag' => 'EPC-REPEAT-1']);
        Item::factory()->available()->create(['name' => 'MikroTik Baru', 'epc_tag' => 'EPC-NEW-2']);

        $jpegBase64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFburstcooldown");

        // Burst 1: hanya EPC-REPEAT-1
        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'EPC-REPEAT-1',
            'photo' => $jpegBase64,
        ])->assertOk();

        Http::assertSentCount(2);

        // Burst 2 langsung menyusul membawa EPC-REPEAT-1 (dalam cooldown) + EPC-NEW-2 (baru)
        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tags' => ['EPC-REPEAT-1', 'EPC-NEW-2'],
            'photo' => $jpegBase64,
        ])->assertOk();

        // Total log tercatat 3, namun Telegram burst ke-2 hanya melaporkan EPC-NEW-2 (2 call tambahan -> total 4)
        $this->assertSame(3, GateLog::count());
        Http::assertSentCount(4);
    }

    public function test_borrowed_and_soft_deleted_borrowed_items_never_show_dipinjam_in_telegram(): void
    {
        $this->enableFakeTelegram();
        Storage::fake('public');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 33]], 200),
        ]);

        // Barang aktif dipinjam -> tidak boleh ikut terkirim ke Telegram saat burst campuran
        Item::factory()->borrowed()->create([
            'name' => 'Laptop Praktikum Dipinjam',
            'item_code' => 'SLM-BORROWED-01',
            'epc_tag' => 'EPC-ACTIVE-BORROWED',
        ]);

        // Barang soft-deleted dengan sisa status DB 'dipinjam' -> harus dianggap tidak terdaftar (bukan Status: Dipinjam)
        $deletedBorrowed = Item::factory()->borrowed()->create([
            'name' => 'Mikrotik Lama Terhapus',
            'item_code' => 'SLM-DEL-01',
            'epc_tag' => 'EPC-DELETED-BORROWED',
        ]);
        $deletedBorrowed->delete();

        $jpegBase64 = 'data:image/jpeg;base64,' . base64_encode("\xFF\xD8\xFF\xE0\x00\x10JFIFnodipinjam");

        $this->postJson('/api/v1/gate/check-tag', [
            'epc_tags' => ['EPC-ACTIVE-BORROWED', 'EPC-DELETED-BORROWED'],
            'photo' => $jpegBase64,
        ])->assertOk()
            ->assertJsonPath('allowed_count', 1)
            ->assertJsonPath('denied_count', 1);

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/sendPhoto') || ! $request->isMultipart()) {
                return false;
            }

            foreach ($request->data() as $part) {
                if (($part['name'] ?? null) === 'caption') {
                    $caption = (string) $part['contents'];

                    return ! str_contains($caption, 'Laptop Praktikum Dipinjam')
                        && ! str_contains($caption, 'Mikrotik Lama Terhapus')
                        && ! str_contains($caption, 'Dipinjam')
                        && str_contains($caption, 'EPC-DELETED-BORROWED')
                        && str_contains($caption, 'Tidak Terdaftar');
                }
            }

            return false;
        });
    }
}

