<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportGenerationService
{
    /**
     * Generate PDF report dari data.
     */
    public function generatePdf(array $data, string $filename): string
    {
        $pdf = Pdf::loadView('reports.pdf.' . $data['view'], $data);
        
        $path = 'exports/' . $filename . '.pdf';
        $pdf->store($path, 'public');
        
        return $path;
    }

    /**
     * Download PDF sebagai response.
     */
    public function downloadPdf(array $data, string $filename): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('reports.pdf.' . $data['view'], $data);
        
        return $pdf->download($filename . '.pdf');
    }

    /**
     * Export ke Excel.
     */
    public function exportExcel(string $type): \Illuminate\Http\Response
    {
        return Excel::download(new ReportExport($type), $type . '_export.xlsx');
    }
}
