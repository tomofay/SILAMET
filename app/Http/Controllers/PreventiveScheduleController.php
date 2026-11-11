<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PreventiveSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreventiveScheduleController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $today = Carbon::today();
        $dueSoonLimit = $today->copy()->addDays(7);

        $query = PreventiveSchedule::query()
            ->with('item')
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('next_due_date');

        if ($filter === 'overdue') {
            $query->where('status', 'scheduled')->whereDate('next_due_date', '<', $today);
        } elseif ($filter === 'due_soon') {
            $query->where('status', 'scheduled')
                ->whereDate('next_due_date', '>=', $today)
                ->whereDate('next_due_date', '<=', $dueSoonLimit);
        } elseif ($filter === 'paused') {
            $query->where('status', 'paused');
        }

        $schedules = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => PreventiveSchedule::count(),
            'overdue' => PreventiveSchedule::where('status', 'scheduled')->whereDate('next_due_date', '<', $today)->count(),
            'due_soon' => PreventiveSchedule::where('status', 'scheduled')
                ->whereDate('next_due_date', '>=', $today)
                ->whereDate('next_due_date', '<=', $dueSoonLimit)
                ->count(),
            'paused' => PreventiveSchedule::where('status', 'paused')->count(),
        ];

        return view('maintenance.schedules.index', compact('schedules', 'counts', 'filter'));
    }

    public function create()
    {
        $items = Item::query()->orderBy('name')->get();

        return view('maintenance.schedules.create', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'title' => 'required|string|max:150',
            'interval_months' => 'required|integer|min:1|max:60',
            'last_serviced_at' => 'required|date',
            'status' => 'nullable|in:scheduled,paused',
            'notes' => 'nullable|string|max:2000',
        ]);

        $nextDueDate = PreventiveSchedule::calculateNextDueDate(
            $validated['last_serviced_at'],
            (int) $validated['interval_months']
        );

        PreventiveSchedule::create([
            'item_id' => $validated['item_id'],
            'title' => $validated['title'],
            'interval_months' => (int) $validated['interval_months'],
            'last_serviced_at' => $validated['last_serviced_at'],
            'next_due_date' => $nextDueDate->toDateString(),
            'status' => $validated['status'] ?? 'scheduled',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('maintenance.schedules.index')
            ->with('success', 'Jadwal perawatan preventif berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $schedule = PreventiveSchedule::with('item')->findOrFail($id);
        $items = Item::query()->orderBy('name')->get();

        return view('maintenance.schedules.edit', compact('schedule', 'items'));
    }

    public function update(Request $request, string $id)
    {
        $schedule = PreventiveSchedule::findOrFail($id);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'title' => 'required|string|max:150',
            'interval_months' => 'required|integer|min:1|max:60',
            'last_serviced_at' => 'required|date',
            'status' => 'required|in:scheduled,paused',
            'notes' => 'nullable|string|max:2000',
        ]);

        $nextDueDate = PreventiveSchedule::calculateNextDueDate(
            $validated['last_serviced_at'],
            (int) $validated['interval_months']
        );

        $dueChanged = $schedule->next_due_date?->toDateString() !== $nextDueDate->toDateString();

        $schedule->update([
            'item_id' => $validated['item_id'],
            'title' => $validated['title'],
            'interval_months' => (int) $validated['interval_months'],
            'last_serviced_at' => $validated['last_serviced_at'],
            'next_due_date' => $nextDueDate->toDateString(),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'last_notified_stage' => $dueChanged ? null : $schedule->last_notified_stage,
            'last_notified_at' => $dueChanged ? null : $schedule->last_notified_at,
        ]);

        return redirect()->route('maintenance.schedules.index')
            ->with('success', 'Jadwal perawatan preventif berhasil diperbarui.');
    }

    public function markServiced(Request $request, string $id)
    {
        $validated = $request->validate([
            'serviced_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $nextDueLabel = DB::transaction(function () use ($id, $validated) {
            $schedule = PreventiveSchedule::query()->lockForUpdate()->findOrFail($id);

            $servicedAt = ! empty($validated['serviced_at'])
                ? Carbon::parse($validated['serviced_at'])->startOfDay()
                : Carbon::today();

            $nextDueDate = PreventiveSchedule::calculateNextDueDate(
                $servicedAt,
                (int) $schedule->interval_months
            );

            $schedule->update([
                'last_serviced_at' => $servicedAt->toDateString(),
                'next_due_date' => $nextDueDate->toDateString(),
                'notes' => array_key_exists('notes', $validated) && filled($validated['notes'])
                    ? $validated['notes']
                    : $schedule->notes,
                'last_notified_stage' => null,
                'last_notified_at' => null,
            ]);

            return $nextDueDate->translatedFormat('d M Y');
        });

        return redirect()->route('maintenance.schedules.index')
            ->with('success', "Servis berkala dicatat. Jadwal berikutnya otomatis ditetapkan ke {$nextDueLabel}.");
    }

    public function destroy(string $id)
    {
        $schedule = PreventiveSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('maintenance.schedules.index')
            ->with('success', 'Jadwal perawatan preventif berhasil dihapus.');
    }
}

