<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumableMinStockAlertTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStaff(): User
    {
        return User::factory()->kabeng()->create();
    }

    public function test_item_model_has_min_stock_attributes_and_helpers(): void
    {
        $itemWithDefault = Item::factory()->create([
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 15,
            'min_stock' => null,
        ]);

        $this->assertEquals(20, $itemWithDefault->minStockThreshold());
        $this->assertTrue($itemWithDefault->isLowStock());

        $itemCustom = Item::factory()->create([
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 25,
            'min_stock' => 30,
        ]);

        $this->assertEquals(30, $itemCustom->minStockThreshold());
        $this->assertTrue($itemCustom->isLowStock());

        $itemSafe = Item::factory()->create([
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 50,
            'min_stock' => 20,
        ]);

        $this->assertFalse($itemSafe->isLowStock());

        $assetItem = Item::factory()->create([
            'type' => ItemType::ASET,
            'stock' => 1,
            'min_stock' => null,
        ]);

        $this->assertFalse($assetItem->isLowStock());
    }

    public function test_can_store_bhp_item_with_custom_min_stock(): void
    {
        $staff = $this->actingAsStaff();

        $response = $this->actingAs($staff)->post(route('items.store'), [
            'item_code' => 'BHP-TEST-001',
            'name' => 'Fast Connector SC UPC',
            'category' => 'KONEKTOR',
            'type' => 'bahan_habis_pakai',
            'stock' => 45,
            'min_stock' => 15,
            'location' => 'Ruang Alat',
            'status' => 'tersedia',
        ]);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', [
            'item_code' => 'BHP-TEST-001',
            'name' => 'Fast Connector SC UPC',
            'stock' => 45,
            'min_stock' => 15,
        ]);
    }

    public function test_can_update_bhp_item_min_stock_and_stock(): void
    {
        $staff = $this->actingAsStaff();

        $item = Item::factory()->create([
            'item_code' => 'BHP-UPDATE-001',
            'name' => 'RJ45 PACK 2',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 10,
            'min_stock' => 20,
            'location' => 'Ruang Alat',
        ]);

        $response = $this->actingAs($staff)->put(route('items.update', $item->id), [
            'item_code' => 'BHP-UPDATE-001',
            'name' => 'RJ45 PACK 2 (Updated)',
            'category' => 'KONEKTOR',
            'type' => 'bahan_habis_pakai',
            'stock' => 12,
            'min_stock' => 25,
            'status' => 'tersedia',
            'location' => 'Lab 1',
        ]);

        $response->assertRedirect(route('items.index'));

        $item->refresh();
        $this->assertEquals(12, $item->stock);
        $this->assertEquals(25, $item->min_stock);
        $this->assertEquals('RJ45 PACK 2 (Updated)', $item->name);
    }

    public function test_dashboard_displays_tidak_ada_yang_terlambat_empty_state_when_no_overdue_loans(): void
    {
        $staff = $this->actingAsStaff();

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tidak Ada yang Terlambat');
        $response->assertDontSee('Peminjaman Tertib');
    }

    public function test_dashboard_radar_displays_low_stock_bhp_when_stock_is_below_or_equal_to_min_stock(): void
    {
        $staff = $this->actingAsStaff();

        // RJ45 PACK 2: stock 10, min_stock 20 -> should trigger alert
        $lowStockItem = Item::factory()->create([
            'item_code' => 'RJ45-LOW-002',
            'name' => 'RJ45 PACK 2',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 10,
            'min_stock' => 20,
            'location' => 'Ruang Alat',
            'status' => ItemStatus::TERSEDIA,
        ]);

        // Safe item: stock 90, min_stock 20 -> should not appear in low stock list
        $safeItem = Item::factory()->create([
            'item_code' => 'RJ45-SAFE-001',
            'name' => 'RJ45 PACK 1',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 90,
            'min_stock' => 20,
            'location' => 'Ruang Alat',
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('RJ45 PACK 2');
        $response->assertSee('Batas alert: 20 pcs');
        $response->assertSee('Peringatan: Sisa 10 pcs');
        $response->assertDontSee('Stok Bahan Praktikum Aman');
    }

    public function test_dashboard_radar_displays_aman_empty_state_when_all_bhp_are_above_min_stock(): void
    {
        $staff = $this->actingAsStaff();

        Item::factory()->create([
            'item_code' => 'RJ45-SAFE-001',
            'name' => 'RJ45 PACK 1',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 90,
            'min_stock' => 20,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Stok Bahan Praktikum Aman');
        $response->assertSee('Semua bahan habis pakai berada di atas batas minimum stok.');
    }
}
