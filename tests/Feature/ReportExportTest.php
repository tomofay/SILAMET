<?php

namespace Tests\Feature;

use App\Enums\GateAction;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Enums\ServiceType;
use App\Enums\TicketStatus;
use App\Exports\ReportExport;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_reports_index_with_real_counts_and_student_is_forbidden(): void
    {
        $kaprog = User::factory()->kaprog()->create();
        $siswa = User::factory()->siswa()->create();

        Item::factory()->count(2)->available()->create();

        $this->actingAs($siswa)
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($kaprog)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Unduh Berkas Laporan Resmi')
            ->assertSee('2 Item')
            ->assertDontSee('Dasbor Data Laporan');
    }

    public function test_all_four_pdf_reports_generate_valid_pdf_documents(): void
    {
        $kaprog = User::factory()->kaprog()->create(['name' => 'Kepala Program SIJA']);
        $student = User::factory()->siswa()->create(['name' => 'Raka Pratama']);
        $item = Item::factory()->available()->create([
            'item_code' => 'SLM-RPT-001',
            'epc_tag' => 'E200RPT001',
            'name' => 'MikroTik RB4011',
        ]);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
            'usage_duration' => LoanDuration::SATU_HARI,
            'borrowed_at' => now()->subHours(2),
            'due_at' => now()->addDay(),
        ]);

        GateLog::create([
            'epc_tag_scanned' => 'E200RPT001',
            'action_status' => GateAction::DENIED,
            'capture_image_path' => 'uploads/photos/gate/sample.jpg',
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $kaprog->id,
            'issue_description' => 'Port SFP tidak terdeteksi',
            'status' => TicketStatus::RESOLVED,
        ]);

        MaintenanceLog::create([
            'ticket_id' => $ticket->id,
            'handled_by' => $kaprog->id,
            'action_taken' => 'Penggantian modul SFP baru',
            'service_type' => ServiceType::INTERNAL,
            'cost' => 350000,
            'repaired_at' => now(),
        ]);

        foreach (['inventaris', 'inventory', 'loans', 'gate_logs', 'maintenance'] as $type) {
            $response = $this->actingAs($kaprog)->get(route('reports.pdf', $type));

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');

            $content = $response->getContent();
            $this->assertStringStartsWith('%PDF-', $content, "PDF {$type} harus valid.");
        }
    }

    public function test_excel_reports_download_real_xlsx_and_respect_filters(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-10 13:45:00');

        try {
            $kabeng = User::factory()->kabeng()->create();
            $student = User::factory()->siswa()->create(['name' => 'Siti Aisyah']);

            $itemOk = Item::factory()->available()->create([
                'item_code' => 'SLM-XLS-01',
                'name' => 'Tang Krimping RJ45',
                'type' => ItemType::ASET,
                'status' => ItemStatus::TERSEDIA,
            ]);
            $itemMaint = Item::factory()->create([
                'item_code' => 'SLM-XLS-02',
                'name' => 'Fusion Splicer',
                'type' => ItemType::ASET,
                'status' => ItemStatus::UNDER_MAINTENANCE,
            ]);

            // Loan di dalam rentang tanggal vs luar rentang
            Loan::factory()->create([
                'user_id' => $student->id,
                'item_id' => $itemOk->id,
                'status' => LoanStatus::AKTIF,
                'borrowed_at' => now()->subDay(),
                'due_at' => now()->addDay(),
            ]);
            Loan::factory()->create([
                'user_id' => $student->id,
                'item_id' => $itemMaint->id,
                'status' => LoanStatus::SELESAI,
                'borrowed_at' => now()->subDays(30),
                'due_at' => now()->subDays(29),
                'returned_at' => now()->subDays(29),
            ]);

            // Verifikasi payload filter secara langsung
            $service = app(ReportGenerationService::class);
            $filteredInv = $service->buildReportPayload('inventaris', ['status' => 'under_maintenance']);
            $this->assertCount(1, $filteredInv['rows']);
            $this->assertSame('SLM-XLS-02', $filteredInv['rows'][0][1]);

            $filteredLoans = $service->buildReportPayload('loans', [
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->toDateString(),
                'status' => 'aktif',
            ]);
            $this->assertCount(1, $filteredLoans['rows']);
            $this->assertSame('SLM-XLS-01', $filteredLoans['rows'][0][3]);

            // Verifikasi download Excel nyata menghasilkan file XLSX (ZIP PK header)
            foreach (['inventaris', 'loans', 'gate_logs', 'maintenance'] as $type) {
                $res = $this->actingAs($kabeng)->get(route('reports.excel', $type));
                $res->assertOk();
                $disposition = (string) $res->headers->get('Content-Disposition');
                $this->assertStringContainsString('.xlsx', $disposition);
                $binary = $res->getFile()->getContent();
                $this->assertStringStartsWith('PK', $binary, "Excel {$type} harus berupa OOXML XLSX valid.");
            }

            // Verifikasi Maatwebsite Excel facade dipanggil dengan ReportExport & nama file akurat
            Excel::fake();
            $this->actingAs($kabeng)
                ->get(route('reports.excel', [
                    'type' => 'loans',
                    'start_date' => now()->subDays(3)->toDateString(),
                    'end_date' => now()->toDateString(),
                ]))
                ->assertOk();
            Excel::assertDownloaded(
                'silamet_loans_20261110_134500.xlsx',
                fn (ReportExport $export) => count($export->array()) === 1
            );
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_empty_reports_generate_cleanly_without_errors(): void
    {
        $kaprog = User::factory()->kaprog()->create();

        foreach (['inventaris', 'loans', 'gate_logs', 'maintenance'] as $type) {
            $pdf = $this->actingAs($kaprog)->get(route('reports.pdf', $type));
            $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());

            $xls = $this->actingAs($kaprog)->get(route('reports.excel', $type));
            $xls->assertOk();
            $this->assertStringStartsWith('PK', $xls->getFile()->getContent());
        }
    }

    public function test_invalid_report_type_or_invalid_date_range_returns_error(): void
    {
        $kaprog = User::factory()->kaprog()->create();

        $this->actingAs($kaprog)
            ->get(route('reports.pdf', 'tidak_ada'))
            ->assertNotFound();

        $this->actingAs($kaprog)
            ->from(route('reports.index'))
            ->get(route('reports.pdf', [
                'type' => 'loans',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-01',
            ]))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_reports_dashboard_displays_live_metrics_breakdowns_and_tables_on_dedicated_page(): void
    {
        $kaprog = User::factory()->kaprog()->create(['name' => 'Kepala Program SIJA']);
        $teknisi = User::factory()->teknisi()->create(['name' => 'Teknisi Lab SIJA']);
        $student = User::factory()->siswa()->create(['name' => 'Budi Santoso', 'nis_nip' => '2401999']);

        $item = Item::factory()->available()->create([
            'item_code' => 'SLM-DSH-01',
            'epc_tag' => 'E200DSH001',
            'name' => 'Router MikroTik hEX',
            'location' => 'Rak A-01',
        ]);

        Loan::factory()->create([
            'user_id' => $student->id,
            'item_id' => $item->id,
            'status' => LoanStatus::AKTIF,
            'usage_duration' => LoanDuration::SATU_HARI,
            'borrowed_at' => now()->subHour(),
            'due_at' => now()->addHours(20),
        ]);

        GateLog::create([
            'epc_tag_scanned' => 'E200DSH001',
            'action_status' => GateAction::DENIED,
            'capture_image_path' => 'uploads/photos/gate/dsh.jpg',
        ]);

        $ticket = MaintenanceTicket::create([
            'item_id' => $item->id,
            'reported_by' => $teknisi->id,
            'issue_description' => 'Port ether2 bermasalah',
            'status' => TicketStatus::RESOLVED,
        ]);

        MaintenanceLog::create([
            'ticket_id' => $ticket->id,
            'handled_by' => $teknisi->id,
            'action_taken' => 'Ganti konektor & solder ulang',
            'spareparts_used' => 'Port RJ45 Gigabit',
            'service_type' => ServiceType::VENDOR_LUAR,
            'vendor_name' => 'Bengkel Jaringan Cibinong',
            'cost' => 175000,
            'repaired_at' => now(),
        ]);

        // Halaman /reports hanya berisi kartu pilihan & tautan ke halaman /reports/dashboard/{type}
        $this->actingAs($kaprog)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertDontSee('Dasbor Data Laporan')
            ->assertSee(route('reports.dashboard', 'inventaris'))
            ->assertSee(route('reports.dashboard', 'loans'))
            ->assertSee(route('reports.dashboard', 'gate_logs'))
            ->assertSee(route('reports.dashboard', 'maintenance'));

        // Siswa dilarang mengakses halaman dasbor laporan
        $this->actingAs($student)
            ->get(route('reports.dashboard', 'inventaris'))
            ->assertForbidden();

        // Tipe dasbor tidak dikenal mengembalikan 404
        $this->actingAs($kaprog)
            ->get(route('reports.dashboard', 'tidak_ada'))
            ->assertNotFound();

        // 1. Inventaris di halaman terpisah
        $this->actingAs($kaprog)
            ->get(route('reports.dashboard', 'inventaris'))
            ->assertOk()
            ->assertSee('Dasbor Data Laporan')
            ->assertSee('SLM-DSH-01')
            ->assertSee('Router MikroTik hEX')
            ->assertSee('Rak A-01')
            ->assertSee('Kembali ke Pusat Laporan');

        // 2. Peminjaman Siswa
        $this->actingAs($kaprog)
            ->get(route('reports.dashboard', ['type' => 'loans', 'q' => 'Budi']))
            ->assertOk()
            ->assertSee('Rekapitulasi Peminjaman Siswa')
            ->assertSee('Budi Santoso')
            ->assertSee('2401999')
            ->assertSee('SLM-DSH-01');

        // 3. Gate Security RFID
        $this->actingAs($kaprog)
            ->get(route('reports.dashboard', ['type' => 'gate_logs', 'status' => 'denied']))
            ->assertOk()
            ->assertSee('E200DSH001')
            ->assertSee('DENIED')
            ->assertSee('Router MikroTik hEX');

        // 4. Maintenance & Biaya — kaprog bisa melihat laporan & biaya tanpa link ke route maintenance.* (role restricted)
        $kaprogMaint = $this->actingAs($kaprog)
            ->get(route('reports.dashboard', 'maintenance'));
        $kaprogMaint->assertOk()
            ->assertSee('Rp 175.000')
            ->assertSee('Bengkel Jaringan Cibinong')
            ->assertDontSee(route('maintenance.show', $ticket->id));

        // Teknisi melihat tabel Maintenance lengkap beserta link detail tiket
        $this->actingAs($teknisi)
            ->get(route('reports.dashboard', 'maintenance'))
            ->assertOk()
            ->assertSee('Rp 175.000')
            ->assertSee(route('maintenance.show', $ticket->id));

        // Tanggal invalid di GET /reports/dashboard tidak redirect loop, tampil inline error
        $this->actingAs($kaprog)
            ->get(route('reports.dashboard', [
                'type' => 'loans',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-01',
            ]))
            ->assertOk()
            ->assertSee('Tanggal akhir laporan tidak boleh lebih awal dari tanggal mulai.');
    }
}
