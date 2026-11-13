<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\TicketStatus;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventiveMaintenanceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknisi_can_manage_preventive_schedules_and_mark_serviced(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->create(['name' => 'MikroTik RB951Ui']);

        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index'))
            ->assertOk()
            ->assertSee('Penjadwalan Perawatan Preventif Berkala');

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'item_id' => $item->id,
                'title' => 'Kalibrasi & Pembersihan Fan',
                'interval_months' => 3,
                'last_serviced_at' => '2026-08-10',
                'status' => 'scheduled',
                'notes' => 'Periksa PSU dan port.',
            ])
            ->assertRedirect(route('maintenance.schedules.index'));

        $schedule = PreventiveSchedule::firstOrFail();
        $this->assertSame('2026-11-10', $schedule->next_due_date->format('Y-m-d'));

        // Tandai sudah diservis hari ini (10 Nov 2026) -> otomatis geser +3 bulan ke 10 Feb 2027
        $schedule->update(['last_notified_stage' => 'h0']);

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.mark-serviced', $schedule->id))
            ->assertRedirect(route('maintenance.schedules.index'));

        $schedule->refresh();
        $this->assertSame('2026-11-10', $schedule->last_serviced_at->format('Y-m-d'));
        $this->assertSame('2027-02-10', $schedule->next_due_date->format('Y-m-d'));
        $this->assertSame('safe', $schedule->workflow_stage);
        $this->assertNull($schedule->last_notified_stage);

        Carbon::setTestNow();
    }

    public function test_schedules_dropdown_and_coverage_match_active_master_aset_and_support_bulk_assign(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 09:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();

        $alreadyScheduled = Item::factory()->create([
            'item_code' => 'TL844_14',
            'name' => 'TPLINK Router 14',
            'type' => ItemType::ASET,
            'location' => 'Ruang Alat',
        ]);
        PreventiveSchedule::factory()->create([
            'item_id' => $alreadyScheduled->id,
            'title' => 'Perawatan Rutin Router 14',
            'interval_months' => 1,
            'last_serviced_at' => '2026-10-10',
            'next_due_date' => '2026-11-10',
            'status' => 'scheduled',
        ]);

        $unscheduledAset = Item::factory()->create([
            'item_code' => 'PC_10',
            'name' => 'PC Workstation Lab 10',
            'type' => ItemType::ASET,
            'location' => 'Lab SIJA 1',
        ]);

        $unscheduledBhp = Item::factory()->consumable()->create([
            'item_code' => 'BHP-RJ45',
            'name' => 'Konektor RJ45 Cat6',
            'location' => 'Ruang Alat',
        ]);

        $trashedItem = Item::factory()->create([
            'item_code' => 'DEL-01',
            'name' => 'Switch Lama Terhapus',
        ]);
        $trashedItem->delete();

        // /maintenance menampilkan indikator jumlah barang Master Aset yang belum terjadwal
        $this->actingAs($teknisi)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Jadwal Maintenance Barang')
            ->assertSee('2 belum terjadwal')
            ->assertSee('2 dari 3 barang di Master Aset')
            ->assertSee('belum memiliki jadwal maintenance berkala');

        // /maintenance/schedules menampilkan paket barang Master Aset aktif & tidak menampilkan item terhapus
        $response = $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index', ['item_id' => $unscheduledAset->id]));

        $response->assertOk()
            ->assertSee('Jadwalkan Maintenance Paket Barang')
            ->assertSee('TPLINK Router 14 — 1 barang (1 unit)')
            ->assertSee('PC Workstation Lab 10 — 1 barang (1 unit)')
            ->assertSee('Konektor RJ45 Cat6 — 1 barang (')
            ->assertDontSee('Switch Lama Terhapus');

        // Bulk assign khusus Aset Tetap: hanya menjadwalkan PC_10, skip TL844_14 (sudah ada) & RJ45 (BHP)
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.bulk-assign'), [
                'scope' => 'aset',
                'interval_months' => 3,
                'last_serviced_at' => '2026-11-11',
                'title_prefix' => 'Perawatan Berkala',
            ])
            ->assertRedirect(route('maintenance.schedules.index'));

        $this->assertSame(1, PreventiveSchedule::where('item_id', $alreadyScheduled->id)->count());
        $asetSchedule = PreventiveSchedule::where('item_id', $unscheduledAset->id)->firstOrFail();
        $this->assertSame('Perawatan Berkala — PC Workstation Lab 10', $asetSchedule->title);
        $this->assertSame(3, $asetSchedule->interval_months);
        $this->assertSame('2026-11-11', $asetSchedule->last_serviced_at->format('Y-m-d'));
        $this->assertSame('2027-02-11', $asetSchedule->next_due_date->format('Y-m-d'));
        $this->assertSame('scheduled', $asetSchedule->status);
        $this->assertSame(0, PreventiveSchedule::where('item_id', $unscheduledBhp->id)->count());

        // Bulk assign seluruh barang Master Aset: melengkapi sisanya (Konektor RJ45 Cat6) tanpa duplikasi
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.bulk-assign'), [
                'scope' => 'all',
                'interval_months' => 6,
                'last_serviced_at' => '2026-11-11',
            ])
            ->assertRedirect(route('maintenance.schedules.index'));

        $bhpSchedule = PreventiveSchedule::where('item_id', $unscheduledBhp->id)->firstOrFail();
        $this->assertSame('Maintenance Berkala — Konektor RJ45 Cat6', $bhpSchedule->title);
        $this->assertSame(6, $bhpSchedule->interval_months);
        $this->assertSame('2027-05-11', $bhpSchedule->next_due_date->format('Y-m-d'));
        $this->assertSame(3, PreventiveSchedule::count());

        Carbon::setTestNow();
    }

    public function test_four_stage_maintenance_workflow_splits_safe_and_damaged_items_into_repair_tickets(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 09:30:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();

        $router = Item::factory()->create([
            'item_code' => 'MKT-RB951',
            'name' => 'MikroTik RB951Ui',
            'type' => ItemType::ASET,
            'stock' => 5,
            'status' => ItemStatus::TERSEDIA,
            'location' => 'Rack Lab SIJA 1',
        ]);

        $cable = Item::factory()->consumable()->create([
            'item_code' => 'BHP-UTP',
            'name' => 'Kabel Tester RJ45',
            'stock' => 3,
            'status' => ItemStatus::TERSEDIA,
            'location' => 'Ruang Alat',
        ]);

        // 1. Page 1 submit (barang, tanggal, jumlah=5) -> langsung masuk Page 2: Sedang Di-Maintenance
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'item_id' => $router->id,
                'maintenance_date' => '2026-11-11',
                'quantity' => 5,
                'interval_months' => 3,
                'notes' => 'Cek seluruh port ether 1-5 & adaptor 24V.',
                'go_to_progress' => 1,
            ])
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $routerSchedule = PreventiveSchedule::where('item_id', $router->id)->firstOrFail();
        $this->assertSame('in_maintenance', $routerSchedule->workflow_stage);
        $this->assertSame('2026-11-11', $routerSchedule->maintenance_date->format('Y-m-d'));
        $this->assertSame(5, $routerSchedule->quantity);

        // Tambah item ke-2 untuk pengujian mode all_safe
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'item_id' => $cable->id,
                'maintenance_date' => '2026-11-11',
                'quantity' => 2,
                'interval_months' => 6,
                'go_to_progress' => 1,
            ])
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $cableSchedule = PreventiveSchedule::where('item_id', $cable->id)->firstOrFail();

        // 2. Page 2: Kedua barang tampil di Sedang Di-Maintenance tanpa ikon Edit Jadwal & bar header Master/Tiket
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.in-progress'))
            ->assertOk()
            ->assertSee('MikroTik RB951Ui')
            ->assertSee('5 unit')
            ->assertSee('Kabel Tester RJ45')
            ->assertSee('2 unit')
            ->assertDontSee('Daftar Tiket Kerusakan')
            ->assertDontSee('title="Edit Jadwal"', false);

        // 2a. Tandai seluruh unit Kabel Tester RJ45 Aman -> masuk Page 3: Barang Aman
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $cableSchedule->id), [
                'mode' => 'all_safe',
            ])
            ->assertRedirect(route('maintenance.schedules.safe'));

        $cableSchedule->refresh();
        $this->assertSame('safe', $cableSchedule->workflow_stage);
        $this->assertSame(2, $cableSchedule->safe_quantity);
        $this->assertSame(0, $cableSchedule->damaged_quantity);
        $this->assertSame('scheduled', $cableSchedule->status);
        $this->assertSame($teknisi->id, $cableSchedule->inspected_by);

        // 2b. Pilah MikroTik RB951Ui (5 unit -> 3 Aman, 2 Rusak) -> pecah ke Barang Aman & Perlu Diperbaiki
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $routerSchedule->id), [
                'mode' => 'split',
                'damaged_quantity' => 2,
                'damage_notes' => 'Port Ether 3 & 4 mati total tidak link.',
            ])
            ->assertRedirect(route('maintenance.schedules.repairs'));

        $routerSchedule->refresh();
        $this->assertSame('safe', $routerSchedule->workflow_stage);
        $this->assertSame(3, $routerSchedule->quantity);
        $this->assertSame(3, $routerSchedule->safe_quantity);
        $this->assertSame(0, $routerSchedule->damaged_quantity);
        $this->assertSame('scheduled', $routerSchedule->status);

        $damagedSplit = PreventiveSchedule::where('parent_schedule_id', $routerSchedule->id)->firstOrFail();
        $this->assertSame('needs_repair', $damagedSplit->workflow_stage);
        $this->assertSame(2, $damagedSplit->quantity);
        $this->assertSame(0, $damagedSplit->safe_quantity);
        $this->assertSame(2, $damagedSplit->damaged_quantity);
        $this->assertSame('Port Ether 3 & 4 mati total tidak link.', $damagedSplit->damage_notes);
        $this->assertSame('paused', $damagedSplit->status);

        // 3. Page 3: Barang Aman menampilkan unit aman tanpa ikon Edit Jadwal & bar header Master/Tiket
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.safe'))
            ->assertOk()
            ->assertSee('MikroTik RB951Ui')
            ->assertSee('3 unit')
            ->assertSee('Kabel Tester RJ45')
            ->assertSee('2 unit')
            ->assertDontSee('Daftar Tiket Kerusakan')
            ->assertDontSee('title="Edit Jadwal"', false);

        // Maintenance index menampilkan pill jumlah item di Perlu Diajukan Tiket
        $this->actingAs($teknisi)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Perlu Diajukan Tiket');

        // 4. Page 4: Perlu Diperbaiki menampilkan 2 unit MikroTik rusak + tombol Masukkan ke Tiket Barang Rusak
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.repairs'))
            ->assertOk()
            ->assertSee('MikroTik RB951Ui')
            ->assertSee('2 unit')
            ->assertSee('Port Ether 3 & 4 mati total tidak link.')
            ->assertSee('Masukkan ke Tiket Barang Rusak');

        // 4a. Ajukan ke Tiket Pengajuan Barang Rusak
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.submit-ticket', $damagedSplit->id), [
                'issue_description' => '[2 unit] Port Ether 3 & 4 mati total hasil maintenance rutin.',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'open']));

        $damagedSplit->refresh();
        $router->refresh();
        $ticket = MaintenanceTicket::firstOrFail();

        $this->assertSame('ticket_created', $damagedSplit->workflow_stage);
        $this->assertSame($ticket->id, $damagedSplit->maintenance_ticket_id);
        $this->assertSame($router->id, $ticket->item_id);
        $this->assertSame($teknisi->id, $ticket->reported_by);
        $this->assertSame(TicketStatus::OPEN, $ticket->status);
        $this->assertSame(ItemStatus::UNDER_MAINTENANCE, $router->status);

        // Pengajuan ulang jadwal yang sudah dibuatkan tiket tidak membuat tiket duplikat
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.submit-ticket', $damagedSplit->id), [
                'issue_description' => 'Coba duplikat',
            ])
            ->assertRedirect(route('maintenance.schedules.repairs'));
        $this->assertSame(1, MaintenanceTicket::count());

        // Edit page pada item aman menyesuaikan safe_quantity dan redirect kembali ke halaman Barang Aman
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.edit', $routerSchedule->id))
            ->assertOk()
            ->assertSee('Kembali ke Barang Aman');

        $this->actingAs($teknisi)
            ->put(route('maintenance.schedules.update', $routerSchedule->id), [
                'item_id' => $router->id,
                'title' => 'Maintenance Port Router Unit Aman',
                'maintenance_date' => '2026-11-11',
                'quantity' => 4,
                'interval_months' => 3,
                'last_serviced_at' => '2026-11-11',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('maintenance.schedules.safe'));

        $routerSchedule->refresh();
        $this->assertSame(4, $routerSchedule->quantity);
        $this->assertSame(4, $routerSchedule->safe_quantity);

        // Restart ("Maintenance Lagi") mengembalikan item aman ke Page 2: Sedang Di-Maintenance
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.restart', $cableSchedule->id))
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $cableSchedule->refresh();
        $this->assertSame('in_maintenance', $cableSchedule->workflow_stage);

        Carbon::setTestNow();
    }

    public function test_triage_all_damaged_validation_and_bulk_assign_to_progress(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();
        $switch = Item::factory()->create([
            'item_code' => 'SW-24P',
            'name' => 'Switch Cisco 24 Port',
            'type' => ItemType::ASET,
            'stock' => 2,
        ]);

        // Bulk assign dengan go_to_progress=1 langsung memasukkan ke Sedang Di-Maintenance
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.bulk-assign'), [
                'scope' => 'all',
                'interval_months' => 3,
                'maintenance_date' => '2026-11-11',
                'quantity_mode' => 'master_stock',
                'go_to_progress' => 1,
            ])
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $schedule = PreventiveSchedule::where('item_id', $switch->id)->firstOrFail();
        $this->assertSame('in_maintenance', $schedule->workflow_stage);
        $this->assertSame(2, $schedule->quantity);

        // Pilah rusak tanpa damage_notes harus ditolak validasi
        $this->actingAs($teknisi)
            ->from(route('maintenance.schedules.in-progress'))
            ->post(route('maintenance.schedules.triage', $schedule->id), [
                'mode' => 'split',
                'damaged_quantity' => 2,
                'damage_notes' => '',
            ])
            ->assertSessionHasErrors('damage_notes');

        // Pilah seluruh unit rusak (damaged_quantity === quantity) tidak membuat child row duplikat
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $schedule->id), [
                'mode' => 'split',
                'damaged_quantity' => 2,
                'damage_notes' => 'Power supply internal short circuit.',
            ])
            ->assertRedirect(route('maintenance.schedules.repairs'));

        $this->assertSame(1, PreventiveSchedule::count());
        $schedule->refresh();
        $this->assertSame('needs_repair', $schedule->workflow_stage);
        $this->assertSame(0, $schedule->safe_quantity);
        $this->assertSame(2, $schedule->damaged_quantity);
        $this->assertSame('paused', $schedule->status);

        Carbon::setTestNow();
    }

    public function test_creating_master_aset_item_can_automatically_create_maintenance_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();

        $this->actingAs($teknisi)
            ->post(route('items.store'), [
                'item_code' => 'SLM-MKT-99',
                'name' => 'MikroTik CCR2004',
                'type' => 'aset',
                'stock' => 1,
                'status' => 'tersedia',
                'location' => 'Rack Server 1',
                'preventive_interval_months' => 3,
                'preventive_last_serviced_at' => '2026-11-11',
            ])
            ->assertRedirect(route('items.index'));

        $item = Item::where('item_code', 'SLM-MKT-99')->firstOrFail();

        $createdSchedule = PreventiveSchedule::where('item_id', $item->id)->firstOrFail();
        $this->assertSame('Perawatan Berkala — MikroTik CCR2004', $createdSchedule->title);
        $this->assertSame(3, $createdSchedule->interval_months);
        $this->assertSame('2026-11-11', $createdSchedule->last_serviced_at->format('Y-m-d'));
        $this->assertSame('2027-02-11', $createdSchedule->next_due_date->format('Y-m-d'));
        $this->assertSame('scheduled', $createdSchedule->status);

        // Kaprog tetap dapat melihat Master Aset detail beserta jadwal tanpa link edit terlarang
        $kaprog = User::factory()->kaprog()->create();
        $this->actingAs($kaprog)
            ->get(route('items.show', $item->id))
            ->assertOk()
            ->assertSee('Perawatan Berkala — MikroTik CCR2004')
            ->assertDontSee('Atur Jadwal Maintenance');

        Carbon::setTestNow();
    }

    public function test_student_and_kaprog_cannot_access_or_bulk_assign_preventive_schedules(): void
    {
        $student = User::factory()->siswa()->create();
        $kaprog = User::factory()->kaprog()->create();
        $schedule = PreventiveSchedule::factory()->create(['workflow_stage' => 'in_maintenance']);

        $this->actingAs($student)->get(route('maintenance.schedules.index'))->assertForbidden();
        $this->actingAs($student)->get(route('maintenance.schedules.in-progress'))->assertForbidden();
        $this->actingAs($student)->get(route('maintenance.schedules.safe'))->assertForbidden();
        $this->actingAs($student)->get(route('maintenance.schedules.repairs'))->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.bulk-assign'), [
                'scope' => 'all',
                'interval_months' => 3,
                'last_serviced_at' => '2026-11-11',
            ])
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.triage', $schedule->id), ['mode' => 'all_safe'])
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.submit-ticket', $schedule->id), ['issue_description' => 'Rusak'])
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.restart', $schedule->id))
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.bulk-triage-safe'), ['schedule_ids' => [$schedule->id]])
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->post(route('maintenance.schedules.mark-broken', $schedule->id), ['damage_reason' => 'Rusak'])
            ->assertForbidden();
    }

    public function test_stage_2_triage_broken_writes_off_single_and_partial_split_items_from_master_aset(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 11:00:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();

        // Kasus A: 1 unit barang -> seluruhnya rusak permanen saat triage Tahap 2
        $routerSingle = Item::factory()->create([
            'item_code' => 'TPL-SGL-01',
            'name' => 'TPLINK WR840N',
            'type' => ItemType::ASET,
            'stock' => 1,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $schedSingle = PreventiveSchedule::factory()->create([
            'item_id' => $routerSingle->id,
            'workflow_stage' => 'in_maintenance',
            'quantity' => 1,
            'maintenance_date' => '2026-11-11',
        ]);

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $schedSingle->id), [
                'mode' => 'split',
                'damaged_quantity' => 1,
                'damage_disposition' => 'broken',
                'damage_notes' => 'Chipset CPU router mati total.',
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        $schedSingle->refresh();
        $this->assertSame('written_off', $schedSingle->workflow_stage);
        $this->assertSame('paused', $schedSingle->status);
        $this->assertTrue(Item::withTrashed()->findOrFail($routerSingle->id)->trashed());

        // Kasus B: 5 unit -> 3 Aman masuk Stage 3 & 2 Rusak Permanen langsung ke Barang Rusak (stok sisa 3)
        $switchMulti = Item::factory()->create([
            'item_code' => 'SW-SPL-05',
            'name' => 'Switch Cisco 8P',
            'type' => ItemType::ASET,
            'stock' => 5,
            'status' => ItemStatus::TERSEDIA,
        ]);

        $schedMulti = PreventiveSchedule::factory()->create([
            'item_id' => $switchMulti->id,
            'workflow_stage' => 'in_maintenance',
            'quantity' => 5,
            'maintenance_date' => '2026-11-11',
        ]);

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $schedMulti->id), [
                'mode' => 'split',
                'damaged_quantity' => 2,
                'damage_disposition' => 'broken',
                'damage_notes' => '2 unit switch power regulator gosong.',
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        $schedMulti->refresh();
        $switchMulti->refresh();

        $this->assertSame('safe', $schedMulti->workflow_stage);
        $this->assertSame(3, $schedMulti->quantity);
        $this->assertSame(3, $schedMulti->safe_quantity);
        $this->assertFalse($switchMulti->trashed());
        $this->assertSame(3, $switchMulti->stock);

        $brokenChild = PreventiveSchedule::where('parent_schedule_id', $schedMulti->id)->firstOrFail();
        $this->assertSame('written_off', $brokenChild->workflow_stage);

        $this->assertSame(2, DamagedAsset::count());
        $this->assertSame(3, (int) DamagedAsset::sum('quantity'));

        Carbon::setTestNow();
    }

    public function test_stage_4_repairs_mark_broken_moves_item_to_damaged_registry_and_removes_from_master(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        $item = Item::factory()->create([
            'item_code' => 'MKT-ST4-01',
            'name' => 'MikroTik hAP Lite',
            'type' => ItemType::ASET,
            'stock' => 1,
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $repairSchedule = PreventiveSchedule::factory()->create([
            'item_id' => $item->id,
            'workflow_stage' => 'needs_repair',
            'status' => 'paused',
            'quantity' => 1,
            'safe_quantity' => 0,
            'damaged_quantity' => 1,
            'damage_notes' => 'Board LAN mati total setelah dicek lab.',
        ]);

        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.repairs'))
            ->assertOk()
            ->assertSee('Masuk Barang Rusak')
            ->assertSee('Masukkan ke Tiket Barang Rusak');

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.mark-broken', $repairSchedule->id), [
                'damage_reason' => 'Board LAN mati permanen dan IC tidak tersedia.',
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        $repairSchedule->refresh();
        $this->assertSame('written_off', $repairSchedule->workflow_stage);
        $this->assertTrue(Item::withTrashed()->findOrFail($item->id)->trashed());

        $record = DamagedAsset::firstOrFail();
        $this->assertSame('preventive_maintenance', $record->source);
        $this->assertSame($repairSchedule->id, $record->preventive_schedule_id);
        $this->assertSame('Board LAN mati permanen dan IC tidak tersedia.', $record->damage_reason);

        // Tidak boleh mencatat duplikat jika dipanggil ulang
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.mark-broken', $repairSchedule->id), [
                'damage_reason' => 'Duplikat',
            ])
            ->assertRedirect(route('maintenance.schedules.repairs'))
            ->assertSessionHas('error');

        $this->assertSame(1, DamagedAsset::count());
    }

    public function test_grouped_item_name_maintenance_schedules_all_matching_units_and_supports_bulk_safe_triage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:45:00', 'Asia/Jakarta'));

        $teknisi = User::factory()->teknisi()->create();

        $tp14 = Item::factory()->create([
            'item_code' => 'TL844_14',
            'name' => 'TPLINK Router 14',
            'type' => ItemType::ASET,
            'stock' => 1,
            'location' => 'Ruang Alat',
        ]);

        $tp17 = Item::factory()->create([
            'item_code' => 'TL840_17',
            'name' => 'TPLINK Router 17',
            'type' => ItemType::ASET,
            'stock' => 1,
            'location' => 'Ruang Alat',
        ]);

        $tpSwitch = Item::factory()->create([
            'item_code' => 'TL-SG108',
            'name' => 'TP-Link Switch 8P',
            'type' => ItemType::ASET,
            'stock' => 2,
            'location' => 'Lab SIJA 2',
        ]);

        // Page 1 otomatis mengelompokkan "TPLINK Router" (2 barang) DAN kelompok merek "Semua TP-Link" (3 barang)
        $this->actingAs($teknisi)
            ->get(route('maintenance.schedules.index'))
            ->assertOk()
            ->assertSee('TPLINK Router — 2 barang (2 unit)')
            ->assertSee('Semua TP-Link — 3 barang (4 unit)');

        // Submit 1 kelompok barang ("TPLINK") langsung menjadwalkan seluruh 3 unit sekaligus ke Sedang Di-Maintenance
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.store'), [
                'item_ids' => [$tp14->id, $tp17->id, $tpSwitch->id],
                'quantities' => [
                    $tp14->id => 1,
                    $tp17->id => 1,
                    $tpSwitch->id => 2,
                ],
                'group_label' => 'TPLINK',
                'title' => 'Maintenance Rutin Perangkat TPLINK',
                'maintenance_date' => '2026-11-11',
                'interval_months' => 3,
                'notes' => 'Cek seluruh port & adaptor.',
                'go_to_progress' => 1,
            ])
            ->assertRedirect(route('maintenance.schedules.in-progress'));

        $this->assertSame(3, PreventiveSchedule::where('workflow_stage', 'in_maintenance')->count());

        $s14 = PreventiveSchedule::where('item_id', $tp14->id)->firstOrFail();
        $s17 = PreventiveSchedule::where('item_id', $tp17->id)->firstOrFail();
        $sSwitch = PreventiveSchedule::where('item_id', $tpSwitch->id)->firstOrFail();

        $this->assertSame(1, $s14->quantity);
        $this->assertSame(1, $s17->quantity);
        $this->assertSame(2, $sSwitch->quantity);
        $this->assertSame('Maintenance Rutin Perangkat TPLINK', $s14->title);

        // Bulk triage Aman untuk 2 unit (TL844_14 & TL-SG108) -> masuk ke Barang Aman sekaligus
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.bulk-triage-safe'), [
                'schedule_ids' => [$s14->id, $sSwitch->id],
            ])
            ->assertRedirect(route('maintenance.schedules.safe'));

        $s14->refresh();
        $s17->refresh();
        $sSwitch->refresh();

        $this->assertSame('safe', $s14->workflow_stage);
        $this->assertSame('safe', $sSwitch->workflow_stage);
        $this->assertSame(2, $sSwitch->safe_quantity);
        $this->assertSame('in_maintenance', $s17->workflow_stage);

        // Sisa unit TL840_17 dinyatakan Rusak -> Perlu Diperbaiki -> Masukkan ke Tiket Barang Rusak spesifik TL840_17
        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.triage', $s17->id), [
                'mode' => 'split',
                'damaged_quantity' => 1,
                'damage_notes' => 'Port WAN mati total.',
            ])
            ->assertRedirect(route('maintenance.schedules.repairs'));

        $this->actingAs($teknisi)
            ->post(route('maintenance.schedules.submit-ticket', $s17->id), [
                'issue_description' => 'Port WAN mati total pada TPLINK Router 17.',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'open']));

        $ticket = MaintenanceTicket::firstOrFail();
        $this->assertSame($tp17->id, $ticket->item_id);
        $this->assertSame(ItemStatus::TERSEDIA, $tp14->fresh()->status);
        $this->assertSame(ItemStatus::UNDER_MAINTENANCE, $tp17->fresh()->status);

        Carbon::setTestNow();
    }
}
