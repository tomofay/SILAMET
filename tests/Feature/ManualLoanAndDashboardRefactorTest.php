<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\Loan;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\GateValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualLoanAndDashboardRefactorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): User
    {
        return User::factory()->kabeng()->create();
    }

    public function test_bhp_item_stock_decrements_and_creates_stock_movement_on_manual_loan(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $item = Item::factory()->create([
            'name' => 'RJ45 (pack 1)',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 100,
            'status' => ItemStatus::TERSEDIA,
            'category' => 'KONEKTOR',
            'epc_tag' => 'EPC-RJ45-001',
        ]);

        $response = $this->actingAs($staff)->post(route('loans.store'), [
            'user_id' => $student->id,
            'item_ids' => [$item->id],
            'quantities' => [$item->id => 5],
            'usage_duration' => LoanDuration::SATU_HARI->value,
        ]);

        $response->assertRedirect(route('loans.index'));

        $item->refresh();
        $this->assertEquals(95, $item->stock);
        $this->assertEquals(ItemStatus::TERSEDIA, $item->status);

        $loan = Loan::where('item_id', $item->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(5, $loan->quantity_borrowed);
        $this->assertEquals('manual', $loan->borrowed_via);
        $this->assertTrue($loan->isManual());
        $this->assertFalse($loan->isKiosk());

        $movement = StockMovement::where('item_id', $item->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(StockMovementType::OUT, $movement->type);
        $this->assertEquals(5, $movement->quantity);
        $this->assertEquals(100, $movement->stock_before);
        $this->assertEquals(95, $movement->stock_after);
    }

    public function test_bhp_item_stock_restored_when_active_loan_deleted(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $item = Item::factory()->create([
            'name' => 'RJ45 (pack 1)',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 95,
            'status' => ItemStatus::TERSEDIA,
            'category' => 'KONEKTOR',
        ]);

        $loan = Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'quantity_borrowed' => 5,
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'manual',
        ]);

        $response = $this->actingAs($staff)->delete(route('loans.destroy', $loan->id));
        $response->assertRedirect();

        $item->refresh();
        $this->assertEquals(100, $item->stock);

        $restoreMovement = StockMovement::where('item_id', $item->id)
            ->where('type', StockMovementType::IN)
            ->latest('id')
            ->first();
        $this->assertNotNull($restoreMovement);
        $this->assertEquals(5, $restoreMovement->quantity);
    }

    public function test_gate_allows_item_with_active_manual_loan(): void
    {
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $item = Item::factory()->create([
            'name' => 'RJ45 (pack 1)',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 95,
            'status' => ItemStatus::TERSEDIA,
            'epc_tag' => 'E280689400004001',
        ]);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'manual',
        ]);

        $gateService = app(GateValidationService::class);
        $result = $gateService->checkTag('E280689400004001');

        $this->assertEquals('allowed', $result['status']);
        $this->assertEquals($item->id, $result['item_id']);
    }

    public function test_loans_index_renders_mandiri_and_manual_badges(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create();
        $item1 = Item::factory()->create(['name' => 'Router A']);
        $item2 = Item::factory()->create(['name' => 'Router B']);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item1->id,
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'kiosk',
        ]);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item2->id,
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'manual',
        ]);

        $response = $this->actingAs($staff)->get(route('loans.index'));
        $response->assertOk();
        $response->assertSee('Mandiri (Kiosk)');
        $response->assertSee('Manual (Admin)');
    }

    public function test_items_edit_view_removes_tanpa_kelompok_option(): void
    {
        $staff = $this->actingAsStaff();
        $item = Item::factory()->create(['category' => 'ROUTER']);

        $response = $this->actingAs($staff)->get(route('items.edit', $item->id));
        $response->assertOk();
        $response->assertDontSee('— TANPA KELOMPOK —');
    }

    public function test_loans_create_view_has_grouped_categories_and_search(): void
    {
        $staff = $this->actingAsStaff();
        Item::factory()->create(['name' => 'RJ45 pack 1', 'category' => 'KONEKTOR', 'status' => ItemStatus::TERSEDIA]);
        Item::factory()->create(['name' => 'TPLINK Router 1', 'category' => 'ROUTER', 'status' => ItemStatus::TERSEDIA]);

        $response = $this->actingAs($staff)->get(route('loans.create'));
        $response->assertOk();
        $response->assertSee('KONEKTOR');
        $response->assertSee('ROUTER');
        $response->assertSee('Pilih Alat Praktikum *');
    }

    public function test_dashboard_renders_3_boxes_and_no_black_bar_or_pintasan_cepat(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create();
        $item = Item::factory()->create();

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('Pusat Kendali Operasional Laboratorium');
        $response->assertDontSee('Pintasan Cepat');
        $response->assertSee('1. Sedang Dipinjam');
        $response->assertSee('2. Sudah Dikembalikan');
        $response->assertSee('3. Terlambat');
    }
}
