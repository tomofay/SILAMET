<?php

namespace App\Services;

use App\Enums\GateAction;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanDuration;
use App\Enums\LoanStatus;
use App\Enums\TicketStatus;
use App\Exports\ReportExport;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
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
     * @param  array{start_date?: ?string, end_date?: ?string, status?: ?string, q?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function buildReportPayload(string $type, array $filters = []): array
    {
        $normalized = $this->normalizeType($type);
        $parsed = $this->parseFilters($filters);

        $generatedAt = now();
        $printedBy = Auth::user()?->name ?? 'Administrator Lab SIJA';
        $printedByNip = Auth::user()?->nis_nip;

        $base = [
            'type' => $normalized,
            'view' => $normalized,
            'period_label' => $parsed['period_label'],
            'status_filter' => $parsed['status'],
            'search_query' => $parsed['q'],
            'generated_at' => $generatedAt,
            'printed_by' => $printedBy,
            'printed_by_nip' => $printedByNip,
            'filename' => sprintf('silamet_%s_%s', $normalized, $generatedAt->format('Ymd_His')),
        ];

        return match ($normalized) {
            'inventaris' => array_merge($base, $this->buildInventoryData($parsed['status'], $parsed['q'])),
            'loans' => array_merge($base, $this->buildLoansData($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q'])),
            'gate_logs' => array_merge($base, $this->buildGateLogsData($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q'])),
            'maintenance' => array_merge($base, $this->buildMaintenanceData($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q'])),
        };
    }

    /**
     * Susun data dasbor interaktif (agregat SQL riil + paginasi) agar laporan dapat ditinjau langsung di UI.
     *
     * @param  array{start_date?: ?string, end_date?: ?string, status?: ?string, q?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function buildDashboardData(string $type, array $filters = [], int $perPage = 15): array
    {
        $normalized = $this->normalizeType($type);
        $parsed = $this->parseFilters($filters);

        return match ($normalized) {
            'inventaris' => $this->buildInventoryDashboard($parsed, $perPage),
            'loans' => $this->buildLoansDashboard($parsed, $perPage),
            'gate_logs' => $this->buildGateLogsDashboard($parsed, $perPage),
            'maintenance' => $this->buildMaintenanceDashboard($parsed, $perPage),
        };
    }

    /**
     * @param  array{start_date?: ?string, end_date?: ?string, status?: ?string, q?: ?string}  $filters
     * @return array{start_date: ?Carbon, end_date: ?Carbon, status: ?string, q: ?string, period_label: string}
     */
    private function parseFilters(array $filters): array
    {
        $startDate = ! empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : null;
        $endDate = ! empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : null;
        $status = isset($filters['status']) && $filters['status'] !== ''
            ? (string) $filters['status']
            : null;
        $q = isset($filters['q']) && trim((string) $filters['q']) !== ''
            ? trim((string) $filters['q'])
            : null;

        $periodLabel = match (true) {
            $startDate !== null && $endDate !== null => $startDate->format('d/m/Y').' s.d. '.$endDate->format('d/m/Y'),
            $startDate !== null => 'Mulai '.$startDate->format('d/m/Y'),
            $endDate !== null => 'Sampai '.$endDate->format('d/m/Y'),
            default => 'Seluruh Periode Tercatat',
        };

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'q' => $q,
            'period_label' => $periodLabel,
        ];
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

        $path = 'exports/'.$filename.'.pdf';
        $pdf->store($path, 'public');

        return $path;
    }

    /**
     * Download PDF sebagai HTTP response.
     */
    public function downloadPdf(array $data, string $filename): Response
    {
        $pdf = $this->makePdfInstance($data);

        return $pdf->download($filename.'.pdf');
    }

    /**
     * Export laporan ke file Excel (.xlsx).
     *
     * @param  array{start_date?: ?string, end_date?: ?string, status?: ?string}  $filters
     */
    public function exportExcel(string $type, array $filters = []): BinaryFileResponse
    {
        $payload = $this->buildReportPayload($type, $filters);

        return Excel::download(new ReportExport($payload), $payload['filename'].'.xlsx');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makePdfInstance(array $data): \Barryvdh\DomPDF\PDF
    {
        $orientation = $data['orientation'] ?? 'landscape';

        return Pdf::loadView('reports.pdf.'.$data['view'], $data)
            ->setPaper('a4', $orientation)
            ->setOption('isRemoteEnabled', false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Item>
     */
    private function inventoryBaseQuery(?string $status, ?string $q = null)
    {
        $query = Item::query();

        if ($status !== null && in_array($status, ['tersedia', 'dipinjam', 'under_maintenance'], true)) {
            $query->where('status', $status);
        }

        if ($q !== null) {
            $term = '%'.$q.'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('item_code', 'like', $term)
                    ->orWhere('barcode_token', 'like', $term)
                    ->orWhere('epc_tag', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Loan>
     */
    private function loansBaseQuery(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null)
    {
        $query = Loan::query();

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

        if ($q !== null) {
            $term = '%'.$q.'%';
            $query->where(function ($sub) use ($term) {
                $sub->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', $term)
                        ->orWhere('nis_nip', 'like', $term);
                })->orWhereHas('item', function ($iq) use ($term) {
                    $iq->withTrashed()
                        ->where('name', 'like', $term)
                        ->orWhere('item_code', 'like', $term);
                });
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<GateLog>
     */
    private function gateLogsBaseQuery(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null)
    {
        $query = GateLog::query();

        if ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('created_at', '<=', $endDate);
        }
        if ($status !== null && in_array($status, ['allowed', 'denied'], true)) {
            $query->where('action_status', $status);
        }

        if ($q !== null) {
            $term = '%'.$q.'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('epc_tag_scanned', 'like', $term)
                    ->orWhereHas('item', function ($iq) use ($term) {
                        $iq->withTrashed()
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term)
                            ->orWhere('location', 'like', $term);
                    });
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MaintenanceTicket>
     */
    private function maintenanceBaseQuery(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null)
    {
        $query = MaintenanceTicket::query();

        if ($startDate !== null) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate !== null) {
            $query->where('created_at', '<=', $endDate);
        }
        if ($status !== null && in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $query->where('status', $status);
        }

        if ($q !== null) {
            $term = '%'.$q.'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('issue_description', 'like', $term)
                    ->orWhereHas('item', function ($iq) use ($term) {
                        $iq->withTrashed()
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term);
                    })
                    ->orWhereHas('reportedBy', function ($uq) use ($term) {
                        $uq->where('name', 'like', $term);
                    })
                    ->orWhereHas('logs', function ($lq) use ($term) {
                        $lq->where('action_taken', 'like', $term)
                            ->orWhere('vendor_name', 'like', $term)
                            ->orWhere('spareparts_used', 'like', $term);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{start_date: ?Carbon, end_date: ?Carbon, status: ?string, q: ?string, period_label: string}  $parsed
     * @return array<string, mixed>
     */
    private function buildInventoryDashboard(array $parsed, int $perPage): array
    {
        $baseQuery = $this->inventoryBaseQuery($parsed['status'], $parsed['q']);

        $total = (int) (clone $baseQuery)->count();
        $aset = (int) (clone $baseQuery)->where('type', ItemType::ASET)->count();
        $bhp = (int) (clone $baseQuery)->where('type', ItemType::BAHAN_HABIS_PAKAI)->count();
        $tersedia = (int) (clone $baseQuery)->where('status', ItemStatus::TERSEDIA)->count();
        $dipinjam = (int) (clone $baseQuery)->where('status', ItemStatus::DIPINJAM)->count();
        $maintenance = (int) (clone $baseQuery)->where('status', ItemStatus::UNDER_MAINTENANCE)->count();
        $totalStock = (int) (clone $baseQuery)->sum('stock');

        $locationRaw = (clone $baseQuery)
            ->select('location')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('location')
            ->orderByDesc('aggregate')
            ->limit(15)
            ->get();

        $mergedLocations = [];
        foreach ($locationRaw as $row) {
            $label = trim((string) $row->location);
            $label = $label !== '' ? $label : 'Belum Ditentukan';
            $mergedLocations[$label] = ($mergedLocations[$label] ?? 0) + (int) $row->aggregate;
        }
        arsort($mergedLocations);

        $breakdown = [];
        foreach (array_slice($mergedLocations, 0, 5, true) as $label => $count) {
            $breakdown[] = [
                'label' => $label,
                'sublabel' => null,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $paginator = (clone $baseQuery)
            ->orderBy('item_code')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'type' => 'inventaris',
            'title' => 'Laporan Inventarisasi Aset & Bahan Lab',
            'uses_dates' => false,
            'period_label' => $parsed['period_label'],
            'status_filter' => $parsed['status'],
            'search_query' => $parsed['q'],
            'summary' => [
                'total' => $total,
                'aset' => $aset,
                'bhp' => $bhp,
                'tersedia' => $tersedia,
                'dipinjam' => $dipinjam,
                'maintenance' => $maintenance,
                'total_stock' => $totalStock,
            ],
            'breakdown_title' => 'Sebaran Item per Lokasi / Rak Teratas',
            'breakdown' => $breakdown,
            'paginator' => $paginator,
        ];
    }

    /**
     * @param  array{start_date: ?Carbon, end_date: ?Carbon, status: ?string, q: ?string, period_label: string}  $parsed
     * @return array<string, mixed>
     */
    private function buildLoansDashboard(array $parsed, int $perPage): array
    {
        $baseQuery = $this->loansBaseQuery($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q']);

        $total = (int) (clone $baseQuery)->count();
        $aktif = (int) (clone $baseQuery)->where('status', LoanStatus::AKTIF)->count();
        $overdue = (int) (clone $baseQuery)
            ->where('status', LoanStatus::AKTIF)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $menungguApproval = (int) (clone $baseQuery)->where('status', LoanStatus::MENUNGGU_APPROVAL_KEMBALI)->count();
        $selesai = (int) (clone $baseQuery)->where('status', LoanStatus::SELESAI)->count();
        $rusak = (int) (clone $baseQuery)->where('condition_in', 'rusak')->count();

        $durationRaw = (clone $baseQuery)
            ->select('usage_duration')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('usage_duration')
            ->orderByDesc('aggregate')
            ->get();

        $breakdown = [];
        foreach ($durationRaw as $row) {
            $durEnum = $row->usage_duration instanceof LoanDuration
                ? $row->usage_duration
                : LoanDuration::tryFrom((string) $row->usage_duration);
            $count = (int) $row->aggregate;
            $breakdown[] = [
                'label' => $durEnum?->label() ?? 'Tidak Tercatat',
                'sublabel' => null,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $paginator = (clone $baseQuery)
            ->with(['user', 'item' => fn ($q) => $q->withTrashed()])
            ->latest('borrowed_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'type' => 'loans',
            'title' => 'Rekapitulasi Peminjaman Siswa',
            'uses_dates' => true,
            'period_label' => $parsed['period_label'],
            'status_filter' => $parsed['status'],
            'search_query' => $parsed['q'],
            'summary' => [
                'total' => $total,
                'aktif' => $aktif,
                'overdue' => $overdue,
                'aktif_normal' => max(0, $aktif - $overdue),
                'menunggu_approval' => $menungguApproval,
                'selesai' => $selesai,
                'rusak' => $rusak,
            ],
            'breakdown_title' => 'Distribusi Durasi Peminjaman Alat',
            'breakdown' => $breakdown,
            'paginator' => $paginator,
        ];
    }

    /**
     * @param  array{start_date: ?Carbon, end_date: ?Carbon, status: ?string, q: ?string, period_label: string}  $parsed
     * @return array<string, mixed>
     */
    private function buildGateLogsDashboard(array $parsed, int $perPage): array
    {
        $baseQuery = $this->gateLogsBaseQuery($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q']);

        $total = (int) (clone $baseQuery)->count();
        $allowed = (int) (clone $baseQuery)->where('action_status', GateAction::ALLOWED)->count();
        $denied = (int) (clone $baseQuery)->where('action_status', GateAction::DENIED)->count();
        $withPhoto = (int) (clone $baseQuery)->whereNotNull('capture_image_path')->count();
        $complianceRate = $total > 0 ? round(($allowed / $total) * 100, 1) : null;

        $topTagsRaw = (clone $baseQuery)
            ->select('epc_tag_scanned')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('epc_tag_scanned')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        $matchedItems = Item::withTrashed()
            ->whereIn('epc_tag', $topTagsRaw->pluck('epc_tag_scanned')->filter()->all())
            ->get()
            ->keyBy('epc_tag');

        $breakdown = [];
        foreach ($topTagsRaw as $row) {
            $epc = (string) $row->epc_tag_scanned;
            $matched = $matchedItems->get($epc);
            $count = (int) $row->aggregate;
            $breakdown[] = [
                'label' => $matched ? $matched->name : 'Tag Tidak Terdaftar',
                'sublabel' => $matched ? trim(($matched->item_code ?? '-').' · '.$epc) : $epc,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $paginator = (clone $baseQuery)
            ->with(['item'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'type' => 'gate_logs',
            'title' => 'Log Keamanan Gate Security UHF RFID',
            'uses_dates' => true,
            'period_label' => $parsed['period_label'],
            'status_filter' => $parsed['status'],
            'search_query' => $parsed['q'],
            'summary' => [
                'total' => $total,
                'allowed' => $allowed,
                'denied' => $denied,
                'with_photo' => $withPhoto,
                'compliance_rate' => $complianceRate,
            ],
            'breakdown_title' => 'Top 5 Barang / Tag Paling Sering Terdeteksi Gerbang',
            'breakdown' => $breakdown,
            'paginator' => $paginator,
        ];
    }

    /**
     * @param  array{start_date: ?Carbon, end_date: ?Carbon, status: ?string, q: ?string, period_label: string}  $parsed
     * @return array<string, mixed>
     */
    private function buildMaintenanceDashboard(array $parsed, int $perPage): array
    {
        $baseQuery = $this->maintenanceBaseQuery($parsed['start_date'], $parsed['end_date'], $parsed['status'], $parsed['q']);

        $total = (int) (clone $baseQuery)->count();
        $open = (int) (clone $baseQuery)->where('status', TicketStatus::OPEN)->count();
        $inProgress = (int) (clone $baseQuery)->where('status', TicketStatus::IN_PROGRESS)->count();
        $resolved = (int) (clone $baseQuery)->where('status', TicketStatus::RESOLVED)->count();

        $ticketIdSubquery = (clone $baseQuery)->select('maintenance_tickets.id');
        $logQuery = MaintenanceLog::query()->whereIn('ticket_id', $ticketIdSubquery);
        $totalCost = (float) (clone $logQuery)->sum('cost');
        $internalCost = (float) (clone $logQuery)->where('service_type', 'internal')->sum('cost');
        $vendorCost = (float) (clone $logQuery)->where('service_type', 'vendor_luar')->sum('cost');
        $receiptCount = (int) (clone $logQuery)->whereNotNull('receipt_path')->count();

        $topItemsRaw = (clone $baseQuery)
            ->select('item_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('item_id')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        $itemsMap = Item::withTrashed()
            ->whereIn('id', $topItemsRaw->pluck('item_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $breakdown = [];
        foreach ($topItemsRaw as $row) {
            $item = $itemsMap->get($row->item_id);
            $count = (int) $row->aggregate;
            $breakdown[] = [
                'label' => $item?->name ?? 'Aset Tidak Dikenal',
                'sublabel' => $item?->item_code ?? '-',
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $paginator = (clone $baseQuery)
            ->with([
                'item' => fn ($q) => $q->withTrashed(),
                'reportedBy',
                'logs.handledBy',
                'latestLog',
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'type' => 'maintenance',
            'title' => 'Laporan Pemeliharaan & Biaya (M&R)',
            'uses_dates' => true,
            'period_label' => $parsed['period_label'],
            'status_filter' => $parsed['status'],
            'search_query' => $parsed['q'],
            'summary' => [
                'total' => $total,
                'open' => $open,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'total_cost' => $totalCost,
                'internal_cost' => $internalCost,
                'vendor_cost' => $vendorCost,
                'receipt_count' => $receiptCount,
            ],
            'breakdown_title' => 'Top 5 Aset dengan Tiket Perbaikan Terbanyak',
            'breakdown' => $breakdown,
            'paginator' => $paginator,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInventoryData(?string $status, ?string $q = null): array
    {
        $items = $this->inventoryBaseQuery($status, $q)
            ->orderBy('item_code')
            ->get();

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
    private function buildLoansData(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null): array
    {
        $loans = $this->loansBaseQuery($startDate, $endDate, $status, $q)
            ->with(['user', 'item' => fn ($iq) => $iq->withTrashed()])
            ->latest('borrowed_at')
            ->latest('id')
            ->get();

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
    private function buildGateLogsData(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null): array
    {
        $logs = $this->gateLogsBaseQuery($startDate, $endDate, $status, $q)
            ->with(['item'])
            ->latest('created_at')
            ->latest('id')
            ->get();

        $rows = [];
        foreach ($logs as $idx => $log) {
            $itemName = $log->item
                ? ($log->item->trashed() ? $log->item->name.' (Dihapus)' : $log->item->name)
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
    private function buildMaintenanceData(?Carbon $startDate, ?Carbon $endDate, ?string $status, ?string $q = null): array
    {
        $tickets = $this->maintenanceBaseQuery($startDate, $endDate, $status, $q)
            ->with([
                'item' => fn ($iq) => $iq->withTrashed(),
                'reportedBy',
                'logs.handledBy',
            ])
            ->latest('created_at')
            ->latest('id')
            ->get();

        $totalCost = 0.0;
        $rows = [];
        foreach ($tickets as $idx => $ticket) {
            $ticketCost = (float) $ticket->logs->sum('cost');
            $totalCost += $ticketCost;

            $actions = $ticket->logs
                ->map(function ($l) {
                    $label = $l->service_type?->label() ?? '-';
                    if ($l->service_type?->value === 'vendor_luar' && filled($l->vendor_name)) {
                        $label .= ': '.$l->vendor_name;
                    }
                    $parts = [$l->action_taken];
                    if (filled($l->spareparts_used)) {
                        $parts[] = 'Sparepart: '.$l->spareparts_used;
                    }

                    return sprintf('[%s] %s', $label, implode(' | ', $parts));
                })
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
