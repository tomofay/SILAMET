<?php

namespace Tests\Feature\Api;

use App\Enums\ItemStatus;
use App\Enums\TicketStatus;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeknisi(): array
    {
        $teknisi = User::factory()->teknisi()->create();
        $token = $teknisi->createToken('web-test')->plainTextToken;

        return [$teknisi, $token];
    }

    public function test_create_ticket_locks_item(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        $item = Item::factory()->available()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/maintenance/tickets', [
                'item_code' => $item->item_code,
                'issue_description' => 'Mesin tidak menyala.',
                'reported_by' => $teknisi->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('maintenance_tickets', [
            'item_id' => $item->id,
            'status' => 'open',
        ]);

        $this->assertSame(
            'under_maintenance',
            Item::find($item->id)->status->value
        );
    }

    public function test_create_ticket_requires_valid_item_code(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/maintenance/tickets', [
                'item_code' => 'TIDAK-ADA',
                'issue_description' => 'Test.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('item_code');
    }

    public function test_teknisi_can_start_ticket(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        $ticket = MaintenanceTicket::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/maintenance/tickets/{$ticket->id}/start");

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertSame(
            'in_progress',
            MaintenanceTicket::find($ticket->id)->status->value
        );
    }

    public function test_teknisi_can_add_repair_log(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        $ticket = MaintenanceTicket::factory()->inProgress()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/maintenance/tickets/{$ticket->id}/logs", [
                'handled_by' => $teknisi->id,
                'action_taken' => 'Mengganti power supply.',
                'service_type' => 'vendor_luar',
                'cost' => 150000,
                'repaired_at' => now()->toDateTimeString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('maintenance_logs', [
            'ticket_id' => $ticket->id,
            'service_type' => 'vendor_luar',
            'cost' => 150000,
        ]);
    }

    public function test_complete_ticket_restores_item(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        $item = Item::factory()->underMaintenance()->create();
        $ticket = MaintenanceTicket::factory()->inProgress()->create([
            'item_id' => $item->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/maintenance/tickets/{$ticket->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertSame(
            'tersedia',
            Item::find($item->id)->status->value
        );
    }

    public function test_complete_resolved_ticket_is_rejected(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        $item = Item::factory()->create(['status' => 'tersedia']);
        $ticket = MaintenanceTicket::factory()->resolved()->create([
            'item_id' => $item->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/maintenance/tickets/{$ticket->id}/complete");

        $response->assertStatus(422);
    }

    public function test_ticket_endpoints_require_auth(): void
    {
        $response = $this->getJson('/api/v1/maintenance/tickets');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/maintenance/tickets', []);
        $response->assertStatus(401);
    }

    public function test_ticket_index_returns_list(): void
    {
        [$teknisi, $token] = $this->actingAsTeknisi();
        MaintenanceTicket::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/maintenance/tickets');

        $response->assertOk()
            ->assertJsonCount(3, 'data.data');
    }

    public function test_student_cannot_access_maintenance_endpoints(): void
    {
        $student = User::factory()->siswa()->create();
        $token = $student->createToken('kiosk-test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/maintenance/tickets');

        $response->assertStatus(403);
    }
}
