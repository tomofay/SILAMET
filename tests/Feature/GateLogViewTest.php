<?php

namespace Tests\Feature;

use App\Enums\GateAction;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GateLogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_gate_logs_index_show_and_violation_photo(): void
    {
        Storage::fake('public');

        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->available()->create([
            'name' => 'MikroTik RB450Gx4',
            'epc_tag' => 'EPC-VIEW-001',
        ]);

        $jpegBinary = "\xFF\xD8\xFF\xE0\x00\x10JFIFgatephoto";
        $relativePath = 'uploads/photos/gate/test_snapshot.jpg';
        Storage::disk('public')->put($relativePath, $jpegBinary);

        $log = GateLog::create([
            'epc_tag_scanned' => 'EPC-VIEW-001',
            'action_status' => GateAction::DENIED,
            'capture_image_path' => $relativePath,
        ]);

        $photoUrl = route('gate-logs.photo', $log->id);

        $this->actingAs($teknisi)
            ->get('/gate-logs')
            ->assertOk()
            ->assertSee('MikroTik RB450Gx4')
            ->assertSee($photoUrl);

        $this->actingAs($teknisi)
            ->get('/gate-logs/' . $log->id)
            ->assertOk()
            ->assertSee('MikroTik RB450Gx4')
            ->assertSee($photoUrl);

        $photoResponse = $this->actingAs($teknisi)->get($photoUrl);
        $photoResponse->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($jpegBinary, $photoResponse->getContent());
    }

    public function test_gate_log_photo_endpoint_returns_404_when_missing_and_403_for_student(): void
    {
        Storage::fake('public');

        $teknisi = User::factory()->teknisi()->create();
        $student = User::factory()->siswa()->create();

        $logWithoutPhoto = GateLog::create([
            'epc_tag_scanned' => 'EPC-NO-PHOTO',
            'action_status' => GateAction::ALLOWED,
            'capture_image_path' => null,
        ]);

        $logMissingFile = GateLog::create([
            'epc_tag_scanned' => 'EPC-MISSING-FILE',
            'action_status' => GateAction::DENIED,
            'capture_image_path' => 'uploads/photos/gate/does_not_exist.jpg',
        ]);

        $this->actingAs($student)
            ->get(route('gate-logs.photo', $logWithoutPhoto->id))
            ->assertForbidden();

        $this->actingAs($teknisi)
            ->get(route('gate-logs.photo', $logWithoutPhoto->id))
            ->assertNotFound();

        $this->actingAs($teknisi)
            ->get(route('gate-logs.photo', $logMissingFile->id))
            ->assertNotFound();
    }

    public function test_soft_deleted_item_shows_deleted_badge_on_gate_logs(): void
    {
        $kabeng = User::factory()->kabeng()->create();
        $item = Item::factory()->available()->create([
            'name' => 'Switch Lama Dihapus',
            'epc_tag' => 'EPC-DEL-VIEW',
        ]);
        $item->delete();

        $log = GateLog::create([
            'epc_tag_scanned' => 'EPC-DEL-VIEW',
            'action_status' => GateAction::DENIED,
            'capture_image_path' => null,
        ]);

        $this->actingAs($kabeng)
            ->get('/gate-logs')
            ->assertOk()
            ->assertSee('Switch Lama Dihapus')
            ->assertSee('DIHAPUS');

        $this->actingAs($kabeng)
            ->get('/gate-logs/' . $log->id)
            ->assertOk()
            ->assertSee('Switch Lama Dihapus')
            ->assertSee('DIHAPUS');
    }

    public function test_gate_logs_feed_endpoint_supports_live_polling_and_violation_alerts(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $student = User::factory()->siswa()->create();

        $activeItem = Item::factory()->available()->create([
            'name' => 'Switch Manageable 24G',
            'epc_tag' => 'EPC-FEED-01',
        ]);

        $initialAllowed = GateLog::create([
            'epc_tag_scanned' => 'EPC-FEED-ALLOWED',
            'action_status' => GateAction::ALLOWED,
            'capture_image_path' => null,
        ]);

        // Siswa tidak boleh mengakses feed JSON staff
        $this->actingAs($student)
            ->getJson(route('gate-logs.feed'))
            ->assertForbidden();

        // Ketika tidak ada log baru (after_id == latest_id), changed = false dan tidak merender ulang HTML
        $this->actingAs($teknisi)
            ->getJson(route('gate-logs.feed', [
                'after_id' => $initialAllowed->id,
                'after_denied_id' => 0,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('changed', false)
            ->assertJsonPath('tbody_html', null)
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.allowed', 1)
            ->assertJsonPath('stats.denied', 0);

        // Pelanggaran baru masuk
        $deniedLog = GateLog::create([
            'epc_tag_scanned' => $activeItem->epc_tag,
            'action_status' => GateAction::DENIED,
            'capture_image_path' => 'uploads/photos/gate/live_denied.jpg',
        ]);

        $res = $this->actingAs($teknisi)
            ->getJson(route('gate-logs.feed', [
                'after_id' => $initialAllowed->id,
                'after_denied_id' => 0,
            ]))
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('latest_id', $deniedLog->id)
            ->assertJsonPath('latest_denied_id', $deniedLog->id)
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.denied', 1)
            ->assertJsonPath('latest_denied_alert.id', $deniedLog->id)
            ->assertJsonPath('latest_denied_alert.epc', 'EPC-FEED-01')
            ->assertJsonPath('latest_denied_alert.item_name', 'Switch Manageable 24G')
            ->assertJsonPath('latest_denied_alert.photo_url', route('gate-logs.photo', $deniedLog->id));

        $this->assertStringContainsString('Switch Manageable 24G', (string) $res->json('tbody_html'));
    }
}

