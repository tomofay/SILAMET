<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\ServiceType;
use App\Enums\StockMovementType;
use App\Enums\TicketStatus;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknisi_can_start_internal_repair_and_complete_without_cost_or_receipt(): void
    {
        $teknisi = User::factory()->teknisi()->create(['name' => 'Teknisi Lab SIJA']);
        $item = Item::factory()->create([
            'item_code' => 'SLM-MR-01',
            'name' => 'MikroTik RB750Gr3',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Port ether1 tidak link',
            'status' => TicketStatus::OPEN,
        ]);

        // 1. Mulai perbaikan internal sekolah (keterangan tambahan opsional / diisi)
        $this->actingAs($teknisi)
            ->post(route('maintenance.start', $ticket->id), [
                'service_type' => 'internal',
                'action_taken' => 'Pembersihan pin konektor RJ45 port 1 & reset konfigurasi',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'in_progress']));

        $ticket->refresh();
        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->status);

        $startLog = MaintenanceLog::where('ticket_id', $ticket->id)->firstOrFail();
        $this->assertSame(ServiceType::INTERNAL, $startLog->service_type);
        $this->assertNull($startLog->vendor_name);
        $this->assertSame(0.0, (float) $startLog->cost);

        $this->actingAs($teknisi)
            ->get(route('maintenance.index', ['status' => 'in_progress']))
            ->assertOk()
            ->assertSee('Internal Sekolah')
            ->assertSee('Tanpa biaya');

        // 2. Selesaikan perbaikan internal TANPA biaya & TANPA upload struk (keduanya opsional)
        $this->actingAs($teknisi)
            ->post(route('maintenance.complete', $ticket->id), [
                'service_type' => 'internal',
                'action_taken' => 'Pin dibersihkan, port ether1 link 1Gbps normal kembali.',
                'spareparts_used' => '',
                'cost' => '',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'resolved']));

        $ticket->refresh();
        $item->refresh();

        $this->assertSame(TicketStatus::RESOLVED, $ticket->status);
        $this->assertSame(ItemStatus::TERSEDIA, $item->status);
        $this->assertSame(2, $ticket->logs()->count());

        $finalLog = $ticket->latestLog;
        $this->assertNotNull($finalLog);
        $this->assertSame(0.0, (float) $finalLog->cost);
        $this->assertNull($finalLog->receipt_path);

        $this->actingAs($teknisi)
            ->get(route('maintenance.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Internal Sekolah')
            ->assertSee('Tanpa biaya');
    }

    public function test_vendor_repair_requires_vendor_name_and_supports_progress_log_cost_and_receipt(): void
    {
        Storage::fake('public');
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'fake-bot-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.notify_ticket_resolved', true);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]], 200),
        ]);

        $kabeng = User::factory()->kabeng()->create(['name' => 'Kepala Bengkel SIJA']);
        $item = Item::factory()->create([
            'item_code' => 'SLM-MR-02',
            'name' => 'Fusion Splicer AI-9',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $kabeng->id,
            'issue_description' => 'Elektroda tidak memercik saat splicing',
            'status' => TicketStatus::OPEN,
        ]);

        // Validasi gagal jika pilih vendor_luar tapi nama vendor dikosongkan
        $this->actingAs($kabeng)
            ->from(route('maintenance.index', ['status' => 'open']))
            ->post(route('maintenance.start', $ticket->id), [
                'service_type' => 'vendor_luar',
                'vendor_name' => '',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'open']))
            ->assertSessionHasErrors('vendor_name');

        // Mulai perbaikan ke vendor luar lengkap dengan nama vendor & estimasi selesai
        $this->actingAs($kabeng)
            ->post(route('maintenance.start', $ticket->id), [
                'service_type' => 'vendor_luar',
                'vendor_name' => 'CV Fiber Optik Nusantara',
                'estimated_finish_at' => '2026-11-18',
                'action_taken' => 'Dikirim ke bengkel kalibrasi Fiber Optik untuk pengecekan modul HV.',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'in_progress']));

        $this->actingAs($kabeng)
            ->get(route('maintenance.index', ['status' => 'in_progress']))
            ->assertOk()
            ->assertSee('Vendor Luar')
            ->assertSee('CV Fiber Optik Nusantara')
            ->assertSee('Est: 18/11/2026');

        // Tambah log progres bertahap dari halaman detail
        $this->actingAs($kabeng)
            ->post(route('maintenance.logs.store', $ticket->id), [
                'service_type' => 'vendor_luar',
                'vendor_name' => 'CV Fiber Optik Nusantara',
                'action_taken' => 'Konfirmasi vendor: elektroda aus dan perlu penggantian sepasang.',
                'spareparts_used' => 'Sepasang Elektroda AI-9',
                'cost' => 150000,
            ])
            ->assertRedirect(route('maintenance.show', $ticket->id));

        // Selesaikan perbaikan dengan biaya jasa & upload foto struk nota (1x1 PNG valid tanpa dependensi GD JPEG)
        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $fakeReceipt = UploadedFile::fake()->createWithContent('nota-servis.png', $pngBinary);

        $this->actingAs($kabeng)
            ->post(route('maintenance.complete', $ticket->id), [
                'service_type' => 'vendor_luar',
                'vendor_name' => 'CV Fiber Optik Nusantara',
                'action_taken' => 'Penggantian elektroda & kalibrasi arc selesai. Uji splice core 0.01dB.',
                'spareparts_used' => 'Elektroda AI-9 + Jasa Kalibrasi',
                'cost' => 450000,
                'receipt' => $fakeReceipt,
                'return_to' => 'show',
            ])
            ->assertRedirect(route('maintenance.show', $ticket->id));

        $ticket->refresh();
        $item->refresh();
        $this->assertSame(TicketStatus::RESOLVED, $ticket->status);
        $this->assertSame(ItemStatus::TERSEDIA, $item->status);
        $this->assertSame(3, $ticket->logs()->count());

        $completionLog = $ticket->latestLog;
        $this->assertNotNull($completionLog);
        $this->assertSame('CV Fiber Optik Nusantara', $completionLog->vendor_name);
        $this->assertSame('Elektroda AI-9 + Jasa Kalibrasi', $completionLog->spareparts_used);
        $this->assertSame(450000.0, (float) $completionLog->cost);
        $this->assertNotNull($completionLog->receipt_path);
        Storage::disk('public')->assertExists($completionLog->receipt_path);

        // Total biaya akumulatif (150.000 + 450.000 = 600.000) & link struk tampil di detail & list
        $this->actingAs($kabeng)
            ->get(route('maintenance.show', $ticket->id))
            ->assertOk()
            ->assertSee('Total Biaya: Rp 600.000')
            ->assertSee('Lihat Struk Nota')
            ->assertSee('CV Fiber Optik Nusantara');

        $this->actingAs($kabeng)
            ->get(route('maintenance.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Rp 600.000')
            ->assertSee('Lihat Struk');

        // Akses endpoint streaming struk berhasil untuk staf teknisi/kabeng
        $receiptResponse = $this->actingAs($kabeng)
            ->get(route('maintenance.receipt', $completionLog->id));
        $receiptResponse->assertOk();
        $this->assertStringStartsWith('image/', (string) $receiptResponse->headers->get('Content-Type'));

        // Telegram notifikasi selesai tetap terkirim
        Http::assertSent(fn ($req) => str_contains((string) $req['text'], 'ALAT SELESAI DIPERBAIKI'));

        // Laporan M&R PDF/Excel menyertakan vendor & sparepart serta menjumlahkan total biaya
        $reportService = app(\App\Services\ReportGenerationService::class);
        $reportPayload = $reportService->buildReportPayload('maintenance');
        $this->assertSame(600000.0, (float) $reportPayload['summary']['total_cost']);
        $this->assertStringContainsString('CV Fiber Optik Nusantara', $reportPayload['rows'][0][6]);
        $this->assertStringContainsString('Elektroda AI-9 + Jasa Kalibrasi', $reportPayload['rows'][0][6]);
    }

    public function test_receipt_and_repair_endpoints_reject_unauthorized_roles_and_reject_invalid_files(): void
    {
        Storage::fake('public');

        $teknisi = User::factory()->teknisi()->create();
        $siswa = User::factory()->siswa()->create();
        $kaprog = User::factory()->kaprog()->create();

        $item = Item::factory()->available()->create();
        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Kabel tester putus',
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        $path = UploadedFile::fake()
            ->createWithContent('struk.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF")
            ->store('uploads/receipts/maintenance', 'public');

        $log = MaintenanceLog::create([
            'ticket_id' => $ticket->id,
            'handled_by' => $teknisi->id,
            'action_taken' => 'Selesai diganti konektor',
            'service_type' => ServiceType::VENDOR_LUAR,
            'vendor_name' => 'Toko Kabel Jaya',
            'cost' => 75000,
            'receipt_path' => $path,
            'repaired_at' => now(),
        ]);

        // Siswa dan kaprog dilarang mengakses aksi/struk M&R
        $this->actingAs($siswa)->post(route('maintenance.start', $ticket->id), [])->assertForbidden();
        $this->actingAs($siswa)->get(route('maintenance.receipt', $log->id))->assertForbidden();
        $this->actingAs($kaprog)->get(route('maintenance.receipt', $log->id))->assertForbidden();

        // Teknisi dapat membuka PDF struk dengan MIME application/pdf
        $this->actingAs($teknisi)
            ->get(route('maintenance.receipt', $log->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Upload file non-gambar/non-PDF ditolak validasi
        $badFile = UploadedFile::fake()->create('script.exe', 50, 'application/octet-stream');
        $this->actingAs($teknisi)
            ->from(route('maintenance.show', $ticket->id))
            ->post(route('maintenance.complete', $ticket->id), [
                'service_type' => 'internal',
                'action_taken' => 'Coba upload file tidak valid',
                'receipt' => $badFile,
            ])
            ->assertRedirect(route('maintenance.show', $ticket->id))
            ->assertSessionHasErrors('receipt');
    }

    public function test_action_buttons_and_edit_pages_match_ticket_lifecycle_stages(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->create([
            'item_code' => 'SLM-LIFE-01',
            'name' => 'Switch Cisco Catalyst',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Fan berbunyi keras',
            'status' => TicketStatus::OPEN,
        ]);

        // Tab OPEN hanya menampilkan Edit & Mulai Perbaikan (tanpa tombol Detail)
        $openList = $this->actingAs($teknisi)->get(route('maintenance.index', ['status' => 'open']));
        $openList->assertOk()
            ->assertSee(route('maintenance.edit', $ticket->id))
            ->assertSee('open-start-repair-modal')
            ->assertDontSee('href="'.route('maintenance.show', $ticket->id).'"', false);

        // Halaman Edit saat OPEN hanya berisi Deskripsi, Status, Tempat Perbaikan, Catatan (tanpa Nota/Biaya)
        $openEdit = $this->actingAs($teknisi)->get(route('maintenance.edit', $ticket->id));
        $openEdit->assertOk()
            ->assertSee('Tempat Perbaikan')
            ->assertSee('Catatan (Opsional)')
            ->assertDontSee('Catat Nota Pembelian / Biaya Bertahap');

        // Edit pada OPEN dapat menyimpan tempat perbaikan vendor + catatan sambil memindahkan ke in_progress
        $this->actingAs($teknisi)
            ->put(route('maintenance.update', $ticket->id), [
                'return_to' => 'tab',
                'issue_description' => 'Fan berbunyi keras & PSU panas',
                'status' => 'in_progress',
                'service_type' => 'vendor_luar',
                'vendor_name' => 'Bengkel Server Jakarta',
                'estimated_finish_at' => '2026-11-20',
                'notes' => 'Unit dikirim ke vendor untuk pemeriksaan kipas.',
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'in_progress']));

        // Tab SEDANG DIKERJAKAN hanya menampilkan Edit & Selesai (tanpa tombol Detail)
        $progressList = $this->actingAs($teknisi)->get(route('maintenance.index', ['status' => 'in_progress']));
        $progressList->assertOk()
            ->assertSee(route('maintenance.edit', $ticket->id))
            ->assertSee('open-complete-repair-modal')
            ->assertDontSee('href="'.route('maintenance.show', $ticket->id).'"', false);

        // Halaman Edit saat IN_PROGRESS menyatukan ringkasan detail, riwayat log, & form nota bertahap opsional
        $progressEdit = $this->actingAs($teknisi)->get(route('maintenance.edit', $ticket->id));
        $progressEdit->assertOk()
            ->assertSee('Penanganan Saat Ini')
            ->assertSee('Bengkel Server Jakarta')
            ->assertSee('Riwayat Tindakan &amp; Struk Sejauh Ini', false)
            ->assertSee('Catat Nota Pembelian / Biaya Bertahap');

        // Submit nota bertahap dari halaman Edit tetap kembali ke halaman Edit
        $this->actingAs($teknisi)
            ->post(route('maintenance.logs.store', $ticket->id), [
                'return_to' => 'edit',
                'service_type' => 'vendor_luar',
                'vendor_name' => 'Bengkel Server Jakarta',
                'action_taken' => 'DP pembelian fan replacement',
                'spareparts_used' => 'Cooling Fan 12V',
                'cost' => 100000,
            ])
            ->assertRedirect(route('maintenance.edit', $ticket->id));

        // Selesaikan tiket
        $this->actingAs($teknisi)
            ->post(route('maintenance.complete', $ticket->id), [
                'service_type' => 'vendor_luar',
                'vendor_name' => 'Bengkel Server Jakarta',
                'action_taken' => 'Kipas baru terpasang, suhu normal.',
                'cost' => 50000,
            ])
            ->assertRedirect(route('maintenance.index', ['status' => 'resolved']));

        // Tab SELESAI hanya menampilkan tombol Detail Laporan Lengkap (tanpa tombol Edit)
        $resolvedList = $this->actingAs($teknisi)->get(route('maintenance.index', ['status' => 'resolved']));
        $resolvedList->assertOk()
            ->assertSee(route('maintenance.show', $ticket->id))
            ->assertDontSee('href="'.route('maintenance.edit', $ticket->id).'"', false);

        // Halaman Detail Laporan saat RESOLVED menampilkan ringkasan lengkap & bebas tombol Edit
        $resolvedShow = $this->actingAs($teknisi)->get(route('maintenance.show', $ticket->id));
        $resolvedShow->assertOk()
            ->assertSee('Lampiran Struk &amp; Nota Pembayaran', false)
            ->assertSee('Cooling Fan 12V')
            ->assertSee('Rp 150.000')
            ->assertDontSee(route('maintenance.edit', $ticket->id));
    }

    public function test_failed_php_upload_returns_indonesian_error_inside_modal_never_raw_translation_key(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $item = Item::factory()->create([
            'item_code' => 'SLM-UPL-01',
            'name' => 'Switch Hub Lab 01',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Port uplink putus',
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        $tmpPath = tempnam(sys_get_temp_dir(), 'silamet_ini_err_');
        file_put_contents($tmpPath, 'dummy-oversized-content');

        try {
            $iniFailedFile = new UploadedFile($tmpPath, 'struk-besar.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

            $response = $this->actingAs($teknisi)
                ->from(route('maintenance.index', ['status' => 'in_progress']))
                ->post(route('maintenance.complete', $ticket->id), [
                    '_modal_mode' => 'complete',
                    '_modal_ticket_id' => $ticket->id,
                    '_modal_item_name' => $item->name,
                    '_modal_item_code' => $item->item_code,
                    '_modal_issue' => $ticket->issue_description,
                    'service_type' => 'internal',
                    'action_taken' => 'Penggantian port RJ45',
                    'cost' => 30000,
                    'receipt' => $iniFailedFile,
                ]);

            $response->assertRedirect(route('maintenance.index', ['status' => 'in_progress']))
                ->assertSessionHasErrors('receipt');

            $receiptError = session('errors')->first('receipt');
            $this->assertStringNotContainsString('validation.', $receiptError);
            $this->assertStringContainsString('File struk gagal diunggah', $receiptError);

            // Pastikan redirect berikutnya menampilkan pesan error di dalam modal selesai perbaikan
            $redirectedPage = $this->actingAs($teknisi)
                ->withSession(session()->all())
                ->get(route('maintenance.index', ['status' => 'in_progress']));
            $redirectedPage->assertOk()
                ->assertSee('complete-modal-server-errors')
                ->assertSee('File struk gagal diunggah');

            // Simulasi error yang sama pada log progres bertahap di halaman edit
            $iniFailedLogFile = new UploadedFile($tmpPath, 'struk-dp.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
            $logResponse = $this->actingAs($teknisi)
                ->from(route('maintenance.edit', $ticket->id))
                ->post(route('maintenance.logs.store', $ticket->id), [
                    '_form_mode' => 'progress',
                    'return_to' => 'edit',
                    'service_type' => 'internal',
                    'action_taken' => 'DP sparepart',
                    'receipt' => $iniFailedLogFile,
                ]);

            $logResponse->assertRedirect(route('maintenance.edit', $ticket->id))
                ->assertSessionHasErrors('receipt');
            $this->assertStringNotContainsString('validation.', session('errors')->first('receipt'));
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_completing_repair_as_permanently_broken_moves_item_to_damaged_registry_and_removes_single_unit_from_master_aset(): void
    {
        Storage::fake('public');
        Config::set('services.telegram.enabled', true);
        Config::set('services.telegram.bot_token', 'fake-bot-token');
        Config::set('services.telegram.group_chat_id', '-1001234567890');
        Config::set('services.telegram.notify_ticket_resolved', true);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]], 200),
        ]);

        $teknisi = User::factory()->teknisi()->create(['name' => 'Teknisi Lab SIJA']);
        $item = Item::factory()->create([
            'item_code' => 'SLM-BRK-01',
            'epc_tag' => 'E200BRK001',
            'name' => 'TP-Link Archer C6',
            'category' => 'TPLINK',
            'type' => ItemType::ASET,
            'stock' => 1,
            'location' => 'Rack Lab 2',
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $activeSchedule = PreventiveSchedule::factory()->create([
            'item_id' => $item->id,
            'workflow_stage' => 'safe',
            'status' => 'scheduled',
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Mainboard router konslet kena petir',
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        // Bar ke-3 "Barang Rusak" tampil di navigasi M&R
        $this->actingAs($teknisi)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Tiket Kerusakan (Kuratif)')
            ->assertSee('Jadwal Maintenance Barang (Preventif)')
            ->assertSee('Barang Rusak');

        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $receipt = UploadedFile::fake()->createWithContent('cek-vendor.png', $pngBinary);

        $this->actingAs($teknisi)
            ->post(route('maintenance.complete', $ticket->id), [
                'outcome' => 'broken',
                'service_type' => 'vendor_luar',
                'vendor_name' => 'Bengkel Router Cibinong',
                'action_taken' => 'IC chipset utama terbakar dan sparepart diskontinyu, tidak dapat diperbaiki.',
                'cost' => 50000,
                'receipt' => $receipt,
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        // Ticket selesai dengan outcome=broken, tapi item relation masih terbaca via withTrashed
        $ticket->refresh();
        $this->assertSame(TicketStatus::RESOLVED, $ticket->status);
        $this->assertSame('broken', $ticket->outcome);
        $this->assertNotNull($ticket->item);
        $this->assertSame('TP-Link Archer C6', $ticket->item->name);

        // Item stok menjadi 0 & soft-deleted dari Master Aset aktif
        $trashedItem = Item::withTrashed()->findOrFail($item->id);
        $this->assertTrue($trashedItem->trashed());
        $this->assertSame(0, $trashedItem->stock);
        $this->assertSame(0, Item::query()->where('id', $item->id)->count());

        // Preventive schedule aktif item tersebut ikut berstatus written_off
        $activeSchedule->refresh();
        $this->assertSame('written_off', $activeSchedule->workflow_stage);
        $this->assertSame('paused', $activeSchedule->status);

        // Record masuk ke damaged_assets
        $damagedRecord = DamagedAsset::firstOrFail();
        $this->assertSame($item->id, $damagedRecord->item_id);
        $this->assertSame('SLM-BRK-01', $damagedRecord->item_code);
        $this->assertSame('TP-Link Archer C6', $damagedRecord->item_name);
        $this->assertSame(1, $damagedRecord->quantity);
        $this->assertSame('repair_ticket', $damagedRecord->source);
        $this->assertSame($ticket->id, $damagedRecord->maintenance_ticket_id);
        $this->assertSame(50000.0, (float) $damagedRecord->repair_cost);
        $this->assertSame('Bengkel Router Cibinong', $damagedRecord->vendor_name);

        // Telegram "ALAT SELESAI DIPERBAIKI" tidak dikirim untuk barang rusak permanen
        Http::assertNothingSent();

        // Halaman Barang Rusak menampilkan catatan tanpa opsi mengembalikan ke Master Aset
        $this->actingAs($teknisi)
            ->get(route('maintenance.damaged.index'))
            ->assertOk()
            ->assertSee('TP-Link Archer C6')
            ->assertSee('SLM-BRK-01')
            ->assertSee('IC chipset utama terbakar')
            ->assertSee('Tiket #'.$ticket->id);

        // Detail tiket & Master Aset konsisten
        $this->actingAs($teknisi)
            ->get(route('maintenance.show', $ticket->id))
            ->assertOk()
            ->assertSee('Rusak Permanen');

        $this->actingAs($teknisi)
            ->get(route('items.index', ['view' => 'all']))
            ->assertOk()
            ->assertDontSee('SLM-BRK-01');
    }

    public function test_broken_repair_decrements_partial_stock_on_multi_unit_aset_and_consumables(): void
    {
        $teknisi = User::factory()->teknisi()->create();

        // 1. Aset multi-unit (stok 5 -> 2 rusak permanen -> sisa 3 aktif di Master Aset)
        $switch = Item::factory()->create([
            'item_code' => 'SW-MULTI-05',
            'name' => 'Switch Unmanaged 8 Port',
            'type' => ItemType::ASET,
            'stock' => 5,
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticketAset = MaintenanceTicket::create([
            'item_id' => $switch->id,
            'reported_by' => $teknisi->id,
            'issue_description' => '2 unit adaptor switch terbakar',
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        // Validasi menolak jika jumlah unit rusak > stok Master Aset
        $this->actingAs($teknisi)
            ->from(route('maintenance.edit', $ticketAset->id))
            ->post(route('maintenance.complete', $ticketAset->id), [
                'outcome' => 'broken',
                'broken_quantity' => 8,
                'service_type' => 'internal',
                'action_taken' => 'Kelebihan jumlah',
            ])
            ->assertSessionHasErrors('broken_quantity');

        // Catat 2 unit rusak permanen
        $this->actingAs($teknisi)
            ->post(route('maintenance.complete', $ticketAset->id), [
                'outcome' => 'broken',
                'broken_quantity' => 2,
                'service_type' => 'internal',
                'action_taken' => '2 unit short circuit PCB permanen.',
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        $switch->refresh();
        $this->assertFalse($switch->trashed());
        $this->assertSame(3, $switch->stock);
        $this->assertSame(ItemStatus::TERSEDIA, $switch->status);

        // 2. Bahan habis pakai via form Edit (status = resolved, outcome = broken) mengurangi stok & mencatat StockMovement::OUT
        $cableTester = Item::factory()->consumable()->create([
            'item_code' => 'BHP-BRK-10',
            'name' => 'Patch Cord Cat6',
            'stock' => 10,
            'status' => ItemStatus::UNDER_MAINTENANCE,
        ]);

        $ticketBhp = MaintenanceTicket::create([
            'item_id' => $cableTester->id,
            'reported_by' => $teknisi->id,
            'issue_description' => '4 kabel putus bagian dalam',
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        $this->actingAs($teknisi)
            ->put(route('maintenance.update', $ticketBhp->id), [
                'status' => 'resolved',
                'outcome' => 'broken',
                'broken_quantity' => 4,
                'notes' => '4 unit kabel putus permanen dan dibuang.',
            ])
            ->assertRedirect(route('maintenance.damaged.index'));

        $cableTester->refresh();
        $this->assertSame(6, $cableTester->stock);
        $this->assertSame(2, DamagedAsset::count());

        $movement = StockMovement::where('item_id', $cableTester->id)->firstOrFail();
        $this->assertSame(StockMovementType::OUT, $movement->type);
        $this->assertSame(4, $movement->quantity);
        $this->assertSame(10, $movement->stock_before);
        $this->assertSame(6, $movement->stock_after);

        // Update ulang tiket yang sudah berstatus broken tidak mencatat duplikat DamagedAsset
        $this->actingAs($teknisi)
            ->put(route('maintenance.update', $ticketBhp->id), [
                'status' => 'resolved',
                'outcome' => 'repaired',
                'notes' => 'Revisi deskripsi saja',
            ])
            ->assertRedirect(route('maintenance.index'));

        $this->assertSame(2, DamagedAsset::count());
        $this->assertSame(6, $cableTester->fresh()->stock);
        $this->assertSame('broken', $ticketBhp->fresh()->outcome);
    }

    public function test_unauthorized_roles_cannot_access_damaged_assets_registry(): void
    {
        $siswa = User::factory()->siswa()->create();
        $kaprog = User::factory()->kaprog()->create();

        $this->actingAs($siswa)->get(route('maintenance.damaged.index'))->assertForbidden();
        $this->actingAs($kaprog)->get(route('maintenance.damaged.index'))->assertForbidden();
    }
}
