<?php

namespace Tests\Feature\Api;

use App\Models\GateLog;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_allows_item_with_borrowed_status(): void
    {
        $item = Item::factory()->borrowed()->create([
            'epc_tag' => 'E280689400004001',
        ]);

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'E280689400004001',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'allowed')
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('gate_logs', [
            'epc_tag_scanned' => 'E280689400004001',
            'action_status' => 'allowed',
        ]);
    }

    public function test_gate_denies_item_with_available_status(): void
    {
        $item = Item::factory()->available()->create([
            'epc_tag' => 'E280689400004002',
        ]);

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'E280689400004002',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied')
            ->assertJsonPath('trigger_sirene', true)
            ->assertJsonPath('trigger_capture', true);

        $this->assertDatabaseHas('gate_logs', [
            'epc_tag_scanned' => 'E280689400004002',
            'action_status' => 'denied',
        ]);
    }

    public function test_gate_denies_item_with_under_maintenance_status(): void
    {
        $item = Item::factory()->underMaintenance()->create([
            'epc_tag' => 'E280689400004003',
        ]);

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'E280689400004003',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied');
    }

    public function test_gate_denies_unknown_tag(): void
    {
        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'E2806894UNKNOWN',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied');

        $this->assertDatabaseHas('gate_logs', [
            'epc_tag_scanned' => 'E2806894UNKNOWN',
            'action_status' => 'denied',
        ]);
    }

    public function test_gate_requires_epc_tag(): void
    {
        $response = $this->postJson('/api/v1/gate/check-tag', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('epc_tag');
    }

    public function test_gate_denied_event_stores_capture_photo(): void
    {
        $item = Item::factory()->available()->create([
            'epc_tag' => 'E280689400004004',
        ]);

        $fakeBase64 = 'data:image/png;base64,' . base64_encode('fake-image-content');

        $response = $this->postJson('/api/v1/gate/check-tag', [
            'epc_tag' => 'E280689400004004',
            'photo' => $fakeBase64,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'denied');

        $log = GateLog::where('epc_tag_scanned', 'E280689400004004')->first();
        $this->assertNotNull($log->capture_image_path);
    }
}
