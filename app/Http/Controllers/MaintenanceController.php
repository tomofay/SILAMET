<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Tampilkan daftar semua tickets M&R.
     */
    public function index()
    {
        $tickets = MaintenanceTicket::with(['item', 'reportedBy', 'logs'])
            ->latest()
            ->paginate(15);
        return view('maintenance.index', compact('tickets'));
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

        $ticket->update($validated);

        return redirect()->route('maintenance.index')
            ->with('success', 'Status tiket diperbarui.');
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
