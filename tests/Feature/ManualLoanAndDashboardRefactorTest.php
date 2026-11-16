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

    public function test_manual_borrow_bhp_item_without_duration_sets_null_duration_and_due_date(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $bhpItem = Item::factory()->create([
            'name' => 'Fast Connector SC UPC',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 50,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->actingAs($staff)->post(route('loans.store'), [
            'user_id' => $student->id,
            'item_ids' => [$bhpItem->id],
            'quantities' => [$bhpItem->id => 2],
            'usage_duration' => null,
        ]);

        $response->assertRedirect(route('loans.index'));

        $loan = Loan::where('item_id', $bhpItem->id)->first();
        $this->assertNotNull($loan);
        $this->assertNull($loan->usage_duration);
        $this->assertNull($loan->due_at);
        $this->assertFalse($loan->isOverdue());
    }

    public function test_manual_borrow_aset_item_without_duration_fails_validation(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $asetItem = Item::factory()->create([
            'name' => 'MikroTik RouterBoard',
            'type' => ItemType::ASET,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->actingAs($staff)->post(route('loans.store'), [
            'user_id' => $student->id,
            'item_ids' => [$asetItem->id],
            'usage_duration' => null,
        ]);

        $response->assertSessionHasErrors('usage_duration');
    }

    public function test_kiosk_api_borrow_bhp_without_duration_sets_null_duration_and_due_date(): void
    {
        $student = User::factory()->siswa()->create([
            'phone' => '081234567890',
            'nis_nip' => '12345678',
        ]);
        $token = $student->createToken('kiosk-test')->plainTextToken;

        $bhpItem = Item::factory()->create([
            'name' => 'Konektor RJ45 Cat6',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 100,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_ids' => [$bhpItem->id],
                'quantities' => [$bhpItem->id => 5],
                'usage_duration' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $loan = Loan::where('item_id', $bhpItem->id)->first();
        $this->assertNotNull($loan);
        $this->assertNull($loan->usage_duration);
        $this->assertNull($loan->due_at);
        $this->assertFalse($loan->isOverdue());

        // Pastikan barang BHP tidak muncul pada daftar alat yang harus dikembalikan di Kiosk
        $activeResponse = $this->getJson('/api/v1/loans/active-by-nis?nis=12345678');
        $activeResponse->assertOk()
            ->assertJsonCount(0, 'data.active_loans');
    }

    public function test_kiosk_api_borrow_aset_without_duration_fails_validation(): void
    {
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);
        $token = $student->createToken('kiosk-test')->plainTextToken;

        $asetItem = Item::factory()->create([
            'name' => 'Cisco Catalyst Switch',
            'type' => ItemType::ASET,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/loans', [
                'user_id' => $student->id,
                'item_ids' => [$asetItem->id],
                'usage_duration' => null,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Pilih durasi peminjaman untuk barang praktikum yang dipinjam.');
    }

    public function test_bhp_item_does_not_display_maintenance_status_in_items_index_and_show(): void
    {
        $staff = $this->actingAsStaff();

        $bhpItem = Item::factory()->create([
            'name' => 'Konektor RJ45 Pack 1',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'category' => 'KONEKTOR',
            'status' => ItemStatus::TERSEDIA,
        ]);

        $asetItem = Item::factory()->create([
            'name' => 'TP-Link Router 1',
            'type' => ItemType::ASET,
            'category' => 'ROUTER',
            'status' => ItemStatus::TERSEDIA,
        ]);

        // 1. Pada items index kelompok BHP, tidak boleh ada teks 'Belum Dijadwalkan'
        $bhpIndexResponse = $this->actingAs($staff)
            ->get(route('items.index', ['group' => 'konektor']));
        $bhpIndexResponse->assertOk()
            ->assertSee('Konektor RJ45 Pack 1')
            ->assertDontSee('Belum Dijadwalkan');

        // 2. Pada items index kelompok Aset, harus tetap ada status maintenance
        $asetIndexResponse = $this->actingAs($staff)
            ->get(route('items.index', ['group' => 'router']));
        $asetIndexResponse->assertOk()
            ->assertSee('TP-Link Router 1')
            ->assertSee('Belum Dijadwalkan');

        // 3. Pada items show BHP, card Jadwal Pemeliharaan Preventif dan Riwayat Maintenance tidak ditampilkan
        $bhpShowResponse = $this->actingAs($staff)
            ->get(route('items.show', $bhpItem->id));
        $bhpShowResponse->assertOk()
            ->assertDontSee('Jadwal Pemeliharaan Preventif')
            ->assertDontSee('Riwayat Maintenance');

        // 4. Pada items show Aset, card Jadwal Pemeliharaan Preventif dan Riwayat Maintenance tetap ditampilkan
        $asetShowResponse = $this->actingAs($staff)
            ->get(route('items.show', $asetItem->id));
        $asetShowResponse->assertOk()
            ->assertSee('Jadwal Pemeliharaan Preventif')
            ->assertSee('Riwayat Maintenance');
    }

    public function test_expired_bhp_loan_is_automatically_archived_to_history(): void
    {
        $staff = $this->actingAsStaff();
        $student = User::factory()->siswa()->create(['phone' => '081234567890']);

        $bhpItem = Item::factory()->create([
            'name' => 'RJ45 Pack 1',
            'type' => ItemType::BAHAN_HABIS_PAKAI,
            'stock' => 50,
            'status' => ItemStatus::TERSEDIA,
        ]);

        // 1. Buat pinjaman BHP yang masih baru (< 24 jam)
        $freshLoan = Loan::create([
            'user_id' => $student->id,
            'item_id' => $bhpItem->id,
            'borrowed_at' => now()->subHours(2),
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'manual',
            'quantity_borrowed' => 5,
        ]);

        // 2. Buat pinjaman BHP yang sudah kadaluarsa (> 24 jam)
        $expiredLoan = Loan::create([
            'user_id' => $student->id,
            'item_id' => $bhpItem->id,
            'borrowed_at' => now()->subHours(25),
            'status' => LoanStatus::AKTIF,
            'borrowed_via' => 'manual',
            'quantity_borrowed' => 10,
        ]);

        // Verifikasi awal
        $this->assertEquals(LoanStatus::AKTIF, $expiredLoan->status);

        // Kunjungi halaman index loans -> memicu Loan::autoArchiveExpiredBhp()
        $indexResponse = $this->actingAs($staff)->get(route('loans.index'));
        $indexResponse->assertOk()
            ->assertSee('RJ45 Pack 1')
            ->assertSee('Sisa')
            ->assertSee('Auto-arsip jam 24:00 (ganti tanggal)');

        $freshLoan->refresh();
        $expiredLoan->refresh();

        // Fresh loan tetap aktif
        $this->assertEquals(LoanStatus::AKTIF, $freshLoan->status);
        // Expired loan otomatis berubah jadi selesai
        $this->assertEquals(LoanStatus::SELESAI, $expiredLoan->status);
        $this->assertNotNull($expiredLoan->returned_at);
        $this->assertEquals('bagus', $expiredLoan->condition_in);

        // Kunjungi halaman histori loans -> expired loan tampil dengan penanda otomatis dan Habis Terpakai
        $historyResponse = $this->actingAs($staff)->get(route('loans.history'));
        $historyResponse->assertOk()
            ->assertSee('Selesai Otomatis (Ganti Tanggal)')
            ->assertSee('Habis Terpakai');
    }
}
