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
            ->assertSee('2 Item');
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
        \Carbon\Carbon::setTestNow('2026-11-10 13:45:00');
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
        \Carbon\Carbon::setTestNow();
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
}
