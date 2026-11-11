<?php

namespace App\Services;

use App\Enums\GateAction;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanStatus;
use App\Enums\TicketStatus;
use App\Exports\ReportExport;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportGenerationService
{
    public const SUPPORTED_TYPES = [
        'inventaris',
        'inventory',
        'loans',
        'gate_logs',
        'maintenance',
    ];

    /**
     * Susun dataset laporan riil dari database sesuai tipe dan filter tanggal/status.
     *
     * @param array{start_date?: ?string, end_date?: ?string, status?: ?string} $filters
     * @return array<string, mixed>
     */
    public function buildReportPayload(string $type, array $filters = []): array
    {
        $normalized = $this->normalizeType($type);

        $startDate = ! empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : null;
        $endDate = ! empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : null;
        $status = isset($filters['status']) && $filters['status'] !== ''
            ? (string) $filters['status']
            : null;

        $periodLabel = match (true) {
            $startDate !== null && $endDate !== null => $startDate->format('d/m/Y') . ' s.d. ' . $endDate->format('d/m/Y'),
            $startDate !== null => 'Mulai ' . $startDate->format('d/m/Y'),
            $endDate !== null => 'Sampai ' . $endDate->format('d/m/Y'),
            default => 'Seluruh Periode Tercatat',
        };

        $generatedAt = now();
        $printedBy = Auth::user()?->name ?? 'Administrator Lab SIJA';
        $printedByNip = Auth::user()?->nis_nip;

        $base = [
            'type' => $normalized,
            'view' => $normalized,
            'period_label' => $periodLabel,
            'status_filter' => $status,
            'generated_at' => $generatedAt,
            'printed_by' => $printedBy,
            'printed_by_nip' => $printedByNip,
            'filename' => sprintf('silamet_%s_%s', $normalized, $generatedAt->format('Ymd_His')),
        ];

        return match ($normalized) {
            'inventaris' => array_merge($base, $this->buildInventoryData($status)),
            'loans' => array_merge($base, $this->buildLoansData($startDate, $endDate, $status)),
            'gate_logs' => array_merge($base, $this->buildGateLogsData($startDate, $endDate, $status)),
            'maintenance' => array_merge($base, $this->buildMaintenanceData($startDate, $endDate, $status)),
        };
    }

    public function normalizeType(string $type): string
    {
        $clean = strtolower(trim($type));

        if (! in_array($clean, self::SUPPORTED_TYPES, true)) {
            abort(404, 'Jenis laporan tidak ditemukan.');
        }

        return $clean === 'inventory' ? 'inventaris' : $clean;
    }

    /**
     * Simpan PDF report ke storage publik.
     */
    public function generatePdf(array $data, string $filename): string
    {
        $pdf = $this->makePdfInstance($data);

        $path = 'exports/' . $filename . '.pdf';
        $pdf->store($path, 'public');

        return $path;
    }

    /**
     * Download PDF sebagai HTTP response.
     */
    public function downloadPdf(array $data, string $filename): Response
    {
        $pdf = $this->makePdfInstance($data);

        return $pdf->download($filename . '.pdf');
    }

    /**
     * Export laporan ke file Excel (.xlsx).
     *
     * @param array{start_date?: ?string, end_date?: ?string, status?: ?string} $filters
     */
    public function exportExcel(string $type, array $filters = []): BinaryFileResponse
    {
        $payload = $this->buildReportPayload($type, $filters);

        return Excel::download(new ReportExport($payload), $payload['filename'] . '.xlsx');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function makePdfInstance(array $data): \Barryvdh\DomPDF\PDF
    {
        $orientation = $data['orientation'] ?? 'landscape';

        return Pdf::loadView('reports.pdf.' . $data['view'], $data)
            ->setPaper('a4', $orientation)
            ->setOption('isRemoteEnabled', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInventoryData(?string $status): array
    {
        $query = Item::query()->orderBy('item_code');

        if ($status !== null && in_array($status, ['tersedia', 'dipinjam', 'under_maintenance'], true)) {
            $query->where('status', $status);
        }

        $items = $query->get();

        $rows = [];
        foreach ($items as $idx => $item) {
            $rows[] = [
                $idx + 1,
                $item->item_code,
                $item->barcode_token ?? '-',
                $item->epc_tag ?? '-',
                $item->name,
                $item->type?->label() ?? '-',
                (int) $item->stock,
                $item->location ?? '-',
                $item->status?->label() ?? '-',
            ];
        }

        return [
            'title' => 'LAPORAN INVENTARISASI ASET & BAHAN LABORATORIUM',
            'sheet_title' => 'Inventaris Lab SIJA',
            'orientation' => 'landscape',
            'items' => $items,
            'summary' => [
                'total' => $items->count(),
                'aset' => $items->where('type', ItemType::ASET)->count(),
                'bhp' => $items->where('type', ItemType::BAHAN_HABIS_PAKAI)->count(),
                'tersedia' => $items->where('status', ItemStatus::TERSEDIA)->count(),
                'dipinjam' => $items->where('status', ItemStatus::DIPINJAM)->count(),
                'maintenance' => $items->where('status', ItemStatus::UNDER_MAINTENANCE)->count(),
            ],
            'headings' => [
                'No',
                'Kode Aset',
                'Token Barcode',
                'EPC Tag UHF RFID',
                'Nama Barang',
                'Tipe',
                'Stok',
                'Lokasi / Rak',
                'Status',
            ],
            'rows' => $rows,
            'column_formats' => [
                'B' => NumberFormat::FORMAT_TEXT,
                'C' => NumberFormat::FORMAT_TEXT,
                'D' => NumberFormat::FORMAT_TEXT,
            ],
            'center_columns' => ['A', 'B', 'C', 'F', 'G', 'H', 'I'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLoansData(?Carbon $startDate, ?Carbon $endDate, ?string $status): array
    {
        $query = Loan::with(['user', 'item' => fn ($q) => $q->withTrashed()])
            ->latest('borrowed_at')
            ->latest('id');

        if ($startDate !== null) {
            $query->where('borrowed_at', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('borrowed_at', '<=', $endDate);
        }
        if ($status === 'overdue') {
            $query->where('status', LoanStatus::AKTIF)->where('due_at', '<', now());
        } elseif ($status !== null && in_array($status, ['aktif', 'menunggu_approval_kembali', 'selesai'], true)) {
            $query->where('status', $status);
        }

        $loans = $query->get();

        $rows = [];
        foreach ($loans as $idx => $loan) {
            $condLabel = match ($loan->condition_in) {
                'bagus' => 'Bagus',
                'rusak' => 'Rusak',
                default => '-',
            };

            $rows[] = [
                $idx + 1,
                $loan->user?->nis_nip ?? '-',
                $loan->user?->name ?? '-',
                $loan->item?->item_code ?? '-',
                $loan->item?->name ?? '-',
                $loan->usage_duration?->label() ?? '-',
                optional($loan->borrowed_at)->format('d/m/Y H:i') ?? '-',
                optional($loan->due_at)->format('d/m/Y H:i') ?? '-',
                optional($loan->returned_at)->format('d/m/Y H:i') ?? '-',
                $condLabel,
                $loan->status?->label() ?? '-',
            ];
        }

        return [
            'title' => 'LAPORAN REKAPITULASI PEMINJAMAN ALAT LABORATORIUM',
            'sheet_title' => 'Peminjaman Alat',
            'orientation' => 'landscape',
            'loans' => $loans,
            'summary' => [
                'total' => $loans->count(),
                'aktif' => $loans->where('status', LoanStatus::AKTIF)->count(),
                'overdue' => $loans->filter(fn (Loan $l) => $l->isOverdue())->count(),
                'selesai' => $loans->where('status', LoanStatus::SELESAI)->count(),
            ],
            'headings' => [
                'No',
                'NIS / NIP',
                'Nama Peminjam',
                'Kode Barang',
                'Nama Barang',
                'Durasi',
                'Waktu Pinjam',
                'Jatuh Tempo',
                'Waktu Kembali',
                'Kondisi Balik',
                'Status',
            ],
            'rows' => $rows,
            'column_formats' => [
                'B' => NumberFormat::FORMAT_TEXT,
                'D' => NumberFormat::FORMAT_TEXT,
            ],
            'center_columns' => ['A', 'B', 'D', 'F', 'G', 'H', 'I', 'J', 'K'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGateLogsData(?Carbon $startDate, ?Carbon $endDate, ?string $status): array
    {
        $query = GateLog::with(['item'])
            ->latest('created_at')
            ->latest('id');

        if ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('created_at', '<=', $endDate);
        }
        if ($status !== null && in_array($status, ['allowed', 'denied'], true)) {
            $query->where('action_status', $status);
        }

        $logs = $query->get();

        $rows = [];
        foreach ($logs as $idx => $log) {
            $itemName = $log->item
                ? ($log->item->trashed() ? $log->item->name . ' (Dihapus)' : $log->item->name)
                : 'Tag Asing / Tidak Terdaftar';

            $rows[] = [
                $idx + 1,
                optional($log->created_at)->format('d/m/Y H:i:s') ?? '-',
                $log->epc_tag_scanned,
                $log->item?->item_code ?? '-',
                $itemName,
                $log->item?->location ?? '-',
                strtoupper($log->action_status?->value ?? '-'),
                $log->capture_image_path ? 'Tersedia' : '-',
            ];
        }

        return [
            'title' => 'LAPORAN LOG KEAMANAN GATE SECURITY UHF RFID',
            'sheet_title' => 'Gate Logs RFID',
            'orientation' => 'landscape',
            'logs' => $logs,
            'summary' => [
                'total' => $logs->count(),
                'allowed' => $logs->where('action_status', GateAction::ALLOWED)->count(),
                'denied' => $logs->where('action_status', GateAction::DENIED)->count(),
            ],
            'headings' => [
                'No',
                'Waktu Deteksi',
                'EPC Tag Terbaca',
                'Kode Barang',
                'Nama Barang',
                'Lokasi Rak',
                'Status Gerbang',
                'Bukti Foto',
            ],
            'rows' => $rows,
            'column_formats' => [
                'C' => NumberFormat::FORMAT_TEXT,
                'D' => NumberFormat::FORMAT_TEXT,
            ],
            'center_columns' => ['A', 'B', 'D', 'F', 'G', 'H'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMaintenanceData(?Carbon $startDate, ?Carbon $endDate, ?string $status): array
    {
        $query = MaintenanceTicket::with([
            'item' => fn ($q) => $q->withTrashed(),
            'reportedBy',
            'logs.handledBy',
        ])
            ->latest('created_at')
            ->latest('id');

        if ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('created_at', '<=', $endDate);
        }
        if ($status !== null && in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $query->where('status', $status);
        }

        $tickets = $query->get();

        $totalCost = 0.0;
        $rows = [];
        foreach ($tickets as $idx => $ticket) {
            $ticketCost = (float) $ticket->logs->sum('cost');
            $totalCost += $ticketCost;

            $actions = $ticket->logs
                ->map(fn ($l) => sprintf('[%s] %s', $l->service_type?->label() ?? '-', $l->action_taken))
                ->implode('; ');

            $technicians = $ticket->logs
                ->map(fn ($l) => $l->handledBy?->name)
                ->filter()
                ->unique()
                ->implode(', ');

            $rows[] = [
                $idx + 1,
                optional($ticket->created_at)->format('d/m/Y H:i') ?? '-',
                $ticket->item?->item_code ?? '-',
                $ticket->item?->name ?? '-',
                $ticket->reportedBy?->name ?? '-',
                $ticket->issue_description,
                $actions !== '' ? $actions : '-',
                $technicians !== '' ? $technicians : '-',
                $ticketCost,
                $ticket->status?->label() ?? '-',
            ];
        }

        return [
            'title' => 'LAPORAN PEMELIHARAAN, PERBAIKAN & BIAYA (M&R)',
            'sheet_title' => 'Maintenance & Biaya',
            'orientation' => 'landscape',
            'tickets' => $tickets,
            'summary' => [
                'total' => $tickets->count(),
                'open' => $tickets->where('status', TicketStatus::OPEN)->count(),
                'in_progress' => $tickets->where('status', TicketStatus::IN_PROGRESS)->count(),
                'resolved' => $tickets->where('status', TicketStatus::RESOLVED)->count(),
                'total_cost' => $totalCost,
            ],
            'headings' => [
                'No',
                'Tanggal Lapor',
                'Kode Barang',
                'Nama Barang',
                'Pelapor',
                'Deskripsi Kendala',
                'Tindakan Perbaikan',
                'Teknisi',
                'Biaya (Rp)',
                'Status Tiket',
            ],
            'rows' => $rows,
            'column_formats' => [
                'C' => NumberFormat::FORMAT_TEXT,
                'I' => '#,##0',
            ],
            'center_columns' => ['A', 'B', 'C', 'J'],
            'right_columns' => ['I'],
        ];
    }
}
