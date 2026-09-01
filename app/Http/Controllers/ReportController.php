<?php

namespace App\Http\Controllers;

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
        return view('reports.index');
    }

    /**
     * Download PDF report berdasarkan type.
     */
    public function downloadPdf(string $type)
    {
        // TODO: Implementasi logic sesuai type yang diminta
        return response()->json([
            'success' => false,
            'message' => 'Laporan belum tersedia.',
        ]);
    }

    /**
     * Download Excel report berdasarkan type.
     */
    public function downloadExcel(string $type)
    {
        // TODO: Implementasi logic sesuai type yang diminta
        return response()->json([
            'success' => false,
            'message' => 'Laporan belum tersedia.',
        ]);
    }
}
