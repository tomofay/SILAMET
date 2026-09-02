<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\TicketStatus;
use App\Models\GateLog;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MaintenanceTicket;

class DashboardController extends Controller
{
    /**
     * Tampilkan dasbor statistik utama.
     */
    public function index()
    {
        $stats = [
            'total_aset' => Item::query()->count(),
            'dipinjam' => Item::query()->where('status', ItemStatus::DIPINJAM)->count(),
            'under_maintenance' => Item::query()->where('status', ItemStatus::UNDER_MAINTENANCE)->count(),
            'gate_insiden_hari_ini' => GateLog::query()
                ->whereDate('created_at', today())
                ->where('action_status', 'denied')
                ->count(),
        ];

        // Daftar peminjaman overdue (aktif & lewat due_at)
        $overdueLoans = Loan::query()
            ->with(['user', 'item'])
            ->where('status', 'aktif')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        // Tiket M&R yang masih berjalan
        $openTickets = MaintenanceTicket::query()
            ->with(['item'])
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', [
            'title' => 'Dashboard',
            'heading' => 'Ringkasan Laboratorium',
            'stats' => $stats,
            'overdueLoans' => $overdueLoans,
            'openTickets' => $openTickets,
        ]);
    }
}
