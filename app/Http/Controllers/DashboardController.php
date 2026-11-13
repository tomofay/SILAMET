<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\LoanStatus;
use App\Enums\TicketStatus;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;

class DashboardController extends Controller
{
    /**
     * Tampilkan dasbor operasional dan manajemen inventaris laboratorium.
     */
    public function index()
    {
        $today = today();

        // 1. Metrik Inventaris & Kesiapan Alat
        $totalAset = Item::query()->count();
        $totalUnits = (int) Item::query()->sum('stock');
        $dipinjamCount = Item::query()->where('status', ItemStatus::DIPINJAM)->count();
        $underMaintenanceCount = Item::query()->where('status', ItemStatus::UNDER_MAINTENANCE)->count();
        $tersediaCount = Item::query()->where('status', ItemStatus::TERSEDIA)->count();
        $readinessPercent = $totalAset > 0 ? (int) round(($tersediaCount / $totalAset) * 100) : 100;

        // 2. Metrik Sirkulasi & Peminjaman Siswa
        $overdueLoansQuery = Loan::query()
            ->with(['user', 'item'])
            ->where('status', LoanStatus::AKTIF)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderBy('due_at');

        $overdueCount = (clone $overdueLoansQuery)->count();
        $overdueLoans = (clone $overdueLoansQuery)->limit(8)->get();

        $activeLoans = Loan::query()
            ->with(['user', 'item'])
            ->where('status', LoanStatus::AKTIF)
            ->latest('borrowed_at')
            ->limit(8)
            ->get();

        $todayReturnedLoans = Loan::query()
            ->with(['user', 'item'])
            ->where('status', LoanStatus::SELESAI)
            ->whereDate('returned_at', $today)
            ->latest('returned_at')
            ->limit(8)
            ->get();

        $todayLoansCount = Loan::query()->whereDate('borrowed_at', $today)->count();
        $todayReturnsCount = Loan::query()->whereDate('returned_at', $today)->count();

        // 3. Metrik Pemeliharaan & Tiket Perbaikan (M&R)
        $openTickets = MaintenanceTicket::query()
            ->with(['item', 'reportedBy', 'logs'])
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])
            ->latest()
            ->limit(8)
            ->get();

        $openTicketsCount = MaintenanceTicket::query()->where('status', TicketStatus::OPEN)->count();
        $inProgressTicketsCount = MaintenanceTicket::query()->where('status', TicketStatus::IN_PROGRESS)->count();
        $preventiveDueSoonCount = PreventiveSchedule::query()
            ->where('workflow_stage', 'scheduled')
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<=', now()->addDays(7))
            ->count();

        // 4. Metrik Keamanan Gerbang UHF RFID (Gate Security)
        $gateTotalHariIni = GateLog::query()->whereDate('created_at', $today)->count();
        $gateInsidenHariIni = GateLog::query()
            ->whereDate('created_at', $today)
            ->where('action_status', 'denied')
            ->count();
        $gateAllowedHariIni = max(0, $gateTotalHariIni - $gateInsidenHariIni);
        $gateComplianceRate = $gateTotalHariIni > 0
            ? round(($gateAllowedHariIni / $gateTotalHariIni) * 100, 1)
            : 100.0;

        $recentGateLogs = GateLog::query()
            ->with(['item'])
            ->latest('id')
            ->limit(5)
            ->get();

        // 5. Radar Stok Kritis (Bahan Habis Pakai <= 5 unit)
        $lowStockConsumables = Item::query()
            ->where('type', ItemType::BAHAN_HABIS_PAKAI)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(5)
            ->get();

        $stats = [
            'total_aset' => $totalAset,
            'total_units' => $totalUnits,
            'dipinjam' => $dipinjamCount,
            'under_maintenance' => $underMaintenanceCount,
            'tersedia' => $tersediaCount,
            'readiness_percent' => $readinessPercent,
            'overdue_count' => $overdueCount,
            'today_loans_count' => $todayLoansCount,
            'today_returns_count' => $todayReturnsCount,
            'open_tickets_count' => $openTicketsCount,
            'in_progress_tickets_count' => $inProgressTicketsCount,
            'preventive_due_soon_count' => $preventiveDueSoonCount,
            'gate_total_hari_ini' => $gateTotalHariIni,
            'gate_insiden_hari_ini' => $gateInsidenHariIni,
            'gate_compliance_rate' => $gateComplianceRate,
        ];

        return view('dashboard', [
            'title' => 'Dashboard Utama',
            'heading' => 'Dashboard Utama',
            'stats' => $stats,
            'overdueLoans' => $overdueLoans,
            'activeLoans' => $activeLoans,
            'todayReturnedLoans' => $todayReturnedLoans,
            'openTickets' => $openTickets,
            'recentGateLogs' => $recentGateLogs,
            'lowStockConsumables' => $lowStockConsumables,
        ]);
    }
}
