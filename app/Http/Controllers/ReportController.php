<?php

namespace App\Http\Controllers;

use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct(
        private ReportGenerationService $reportService
    ) {}

    /**
     * Tampilkan halaman pusat pilihan laporan & ekspor PDF/Excel.
     */
    public function index(Request $request)
    {
        $counts = $this->reportCounts();
        [$filters, $filterErrors] = $this->parseQueryFilters($request);

        return view('reports.index', compact('counts', 'filters', 'filterErrors'));
    }

    /**
     * Tampilkan halaman dasbor statistik & tabel pratinjau khusus untuk 1 jenis laporan.
     */
    public function dashboard(Request $request, ?string $type = null)
    {
        $rawType = (string) ($type ?? $request->query('report', 'inventaris'));
        $activeType = $this->reportService->normalizeType($rawType !== '' ? $rawType : 'inventaris');

        $counts = $this->reportCounts();
        [$filters, $filterErrors] = $this->parseQueryFilters($request);

        $dashboard = $this->reportService->buildDashboardData($activeType, $filters, 15);

        return view('reports.dashboard', compact('counts', 'activeType', 'filters', 'dashboard', 'filterErrors'));
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
     * @return array<string, int>
     */
    private function reportCounts(): array
    {
        return [
            'inventaris' => Item::count(),
            'loans' => Loan::count(),
            'gate_logs' => GateLog::count(),
            'maintenance' => MaintenanceTicket::count(),
        ];
    }

    /**
     * @return array{0: array<string, ?string>, 1: array<int, string>}
     */
    private function parseQueryFilters(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|max:50',
            'q' => 'nullable|string|max:100',
        ], [
            'start_date.date' => 'Format tanggal mulai laporan tidak valid.',
            'end_date.date' => 'Format tanggal akhir laporan tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir laporan tidak boleh lebih awal dari tanggal mulai.',
        ]);

        if ($validator->fails()) {
            return [
                [
                    'start_date' => null,
                    'end_date' => null,
                    'status' => $request->query('status'),
                    'q' => $request->query('q'),
                ],
                $validator->errors()->all(),
            ];
        }

        return [$validator->validated(), []];
    }

    /**
     * @return array{start_date?: ?string, end_date?: ?string, status?: ?string, q?: ?string}
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|max:50',
            'q' => 'nullable|string|max:100',
        ], [
            'start_date.date' => 'Format tanggal mulai laporan tidak valid.',
            'end_date.date' => 'Format tanggal akhir laporan tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir laporan tidak boleh lebih awal dari tanggal mulai.',
        ]);
    }
}
