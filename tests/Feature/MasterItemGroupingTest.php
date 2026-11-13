<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\PreventiveSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_index_displays_grouped_folders_and_drills_down_into_full_unit_list(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        $tp1 = Item::factory()->available()->create([
            'item_code' => 'TL844_14',
            'epc_tag' => 'E200TP0014',
            'name' => 'TPLINK Router 14',
            'category' => 'TPLINK',
            'type' => ItemType::ASET,
            'location' => 'Rak Network 1',
        ]);

        $tp2 = Item::factory()->underMaintenance()->create([
            'item_code' => 'TL840_17',
            'epc_tag' => 'E200TP0017',
            'name' => 'TPLINK Router 17',
            'category' => 'tplink',
            'type' => ItemType::ASET,
            'location' => 'Rak Network 1',
        ]);

        $tp3 = Item::factory()->available()->create([
            'item_code' => 'TL_SW_08',
            'epc_tag' => 'E200TPSW08',
            'name' => 'Switch Desktop TL-SG108',
            'category' => 'TPLINK',
            'type' => ItemType::ASET,
            'location' => 'Rak Network 2',
        ]);

        $mikrotik = Item::factory()->available()->create([
            'item_code' => 'MK_RB951_01',
            'name' => 'MikroTik RB951Ui',
            'category' => 'MIKROTIK',
            'type' => ItemType::ASET,
            'location' => 'Rak Core',
        ]);

        $pc1 = Item::factory()->available()->create([
            'item_code' => 'PC_LAB_01',
            'name' => 'PC Praktikum 01',
            'category' => 'PC',
            'type' => ItemType::ASET,
            'location' => 'Meja 01',
        ]);

        $pc2 = Item::factory()->borrowed()->create([
            'item_code' => 'PC_LAB_02',
            'name' => 'PC Praktikum 02',
            'category' => 'PC',
            'type' => ItemType::ASET,
            'location' => 'Meja 02',
        ]);

        // Default /items harus menampilkan daftar kelompok ringkas (TPLINK, MIKROTIK, PC) tanpa tombol stok/tambah/maintenance
        $indexResponse = $this->actingAs($teknisi)->get(route('items.index'));
        $indexResponse->assertOk()
            ->assertViewIs('items.index')
            ->assertViewHas('viewMode', 'groups')
            ->assertSee('TPLINK')
            ->assertSee('3 item')
            ->assertSee('MIKROTIK')
            ->assertSee('PC')
            ->assertSee('2 item')
            ->assertSee('Cetak Semua Barcode')
            ->assertSee(route('items.index', ['group' => 'tplink']))
            ->assertDontSee('Buku Stok (BHP)')
            ->assertDontSee('Tambah Barang')
            ->assertDontSee(route('maintenance.schedules.index', ['group' => 'name:tplink']))
            ->assertDontSee('Tambah Unit ke')
            // Pastikan tabel detail unit belum ditumpuk di halaman utama kelompok
            ->assertDontSee('E200TP0014')
            ->assertDontSee('E200TPSW08');

        // Klik kelompok TPLINK -> masuk ke halaman isi penuh semua unit TPLINK (hanya tombol cetak & tambah barang di atas)
        $groupResponse = $this->actingAs($teknisi)->get(route('items.index', ['group' => 'tplink']));
        $groupResponse->assertOk()
            ->assertViewHas('viewMode', 'group_detail')
            ->assertSee('Kelompok: TPLINK')
            ->assertSee('3 barang (3 unit)')
            ->assertSee('TL844_14')
            ->assertSee('E200TP0014')
            ->assertSee('TL840_17')
            ->assertSee('E200TP0017')
            ->assertSee('TL_SW_08')
            ->assertSee('E200TPSW08')
            ->assertSee('Tambah Barang')
            ->assertSee('Cetak Barcode TPLINK')
            ->assertDontSee('Buku Stok (BHP)')
            ->assertDontSee('Pindah Kelompok:')
            ->assertDontSee('Maintenance Semua TPLINK')
            ->assertDontSee('Tambah Unit TPLINK')
            ->assertDontSee('PC_LAB_01')
            ->assertDontSee('MK_RB951_01');

        // Cari unit spesifik di dalam kelompok TPLINK
        $groupSearchResponse = $this->actingAs($teknisi)->get(route('items.index', ['group' => 'tplink', 'q' => 'SG108']));
        $groupSearchResponse->assertOk()
            ->assertViewHas('viewMode', 'group_detail')
            ->assertSee('TL_SW_08')
            ->assertDontSee('TL844_14');

        // Mode Semua Unit & Pencarian Global
        $allResponse = $this->actingAs($teknisi)->get(route('items.index', ['view' => 'all']));
        $allResponse->assertOk()
            ->assertViewHas('viewMode', 'all')
            ->assertSee('TL844_14')
            ->assertSee('PC_LAB_01')
            ->assertSee('MK_RB951_01');

        $globalSearchResponse = $this->actingAs($teknisi)->get(route('items.index', ['q' => 'RB951']));
        $globalSearchResponse->assertOk()
            ->assertViewHas('viewMode', 'all')
            ->assertSee('MK_RB951_01')
            ->assertDontSee('PC_LAB_01');

        $this->assertTrue($tp1->isNot($tp2) && $tp3->isNot($mikrotik) && $pc1->isNot($pc2));
    }

    public function test_master_group_maintenance_link_preselects_entire_category_on_maintenance_page_one(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        $tp1 = Item::factory()->available()->create([
            'item_code' => 'TL840_01',
            'name' => 'TPLINK TL-WR840N #1',
            'category' => 'TPLINK',
            'type' => ItemType::ASET,
        ]);

        $tp2 = Item::factory()->available()->create([
            'item_code' => 'TL_SW_08',
            'name' => 'Switch Desktop 8-Port',
            'category' => 'TPLINK',
            'type' => ItemType::ASET,
        ]);

        $pc = Item::factory()->available()->create([
            'item_code' => 'PC_01',
            'name' => 'Komputer Siswa 01',
            'category' => 'PC',
            'type' => ItemType::ASET,
        ]);

        $response = $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index', ['group' => 'name:tplink']));

        $response->assertOk()
            ->assertViewHas('preselectedGroupKey', 'name:tplink')
            ->assertSee('Jadwalkan Maintenance Paket Barang')
            ->assertSee('TPLINK — 2 barang (2 unit)')
            ->assertSee("initialGroup: 'name:tplink'", false)
            ->assertSee($tp1->item_code)
            ->assertSee($tp2->item_code)
            ->assertDontSee('ATAU CARI MEREK');

        // Submit langsung berdasarkan paket (selected_group), tanggal, siklus bulanan, & catatan checklist
        $checklist = "1. Bersihkan debu body & port\n2. Cek adaptor daya 9V\n3. Uji koneksi port LAN 1-4";

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'selected_group' => 'name:tplink',
                'maintenance_date' => '2026-11-11',
                'interval_months' => 6,
                'notes' => $checklist,
                'go_to_progress' => 1,
            ])
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $this->assertSame(2, \App\Models\PreventiveSchedule::where('workflow_stage', 'in_maintenance')->count());
        $this->assertSame(0, \App\Models\PreventiveSchedule::where('item_id', $pc->id)->count());

        $s1 = \App\Models\PreventiveSchedule::where('item_id', $tp1->id)->firstOrFail();
        $s2 = \App\Models\PreventiveSchedule::where('item_id', $tp2->id)->firstOrFail();
        $this->assertSame('Maintenance — TPLINK', $s1->title);
        $this->assertSame(6, $s1->interval_months);
        $this->assertSame('2027-05-11', $s1->next_due_date->format('Y-m-d'));
        $this->assertSame($checklist, $s1->notes);
        $this->assertSame($checklist, $s2->notes);

        // Tahap 2 (Sedang Di-Maintenance) menampilkan catatan checklist paket untuk teknisi
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.in-progress'))
            ->assertOk()
            ->assertSee('Maintenance — TPLINK')
            ->assertSee('1. Bersihkan debu body &amp; port', false);

        // Pastikan kelompok lain terpisah dengan rapi
        $groups = $response->viewData('itemGroups');
        $tplinkGroup = collect($groups)->firstWhere('key', 'name:tplink');
        $this->assertNotNull($tplinkGroup);
        $this->assertEqualsCanonicalizing([$tp1->id, $tp2->id], $tplinkGroup['item_ids']);
        $this->assertNotContains($pc->id, $tplinkGroup['item_ids']);
    }

    public function test_staff_can_create_edit_and_bulk_assign_item_categories(): void
    {
        $kabeng = User::factory()->kabeng()->create();

        // 1. Tambah item dengan category + return_group
        $this->actingAs($kabeng)
            ->post(route('items.store'), [
                'item_code' => 'MIK_HEX_01',
                'epc_tag' => 'E200MIK01',
                'name' => 'MikroTik hEX RB750Gr3 #1',
                'category' => '  MIKROTIK  ',
                'type' => 'aset',
                'status' => ItemStatus::TERSEDIA->value,
                'location' => 'Rak Core',
                'return_group' => 'mikrotik',
            ])
            ->assertRedirect(route('items.index', ['group' => 'mikrotik']));

        $item1 = Item::where('item_code', 'MIK_HEX_01')->firstOrFail();
        $this->assertSame('MIKROTIK', $item1->category);

        // Show item juga menampilkan badge kelompok & tombol kembali ke kelompok
        $this->actingAs($kabeng)
            ->get(route('items.show', $item1->id))
            ->assertOk()
            ->assertSee('Kelompok: MIKROTIK')
            ->assertSee('Kembali ke Kelompok MIKROTIK')
            ->assertSee(route('items.index', ['group' => 'mikrotik']));

        // 2. Edit item mengubah kelompok dari MIKROTIK ke PC + return_group
        $this->actingAs($kabeng)
            ->put(route('items.update', $item1->id), [
                'item_code' => 'MIK_HEX_01',
                'epc_tag' => 'E200MIK01',
                'name' => 'Komputer Server Mini 01',
                'category' => 'PC',
                'type' => 'aset',
                'status' => ItemStatus::TERSEDIA->value,
                'location' => 'Rak Server',
                'return_group' => 'mikrotik',
            ])
            ->assertRedirect(route('items.index', ['group' => 'pc']));

        $this->assertSame('PC', $item1->fresh()->category);

        // 3. Edit item tanpa return_group tetap redirect ke items.index standar
        $this->actingAs($kabeng)
            ->put(route('items.update', $item1->id), [
                'item_code' => 'MIK_HEX_01',
                'epc_tag' => 'E200MIK01',
                'name' => 'Komputer Server Mini 01',
                'category' => 'PC',
                'type' => 'aset',
                'status' => ItemStatus::TERSEDIA->value,
                'location' => 'Rak Server',
            ])
            ->assertRedirect(route('items.index'));

        // 4. Bulk assign category untuk beberapa barang sekaligus
        $unitA = Item::factory()->create(['item_code' => 'SW_01', 'name' => 'Switch 1', 'category' => null]);
        $unitB = Item::factory()->create(['item_code' => 'SW_02', 'name' => 'Switch 2', 'category' => null]);
        $untouched = Item::factory()->create(['item_code' => 'OTR_01', 'name' => 'Tang Krimping', 'category' => 'ALAT']);

        $this->actingAs($kabeng)
            ->post(route('items.bulk-category'), [
                'item_ids' => [$unitA->id, $unitB->id],
                'category' => '  TPLINK  ',
            ])
            ->assertRedirect(route('items.index', ['group' => 'tplink']))
            ->assertSessionHas('success');

        $this->assertSame('TPLINK', $unitA->fresh()->category);
        $this->assertSame('TPLINK', $unitB->fresh()->category);
        $this->assertSame('ALAT', $untouched->fresh()->category);
    }

    public function test_unauthorized_roles_are_blocked_from_master_items_and_bulk_category(): void
    {
        $student = User::factory()->siswa()->create();
        $item = Item::factory()->create(['item_code' => 'PC_LOCK_01']);

        $this->actingAs($student)
            ->get(route('items.index'))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('items.bulk-category'), [
                'item_ids' => [$item->id],
                'category' => 'PC',
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('items.groups.store'), [
                'name' => 'SWITCH',
            ])
            ->assertForbidden();

        $this->assertNull($item->fresh()->category);
        $this->assertSame(0, ItemGroup::count());
    }

    public function test_staff_can_create_empty_group_only_and_add_item_cleanly_inside_that_group(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        // Buat 1 item di grup lain agar teruji isolasi grup saat buka form tambah di dalam kelompok baru
        $otherItem = Item::factory()->available()->create([
            'item_code' => 'TL_99',
            'name' => 'TPLINK Router 99',
            'category' => 'TPLINK',
        ]);

        // Halaman utama /items hanya punya tombol Tambah Kelompok Barang & Cetak Semua Barcode
        $this->actingAs($teknisi)
            ->get(route('items.index'))
            ->assertOk()
            ->assertSee('Tambah Kelompok Barang')
            ->assertSee(route('items.groups.store'))
            ->assertDontSee('Tambah Barang');

        // Duplikat nama kelompok yang sudah ada dari item ("tplink") atau karakter non-alfanumerik ditolak
        $this->actingAs($teknisi)
            ->from(route('items.index'))
            ->post(route('items.groups.store'), ['name' => 'tplink'])
            ->assertSessionHasErrors('name');

        $this->actingAs($teknisi)
            ->from(route('items.index'))
            ->post(route('items.groups.store'), ['name' => '---'])
            ->assertSessionHasErrors('name');

        // Tambah kelompok barang baru "MIKROTIK" tanpa membuat unit atau jadwal maintenance
        $this->actingAs($teknisi)
            ->post(route('items.groups.store'), ['name' => '  MIKROTIK  '])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success');

        $group = ItemGroup::where('slug', 'mikrotik')->firstOrFail();
        $this->assertSame('MIKROTIK', $group->name);
        $this->assertSame($teknisi->id, $group->created_by);
        $this->assertSame(1, Item::count());
        $this->assertSame(0, PreventiveSchedule::count());

        // Kelompok kosong muncul di /items (0 item) namun TIDAK mengotori dropdown Maintenance Page 1
        $this->actingAs($teknisi)
            ->get(route('items.index'))
            ->assertOk()
            ->assertSee('MIKROTIK')
            ->assertSee('0 item')
            ->assertSee('Kelompok kosong');

        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index'))
            ->assertOk()
            ->assertSee('TPLINK — 1 barang (1 unit)')
            ->assertDontSee('MIKROTIK — 0 barang');

        // Buka kelompok MIKROTIK (masih kosong): tampil tombol Tambah Barang untuk MIKROTIK, tanpa tombol cetak kosong
        $this->actingAs($teknisi)
            ->get(route('items.index', ['group' => 'mikrotik']))
            ->assertOk()
            ->assertViewHas('viewMode', 'group_detail')
            ->assertSee('Kelompok: MIKROTIK')
            ->assertSee('0 barang (0 unit)')
            ->assertSee('Belum Ada Unit di Kelompok MIKROTIK')
            ->assertSee('Tambah Barang')
            ->assertSee(route('items.create', ['category' => 'MIKROTIK', 'return_group' => 'mikrotik']))
            ->assertDontSee('Cetak Barcode MIKROTIK');

        // Endpoint cetak barcode dengan ids kosong tidak membocorkan item kelompok lain
        $this->actingAs($teknisi)
            ->get(route('items.barcodes', ['ids' => '']))
            ->assertOk()
            ->assertDontSee($otherItem->item_code);

        // Form Tambah Barang di dalam kelompok MIKROTIK strictly terkunci ke MIKROTIK & bebas dari section Maintenance
        $this->actingAs($teknisi)
            ->get(route('items.create', ['category' => 'MIKROTIK', 'return_group' => 'mikrotik']))
            ->assertOk()
            ->assertSee('Formulir Tambah Barang — Kelompok MIKROTIK')
            ->assertSee('Kembali ke Kelompok MIKROTIK')
            ->assertSee('<input type="hidden" name="category" value="MIKROTIK">', false)
            ->assertDontSee('Jadwal Maintenance Barang')
            ->assertDontSee('preventive_interval_months')
            ->assertDontSee('preventive_last_serviced_at')
            ->assertDontSee('name="category" x-model="category"', false);

        // Submit unit baru di dalam kelompok MIKROTIK -> hanya membuat Item tanpa auto-create jadwal maintenance
        $this->actingAs($teknisi)
            ->post(route('items.store'), [
                'item_code' => 'MKT-RB750-01',
                'epc_tag' => 'E200MKT75001',
                'name' => 'MIKROTIK RB750Gr3 #1',
                'category' => 'MIKROTIK',
                'return_group' => 'mikrotik',
                'type' => 'aset',
                'stock' => 1,
                'status' => ItemStatus::TERSEDIA->value,
                'location' => 'Rak Core Network',
            ])
            ->assertRedirect(route('items.index', ['group' => 'mikrotik']));

        $this->assertSame(0, PreventiveSchedule::count());

        // Kelompok MIKROTIK sekarang menampilkan unitnya & Cetak Barcode MIKROTIK, dan muncul di Maintenance Page 1
        $this->actingAs($teknisi)
            ->get(route('items.index', ['group' => 'mikrotik']))
            ->assertOk()
            ->assertSee('1 barang (1 unit)')
            ->assertSee('MKT-RB750-01')
            ->assertSee('Cetak Barcode MIKROTIK')
            ->assertDontSee('TL_99');

        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index'))
            ->assertOk()
            ->assertSee('MIKROTIK — 1 barang (1 unit)');
    }
}
