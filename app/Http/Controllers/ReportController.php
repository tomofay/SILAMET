<?php

namespace App\Http\Controllers;

use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportGenerationService $reportService
    ) {}

    /**
     * Tampilkan halaman report selection.
     */
    public function index()
    {
        $counts = [
            'inventaris' => Item::count(),
            'loans' => Loan::count(),
            'gate_logs' => GateLog::count(),
            'maintenance' => MaintenanceTicket::count(),
        ];

        return view('reports.index', compact('counts'));
    }

    /**
     * Download PDF report berdasarkan type dan filter opsional (start_date, end_date, status).
     */
    public function downloadPdf(Request $request, string $type)
    {
        $filters = $this->validateFilters($request);
        $payload = $this->reportService->buildReportPayload($type, $filters);

        return $this->reportService->downloadPdf($payload, $payload['filename']);
    }

    /**
     * Download Excel report berdasarkan type dan filter opsional (start_date, end_date, status).
     */
    public function downloadExcel(Request $request, string $type)
    {
        $filters = $this->validateFilters($request);

        return $this->reportService->exportExcel($type, $filters);
    }

    /**
     * @return array{start_date?: ?string, end_date?: ?string, status?: ?string}
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|max:50',
        ], [
            'start_date.date' => 'Format tanggal mulai laporan tidak valid.',
            'end_date.date' => 'Format tanggal akhir laporan tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir laporan tidak boleh lebih awal dari tanggal mulai.',
        ]);
    }
}
