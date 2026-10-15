<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceLog;
use App\Models\Item;
use App\Enums\ItemStatus;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * Tampilkan daftar semua tickets M&R dengan filter status.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        if (! in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $status = 'open';
        }

        $tickets = MaintenanceTicket::with(['item', 'reportedBy', 'logs'])
            ->where('status', $status)
            ->latest()
            ->paginate(15);

        // Hitungan global per status untuk badge tab
        $counts = [
            'open' => MaintenanceTicket::where('status', 'open')->count(),
            'in_progress' => MaintenanceTicket::where('status', 'in_progress')->count(),
            'resolved' => MaintenanceTicket::where('status', 'resolved')->count(),
        ];

        return view('maintenance.index', compact('tickets', 'counts'));
    }

    /**
     * Tampilkan form create ticket baru.
     */
    public function create()
    {
        return view('maintenance.create');
    }

    /**
     * Simpan ticket baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'reported_by' => 'nullable|exists:users,id',
            'issue_description' => 'required|string',
            'status' => 'required|in:open,in_progress,resolved',
        ]);

        MaintenanceTicket::create($validated);

        // Update status item jika belum under_maintenance
        $item = \App\Models\Item::find($validated['item_id']);
        if ($item && $item->status !== 'under_maintenance') {
            $item->update(['status' => 'under_maintenance']);
        }

        return redirect()->route('maintenance.index')
            ->with('success', 'Tiket kerusakan berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail ticket.
     */
    public function show(string $id)
    {
        $ticket = MaintenanceTicket::with(['item', 'reportedBy', 'logs.handledBy'])->findOrFail($id);
        return view('maintenance.show', compact('ticket'));
    }

    /**
     * Tampilkan form edit ticket.
     */
    public function edit(string $id)
    {
        $ticket = MaintenanceTicket::findOrFail($id);
        return view('maintenance.edit', compact('ticket'));
    }

    /**
     * Update ticket di database.
     */
    public function update(Request $request, string $id)
    {
        $ticket = MaintenanceTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
            'issue_description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            $ticket->update($validated);

            // Jika status tiket berubah menjadi resolved, kembalikan status barang menjadi 'tersedia'
            if ($validated['status'] === 'resolved' || $validated['status'] === TicketStatus::RESOLVED->value) {
                if ($ticket->item_id) {
                    Item::where('id', $ticket->item_id)->update([
                        'status' => ItemStatus::TERSEDIA
                    ]);
                }
            }
        });

        $message = $validated['status'] === 'resolved'
            ? 'Tiket telah diselesaikan (Resolved). Barang sekarang berstatus TERSEDIA kembali.'
            : 'Status tiket diperbarui.';

        return redirect()->route('maintenance.index')
            ->with('success', $message);
    }

    /**
     * Hapus ticket (soft delete).
     */
    public function destroy(string $id)
    {
        $ticket = MaintenanceTicket::findOrFail($id);
        $ticket->delete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Tiket berhasil dihapus.');
    }
}
