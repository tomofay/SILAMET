<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_homepage_renders_successfully(): void
    {
        $response = $this->get('/kiosk');

        $response->assertStatus(200);
        $response->assertSee('KIOSK MANDIRI');
        $response->assertSee('Silakan Tempel / Scan Barcode Kartu Pelajar');
    }

    public function test_kiosk_root_redirects_or_shows_kiosk(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('KIOSK MANDIRI');
    }

    public function test_kiosk_login_endpoint_returns_phone_field(): void
    {
        $user = User::factory()->create([
            'nis_nip' => '1234567890',
            'phone' => '6281234567890',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'nis_nip' => '1234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'nis_nip' => '1234567890',
                        'phone' => '6281234567890',
                    ],
                ],
            ]);
    }

    public function test_kiosk_item_lookup_by_item_code(): void
    {
        $item = Item::factory()->create([
            'item_code' => 'AST-RTR-999',
            'name' => 'Mikrotik Test RB951',
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->getJson('/api/v1/items/AST-RTR-999');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_code' => 'AST-RTR-999',
                    'name' => 'Mikrotik Test RB951',
                ],
            ]);
    }

    public function test_kiosk_return_flow_in_good_condition_restores_item(): void
    {
        $user = User::factory()->create(['nis_nip' => '11223344', 'phone' => '62899999999']);
        $item = Item::factory()->create([
            'item_code' => 'AST-SW-001',
            'status' => ItemStatus::DIPINJAM,
        ]);

        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
        ]);

        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'item_code' => 'AST-SW-001',
            'condition_in' => 'bagus',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => LoanStatus::SELESAI->value,
            'condition_in' => 'bagus',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => ItemStatus::TERSEDIA->value,
        ]);
    }

    public function test_kiosk_return_flow_in_damaged_condition_creates_maintenance_ticket(): void
    {
        $user = User::factory()->create(['nis_nip' => '99887766', 'phone' => '62899999999']);
        $item = Item::factory()->create([
            'item_code' => 'AST-SW-002',
            'status' => ItemStatus::DIPINJAM,
        ]);

        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
        ]);

        $response = $this->postJson('/api/v1/loans/return-kiosk', [
            'item_code' => 'AST-SW-002',
            'condition_in' => 'rusak',
            'issue_description' => 'Port Ethernet nomor 3 terbakar saat praktikum.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => LoanStatus::SELESAI->value,
            'condition_in' => 'rusak',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'status' => ItemStatus::UNDER_MAINTENANCE->value,
        ]);

        $this->assertDatabaseHas('maintenance_tickets', [
            'item_id' => $item->id,
            'reported_by' => $user->id,
            'issue_description' => 'Port Ethernet nomor 3 terbakar saat praktikum.',
            'status' => 'open',
        ]);
    }
}
