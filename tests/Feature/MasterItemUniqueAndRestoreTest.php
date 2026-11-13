<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemUniqueAndRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_item_code_of_soft_deleted_item_restores_and_updates_cleanly_without_500_duplicate_key_error(): void
    {
        $admin = User::factory()->teknisi()->create();

        // Buat item lama yang kemudian dihapus (soft-delete)
        $oldItem = Item::factory()->create([
            'item_code' => 'TL840_17',
            'name' => 'TPLINK Router 17 Lama',
            'category' => 'TPLINK N ROUTER',
            'epc_tag' => '20261014',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);
        $oldItem->delete();

        $this->assertSoftDeleted('items', ['id' => $oldItem->id, 'item_code' => 'TL840_17']);

        // User mencoba mendaftarkan kembali TL840_17 dengan data baru (seperti pada screenshot error 1062)
        $response = $this->actingAs($admin)->post(route('items.store'), [
            'item_code' => 'TL840_17',
            'name' => 'TPLINK N ROUTER 1 Baru',
            'category' => 'TPLINK N ROUTER',
            'epc_tag' => '00268211',
            'type' => 'aset',
            'stock' => 1,
            'status' => 'tersedia',
            'location' => 'Ruang Alat',
        ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('success');

        // Pastikan tidak ada duplikasi ID baru dan item lama telah di-restore dengan data baru
        $this->assertDatabaseCount('items', 1);
        $this->assertNotSoftDeleted('items', ['id' => $oldItem->id]);

        $restored = Item::findOrFail($oldItem->id);
        $this->assertEquals('TL840_17', $restored->item_code);
        $this->assertEquals('TPLINK N ROUTER 1 Baru', $restored->name);
        $this->assertEquals('00268211', $restored->epc_tag);
        $this->assertEquals(ItemStatus::TERSEDIA, $restored->status);
    }

    public function test_submitting_item_code_of_active_item_fails_validation_cleanly(): void
    {
        $admin = User::factory()->teknisi()->create();

        Item::factory()->create([
            'item_code' => 'TL840_ACTIVE',
            'name' => 'Active Router',
        ]);

        $response = $this->actingAs($admin)->post(route('items.store'), [
            'item_code' => 'TL840_ACTIVE',
            'name' => 'Another Router',
            'type' => 'aset',
            'stock' => 1,
            'status' => 'tersedia',
            'location' => 'Ruang Alat',
        ]);

        $response->assertSessionHasErrors('item_code');
    }

    public function test_reusing_epc_tag_from_trashed_item_does_not_crash_mysql(): void
    {
        $admin = User::factory()->teknisi()->create();

        // Item trashed punya EPC tag 'EPC-OLD-123'
        $trashed = Item::factory()->create([
            'item_code' => 'OLD-ITEM-01',
            'epc_tag' => 'EPC-OLD-123',
        ]);
        $trashed->delete();

        // Item baru menggunakan EPC tag tersebut
        $response = $this->actingAs($admin)->post(route('items.store'), [
            'item_code' => 'NEW-ITEM-02',
            'name' => 'Brand New Switch',
            'epc_tag' => 'EPC-OLD-123',
            'type' => 'aset',
            'stock' => 1,
            'status' => 'tersedia',
            'location' => 'Rak B',
        ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('success');

        $newItem = Item::where('item_code', 'NEW-ITEM-02')->first();
        $this->assertNotNull($newItem);
        $this->assertEquals('EPC-OLD-123', $newItem->epc_tag);

        // Trashed item EPC tag telah dilepas agar tidak bentrok di indeks MySQL
        $this->assertNull($trashed->fresh()->epc_tag);
    }

    public function test_destroy_item_without_history_force_deletes_cleanly(): void
    {
        $admin = User::factory()->teknisi()->create();

        $item = Item::factory()->create([
            'item_code' => 'TEST-UNUSED',
        ]);

        $response = $this->actingAs($admin)->delete(route('items.destroy', $item->id));

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_destroy_item_with_loan_history_soft_deletes_safely(): void
    {
        $admin = User::factory()->teknisi()->create();
        $student = User::factory()->siswa()->create();

        $item = Item::factory()->create([
            'item_code' => 'TEST-WITH-LOAN',
        ]);

        Loan::create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::SELESAI,
            'usage_duration' => LoanDuration::SATU_HARI,
            'borrowed_at' => now()->subDays(2),
            'returned_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->delete(route('items.destroy', $item->id));

        $response->assertRedirect(route('items.index'));
        $this->assertSoftDeleted('items', ['id' => $item->id]);
        $this->assertDatabaseHas('loans', ['item_id' => $item->id]);
    }
}
