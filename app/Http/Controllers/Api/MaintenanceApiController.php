<?php

namespace App\Http\Controllers\Api;

use App\Enums\ItemStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceApiController extends Controller
{
    /**
     * Daftar semua tiket (dengan status dan relasi dasar).
     */
    public function index()
    {
        $tickets = MaintenanceTicket::query()
            ->with(['item', 'reportedBy:id,name', 'logs.handledBy:id,name'])
            ->latest()
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    /**
     * Detail tiket lengkap.
     */
    public function show(int $id)
    {
        $ticket = MaintenanceTicket::query()
            ->with(['item', 'reportedBy:id,name', 'logs.handledBy:id,name'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /**
     * Buat tiket kerusakan baru (siswa/guru/teknisi) — otomatis kunci barang.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|exists:items,item_code',
            'issue_description' => 'required|string|max:2000',
            'reported_by' => 'nullable|exists:users,id',
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $item = Item::query()->lockForUpdate()
                ->where('item_code', $validated['item_code'])
                ->firstOrFail();

            $ticket = MaintenanceTicket::create([
                'item_id' => $item->id,
                'reported_by' => $validated['reported_by'] ?? $request->user()?->id,
                'issue_description' => $validated['issue_description'],
                'status' => TicketStatus::OPEN,
            ]);

            // Tiket kerusakan mengunci barang dari katalog Kiosk.
            if ($item->status !== ItemStatus::UNDER_MAINTENANCE) {
                $item->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
            }

            return $ticket->load('item', 'reportedBy:id,name');
        });

        return response()->json([
            'success' => true,
            'message' => 'Tiket kerusakan dibuat. Barang dikunci dari katalog.',
            'data' => $result,
        ], 201);
    }

    /**
     * Teknisi mulai menangani tiket → status in_progress.
     */
    public function start(int $id, Request $request)
    {
        $ticket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($id);

        if ($ticket->status === TicketStatus::RESOLVED) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket sudah diselesaikan.',
            ], 422);
        }

        $ticket->update(['status' => TicketStatus::IN_PROGRESS]);

        return response()->json([
            'success' => true,
            'message' => 'Perbaikan dimulai.',
            'data' => $ticket->fresh(['item']),
        ]);
    }

    /**
     * Catat tindakan perbaikan ke maintenance_logs.
     */
    public function addLog(int $id, Request $request)
    {
        $validated = $request->validate([
            'handled_by' => 'nullable|exists:users,id',
            'action_taken' => 'required|string|max:2000',
            'service_type' => 'required|in:internal,vendor_luar',
            'cost' => 'nullable|numeric|min:0',
            'repaired_at' => 'nullable|date',
        ]);

        $ticket = MaintenanceTicket::query()->findOrFail($id);

        $log = MaintenanceLog::create([
            'ticket_id' => $ticket->id,
            'handled_by' => $validated['handled_by'] ?? $request->user()?->id,
            'action_taken' => $validated['action_taken'],
            'service_type' => $validated['service_type'],
            'cost' => $validated['cost'] ?? 0,
            'repaired_at' => $validated['repaired_at'] ?? now(),
        ]);

        $ticket->update(['status' => TicketStatus::IN_PROGRESS]);

        return response()->json([
            'success' => true,
            'message' => 'Log perbaikan dicatat.',
            'data' => $log->load('handledBy:id,name'),
        ], 201);
    }

    /**
     * Teknisi menyelesaikan tiket → barang restorasi ke 'tersedia'.
     */
    public function complete(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $ticket = MaintenanceTicket::query()->with('item')->lockForUpdate()->findOrFail($id);

            if ($ticket->status === TicketStatus::RESOLVED) {
                abort(422, 'Tiket sudah diselesaikan.');
            }

            $item = Item::query()->lockForUpdate()->findOrFail($ticket->item_id);
            $item->update(['status' => ItemStatus::TERSEDIA]);
            $ticket->update(['status' => TicketStatus::RESOLVED]);

            return $ticket->fresh(['item']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Perbaikan selesai. Aset kembali tersedia.',
            'data' => $result,
        ]);
    }
}
