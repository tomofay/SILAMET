<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_consumable_records_initial_stock_movement(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        $this->actingAs($teknisi)
            ->post(route('items.store'), [
                'item_code' => 'BHP-RJ45-01',
                'name' => 'Konektor RJ45 Cat6 Pack',
                'type' => 'bahan_habis_pakai',
                'stock' => 50,
                'status' => 'tersedia',
                'location' => 'LAB1-LEMARI-BHP',
            ])
            ->assertRedirect(route('items.index'));

        $item = Item::where('item_code', 'BHP-RJ45-01')->firstOrFail();
        $this->assertSame(50, $item->stock);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'user_id' => $teknisi->id,
            'type' => StockMovementType::IN->value,
            'quantity' => 50,
            'stock_before' => 0,
            'stock_after' => 50,
        ]);
    }

    public function test_staff_can_record_stock_in_and_out_atomically_and_view_ledger(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $student = User::factory()->siswa()->create();

        $consumable = Item::factory()->consumable()->create([
            'name' => 'Kabel UTP Cat6 Belden',
            'item_code' => 'BHP-UTP-01',
            'stock' => 20,
        ]);

        // Siswa dilarang mengakses buku stok
        $this->actingAs($student)
            ->get(route('stock-movements.index'))
            ->assertForbidden();

        // Catat keluar 8 unit untuk praktikum
        $this->actingAs($teknisi)
            ->from(route('stock-movements.index'))
            ->post(route('stock-movements.store'), [
                'item_id' => $consumable->id,
                'type' => 'out',
                'quantity' => 8,
                'recipient_name' => 'XI SIJA 1',
                'description' => 'Praktikum terminasi straight & cross',
            ])
            ->assertRedirect(route('stock-movements.index'));

        $this->assertSame(12, $consumable->fresh()->stock);

        // Catat masuk 15 unit pengadaan baru
        $this->actingAs($teknisi)
            ->from(route('stock-movements.index'))
            ->post(route('stock-movements.store'), [
                'item_id' => $consumable->id,
                'type' => 'in',
                'quantity' => 15,
                'recipient_name' => 'Vendor Jaringan Jaya',
                'description' => 'Restok awal semester genap',
            ])
            ->assertRedirect(route('stock-movements.index'));

        $this->assertSame(27, $consumable->fresh()->stock);

        $latest = StockMovement::latest('id')->first();
        $this->assertSame(12, $latest->stock_before);
        $this->assertSame(27, $latest->stock_after);

        // Cek tampilan di buku stok dan detail item
        $this->actingAs($teknisi)
            ->get(route('stock-movements.index'))
            ->assertOk()
            ->assertSee('Kabel UTP Cat6 Belden')
            ->assertSee('Praktikum terminasi straight &amp; cross', false);

        $this->actingAs($teknisi)
            ->get(route('items.show', $consumable->id))
            ->assertOk()
            ->assertSee('Buku Stok &amp; Mutasi Bahan', false)
            ->assertSee('XI SIJA 1');
    }

    public function test_stock_out_cannot_exceed_available_stock_and_rejects_fixed_assets(): void
    {
        $kabeng = User::factory()->kabeng()->create();

        $consumable = Item::factory()->consumable()->create(['stock' => 5]);
        $asset = Item::factory()->create(['type' => ItemType::ASET, 'stock' => 1]);

        $this->actingAs($kabeng)
            ->postJson(route('stock-movements.store'), [
                'item_id' => $consumable->id,
                'type' => 'out',
                'quantity' => 6,
                'description' => 'Permintaan melebihi sisa stok',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertSame(5, $consumable->fresh()->stock);

        $this->actingAs($kabeng)
            ->postJson(route('stock-movements.store'), [
                'item_id' => $asset->id,
                'type' => 'out',
                'quantity' => 1,
                'description' => 'Tidak valid untuk aset tetap',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['item_id']);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_editing_consumable_stock_logs_adjustment_movement(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $consumable = Item::factory()->consumable()->create([
            'item_code' => 'BHP-ADJ-01',
            'name' => 'Timah Solder Roll',
            'stock' => 10,
            'location' => 'LAB1-RAK04',
        ]);

        $this->actingAs($teknisi)
            ->put(route('items.update', $consumable->id), [
                'item_code' => 'BHP-ADJ-01',
                'name' => 'Timah Solder Roll',
                'type' => 'bahan_habis_pakai',
                'stock' => 4,
                'status' => 'tersedia',
                'location' => 'LAB1-RAK04',
            ])
            ->assertRedirect(route('items.index'));

        $this->assertSame(4, $consumable->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $consumable->id,
            'type' => 'out',
            'quantity' => 6,
            'stock_before' => 10,
            'stock_after' => 4,
        ]);
    }
}

