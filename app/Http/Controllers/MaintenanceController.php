<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendMaintenanceTicketTelegramJob;
use App\Models\DamagedAsset;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\PreventiveSchedule;
use App\Services\DamagedAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly DamagedAssetService $damagedAssetService,
    ) {}

    /**
     * Tampilkan daftar semua tickets M&R dengan filter status.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        if (! in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $status = 'open';
        }

        $tickets = MaintenanceTicket::with(['item', 'reportedBy', 'logs.handledBy', 'latestLog.handledBy', 'preventiveSchedule'])
            ->where('status', $status)
            ->latest()
            ->paginate(15);

        // Hitungan global per status untuk badge tab
        $counts = [
            'open' => MaintenanceTicket::where('status', 'open')->count(),
            'in_progress' => MaintenanceTicket::where('status', 'in_progress')->count(),
            'resolved' => MaintenanceTicket::where('status', 'resolved')->count(),
        ];

        $totalMasterItemsCount = Item::query()->count();
        $unscheduledMasterItemsCount = Item::query()->whereDoesntHave('preventiveSchedules')->count();
        $inMaintenanceStageCount = PreventiveSchedule::query()
            ->where('workflow_stage', 'in_maintenance')
            ->whereHas('item', fn ($q) => $q->withoutTrashed())
            ->count();
        $needsRepairStageCount = PreventiveSchedule::query()
            ->where('workflow_stage', 'needs_repair')
            ->whereHas('item', fn ($q) => $q->withoutTrashed())
            ->count();
        $dueSchedulesCount = PreventiveSchedule::query()
            ->whereIn('workflow_stage', ['safe', 'scheduled'])
            ->where('status', 'scheduled')
            ->whereHas('item', fn ($q) => $q->withoutTrashed())
            ->whereDate('next_due_date', '<=', now()->addDays(7)->toDateString())
            ->count();
        $damagedUnitsCount = (int) DamagedAsset::query()->sum('quantity');

        return view('maintenance.index', compact(
            'tickets',
            'counts',
            'totalMasterItemsCount',
            'unscheduledMasterItemsCount',
            'inMaintenanceStageCount',
            'needsRepairStageCount',
            'dueSchedulesCount',
            'damagedUnitsCount'
        ));
    }

    /**
     * Tampilkan form create ticket baru.
     */
    public function create(Request $request)
    {
        $items = Item::query()->orderBy('name')->orderBy('item_code')->get();
        $requestedItemId = $request->query('item_id');
        $preselectedItemId = is_numeric($requestedItemId) && $items->contains('id', (int) $requestedItemId)
            ? (int) $requestedItemId
            : null;

        return view('maintenance.create', compact('items', 'preselectedItemId'));
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

        $ticket = DB::transaction(function () use ($validated) {
            $created = MaintenanceTicket::create($validated);

            // Update status item jika belum under_maintenance
            $item = Item::query()->lockForUpdate()->find($validated['item_id']);
            if ($item && $item->status !== ItemStatus::UNDER_MAINTENANCE && $validated['status'] !== 'resolved') {
                $item->update(['status' => ItemStatus::UNDER_MAINTENANCE]);
            }

            return $created;
        });

        if (config('services.telegram.enabled') && $ticket->status !== TicketStatus::RESOLVED) {
            dispatch(new SendMaintenanceTicketTelegramJob($ticket->id, 'created'));
        }

        return redirect()->route('maintenance.index')
            ->with('success', 'Tiket kerusakan berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail ticket.
     */
    public function show(string $id)
    {
        $ticket = MaintenanceTicket::with(['item', 'reportedBy', 'logs.handledBy', 'latestLog'])->findOrFail($id);

        return view('maintenance.show', compact('ticket'));
    }

    /**
     * Mulai proses perbaikan (Internal Sekolah atau Kirim ke Vendor Luar) & catat log awal.
     */
    public function start(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:internal,vendor_luar',
            'vendor_name' => 'required_if:service_type,vendor_luar|nullable|string|max:150',
            'estimated_finish_at' => 'nullable|date',
            'action_taken' => 'nullable|string|max:2000',
        ], [
            'service_type.required' => 'Pilih lokasi / metode penanganan perbaikan.',
            'vendor_name.required_if' => 'Nama vendor / bengkel wajib diisi jika alat dikirim ke vendor luar.',
            'estimated_finish_at.date' => 'Format tanggal estimasi selesai tidak valid.',
        ]);

        $vendorName = $validated['service_type'] === 'vendor_luar'
            ? trim((string) ($validated['vendor_name'] ?? ''))
            : null;

        $actionTaken = trim((string) ($validated['action_taken'] ?? ''));
        if ($actionTaken === '') {
            $actionTaken = $validated['service_type'] === 'vendor_luar'
                ? 'Alat dikirim ke vendor luar ('.$vendorName.') untuk proses perbaikan.'
                : 'Perbaikan internal di laboratorium sekolah dimulai oleh teknisi.';
        }

        $alreadyResolved = false;

        DB::transaction(function () use ($id, $validated, $vendorName, $actionTaken, &$alreadyResolved) {
            $ticket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($id);

            if ($ticket->status === TicketStatus::RESOLVED) {
                $alreadyResolved = true;

                return;
            }

            $ticket->update([
                'status' => TicketStatus::IN_PROGRESS,
            ]);

            if ($ticket->item_id) {
                Item::where('id', $ticket->item_id)->update([
                    'status' => ItemStatus::UNDER_MAINTENANCE,
                ]);
            }

            MaintenanceLog::create([
                'ticket_id' => $ticket->id,
                'handled_by' => auth()->id(),
                'action_taken' => $actionTaken,
                'service_type' => $validated['service_type'],
                'vendor_name' => $vendorName,
                'cost' => 0,
                'repaired_at' => now(),
                'estimated_finish_at' => $validated['estimated_finish_at'] ?? null,
            ]);
        });

        if ($alreadyResolved) {
            return back()->with('error', 'Tiket ini sudah berstatus Selesai dan tidak dapat dimulai ulang.');
        }

        $message = $validated['service_type'] === 'vendor_luar'
            ? 'Perbaikan dimulai (Vendor Luar: '.$vendorName.'). Tiket dipindahkan ke Sedang Dikerjakan.'
            : 'Perbaikan internal di sekolah dimulai. Tiket dipindahkan ke Sedang Dikerjakan.';

        if ($request->input('return_to') === 'show') {
            return redirect()->route('maintenance.show', $id)->with('success', $message);
        }

        return redirect()->route('maintenance.index', ['status' => 'in_progress'])
            ->with('success', $message);
    }

    /**
     * Tambahkan catatan progres perbaikan bertahap pada detail tiket.
     */
    public function storeLog(Request $request, string $id)
    {
        $phpMaxKb = max(1, (int) floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024));
        $phpMaxMb = number_format($phpMaxKb / 1024, 1, ',', '.');

        $validated = $request->validate([
            'service_type' => 'required|in:internal,vendor_luar',
            'vendor_name' => 'required_if:service_type,vendor_luar|nullable|string|max:150',
            'estimated_finish_at' => 'nullable|date',
            'action_taken' => 'required|string|max:2000',
            'spareparts_used' => 'nullable|string|max:1000',
            'cost' => 'nullable|numeric|min:0|max:999999999',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'service_type.required' => 'Pilih lokasi / metode penanganan perbaikan.',
            'vendor_name.required_if' => 'Nama vendor / bengkel wajib diisi jika penanganan dilakukan di vendor luar.',
            'action_taken.required' => 'Deskripsi tindakan progres perbaikan wajib diisi.',
            'estimated_finish_at.date' => 'Format tanggal estimasi selesai tidak valid.',
            'cost.numeric' => 'Harga / biaya perbaikan harus berupa angka.',
            'cost.min' => 'Harga / biaya perbaikan tidak boleh bernilai negatif.',
            'receipt.uploaded' => 'File struk gagal diunggah ke server. Pastikan ukuran file tidak melebihi batas PHP saat ini ('.$phpMaxMb.' MB) atau coba kompres file terlebih dahulu.',
            'receipt.mimes' => 'Struk / nota harus berformat gambar (JPG, PNG, WEBP) atau PDF.',
            'receipt.max' => 'Ukuran file struk maksimal 4 MB.',
        ]);

        $ticketCheck = MaintenanceTicket::findOrFail($id);
        if ($ticketCheck->status === TicketStatus::RESOLVED) {
            return back()->with('error', 'Tiket sudah berstatus Selesai dan tidak dapat ditambah log progres.');
        }

        $vendorName = $validated['service_type'] === 'vendor_luar'
            ? trim((string) ($validated['vendor_name'] ?? ''))
            : null;

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('uploads/receipts/maintenance', 'public');
        }

        $alreadyResolved = false;

        try {
            DB::transaction(function () use ($id, $validated, $vendorName, $receiptPath, &$alreadyResolved) {
                $ticket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($id);

                if ($ticket->status === TicketStatus::RESOLVED) {
                    $alreadyResolved = true;

                    return;
                }

                if ($ticket->status === TicketStatus::OPEN) {
                    $ticket->update(['status' => TicketStatus::IN_PROGRESS]);
                }

                MaintenanceLog::create([
                    'ticket_id' => $ticket->id,
                    'handled_by' => auth()->id(),
                    'action_taken' => trim((string) $validated['action_taken']),
                    'spareparts_used' => filled($validated['spareparts_used'] ?? null)
                        ? trim((string) $validated['spareparts_used'])
                        : null,
                    'service_type' => $validated['service_type'],
                    'vendor_name' => $vendorName,
                    'cost' => (float) ($validated['cost'] ?? 0),
                    'receipt_path' => $receiptPath,
                    'repaired_at' => now(),
                    'estimated_finish_at' => $validated['estimated_finish_at'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            if ($receiptPath !== null) {
                Storage::disk('public')->delete($receiptPath);
            }
            throw $e;
        }

        if ($alreadyResolved) {
            if ($receiptPath !== null) {
                Storage::disk('public')->delete($receiptPath);
            }

            return back()->with('error', 'Tiket sudah berstatus Selesai dan tidak dapat ditambah log progres.');
        }

        if ($request->input('return_to') === 'edit') {
            return redirect()->route('maintenance.edit', $id)
                ->with('success', 'Log progres / nota bertahap berhasil ditambahkan.');
        }

        return redirect()->route('maintenance.show', $id)
            ->with('success', 'Log progres perbaikan berhasil ditambahkan.');
    }

    /**
     * Selesaikan perbaikan dengan laporan hasil akhir (bener vs rusak permanen), biaya opsional, dan struk opsional.
     */
    public function complete(Request $request, string $id)
    {
        $phpMaxKb = max(1, (int) floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024));
        $phpMaxMb = number_format($phpMaxKb / 1024, 1, ',', '.');

        $validated = $request->validate([
            'outcome' => 'nullable|in:repaired,broken',
            'broken_quantity' => 'nullable|integer|min:1|max:10000',
            'service_type' => 'required|in:internal,vendor_luar',
            'vendor_name' => 'required_if:service_type,vendor_luar|nullable|string|max:150',
            'action_taken' => 'required|string|max:2000',
            'spareparts_used' => 'nullable|string|max:1000',
            'cost' => 'nullable|numeric|min:0|max:999999999',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'service_type.required' => 'Pilih lokasi / metode penanganan perbaikan.',
            'vendor_name.required_if' => 'Nama vendor / bengkel wajib diisi jika penanganan dilakukan di vendor luar.',
            'action_taken.required' => 'Laporan tindakan dan hasil kondisi barang wajib diisi.',
            'broken_quantity.min' => 'Jumlah unit rusak permanen minimal 1 unit.',
            'cost.numeric' => 'Harga / biaya perbaikan harus berupa angka.',
            'cost.min' => 'Harga / biaya perbaikan tidak boleh bernilai negatif.',
            'receipt.uploaded' => 'File struk gagal diunggah ke server. Pastikan ukuran file tidak melebihi batas PHP saat ini ('.$phpMaxMb.' MB) atau coba kompres file terlebih dahulu.',
            'receipt.mimes' => 'Struk / nota harus berformat gambar (JPG, PNG, WEBP) atau PDF.',
            'receipt.max' => 'Ukuran file struk maksimal 4 MB.',
        ]);

        $ticketCheck = MaintenanceTicket::with('item')->findOrFail($id);
        if ($ticketCheck->status === TicketStatus::RESOLVED) {
            return back()->with('error', 'Tiket ini sudah berstatus Selesai.');
        }

        $outcome = ($validated['outcome'] ?? 'repaired') === 'broken' ? 'broken' : 'repaired';

        if ($outcome === 'broken' && $ticketCheck->item_id && isset($validated['broken_quantity'])) {
            $stockLimit = max(1, (int) ($ticketCheck->item?->stock ?? 1));
            if ((int) $validated['broken_quantity'] > $stockLimit) {
                throw ValidationException::withMessages([
                    'broken_quantity' => "Jumlah unit rusak permanen tidak boleh melebihi stok Master Aset ({$stockLimit} unit).",
                ]);
            }
        }

        $vendorName = $validated['service_type'] === 'vendor_luar'
            ? trim((string) ($validated['vendor_name'] ?? ''))
            : null;

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('uploads/receipts/maintenance', 'public');
        }

        $alreadyResolved = false;
        $writeOffResult = null;

        try {
            DB::transaction(function () use ($id, $validated, $outcome, $vendorName, $receiptPath, &$alreadyResolved, &$writeOffResult) {
                $ticket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($id);

                if ($ticket->status === TicketStatus::RESOLVED) {
                    $alreadyResolved = true;

                    return;
                }

                MaintenanceLog::create([
                    'ticket_id' => $ticket->id,
                    'handled_by' => auth()->id(),
                    'action_taken' => trim((string) $validated['action_taken']),
                    'spareparts_used' => filled($validated['spareparts_used'] ?? null)
                        ? trim((string) $validated['spareparts_used'])
                        : null,
                    'service_type' => $validated['service_type'],
                    'vendor_name' => $vendorName,
                    'cost' => (float) ($validated['cost'] ?? 0),
                    'receipt_path' => $receiptPath,
                    'repaired_at' => now(),
                ]);

                $ticket->update([
                    'status' => TicketStatus::RESOLVED,
                    'outcome' => $outcome,
                ]);

                if ($outcome === 'broken') {
                    $linkedSchedule = PreventiveSchedule::query()
                        ->where('maintenance_ticket_id', $ticket->id)
                        ->lockForUpdate()
                        ->first();

                    if ($ticket->item_id) {
                        $brokenQty = isset($validated['broken_quantity'])
                            ? max(1, (int) $validated['broken_quantity'])
                            : max(1, (int) ($linkedSchedule?->damaged_quantity ?: 1));

                        $totalTicketCost = (float) MaintenanceLog::query()
                            ->where('ticket_id', $ticket->id)
                            ->sum('cost');

                        $effectiveReceiptPath = $receiptPath ?? MaintenanceLog::query()
                            ->where('ticket_id', $ticket->id)
                            ->whereNotNull('receipt_path')
                            ->latest('id')
                            ->value('receipt_path');

                        $writeOffResult = $this->damagedAssetService->recordAndReduceMasterAsset([
                            'item_id' => $ticket->item_id,
                            'quantity' => $brokenQty,
                            'source' => 'repair_ticket',
                            'maintenance_ticket_id' => $ticket->id,
                            'preventive_schedule_id' => $linkedSchedule?->id,
                            'damage_reason' => trim((string) $validated['action_taken']),
                            'service_type' => $validated['service_type'],
                            'vendor_name' => $vendorName,
                            'repair_cost' => $totalTicketCost,
                            'receipt_path' => $effectiveReceiptPath,
                        ]);
                    }

                    if ($linkedSchedule) {
                        $linkedSchedule->update([
                            'workflow_stage' => 'written_off',
                            'status' => 'paused',
                        ]);
                    }
                } elseif ($ticket->item_id) {
                    Item::where('id', $ticket->item_id)->update([
                        'status' => ItemStatus::TERSEDIA,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($receiptPath !== null) {
                Storage::disk('public')->delete($receiptPath);
            }
            throw $e;
        }

        if ($alreadyResolved) {
            if ($receiptPath !== null) {
                Storage::disk('public')->delete($receiptPath);
            }

            return back()->with('error', 'Tiket ini sudah berstatus Selesai.');
        }

        if (
            $outcome !== 'broken'
            && config('services.telegram.enabled')
            && config('services.telegram.notify_ticket_resolved', false)
        ) {
            dispatch(new SendMaintenanceTicketTelegramJob((int) $id, 'resolved'));
        }

        if ($outcome === 'broken') {
            $itemLabel = $ticketCheck->item?->name ?? 'Barang';
            $retiredQty = (int) ($writeOffResult['retired_qty'] ?? 1);
            $brokenMsg = ! empty($writeOffResult['item_deleted'])
                ? "\"{$itemLabel}\" ({$retiredQty} unit) dinyatakan Rusak Permanen, dicatat di Barang Rusak, dan dikeluarkan dari Master Aset."
                : "\"{$itemLabel}\" ({$retiredQty} unit) dinyatakan Rusak Permanen dan masuk ke Barang Rusak. Stok Master Aset berkurang menjadi ".($writeOffResult['remaining_stock'] ?? 0).' unit.';

            return redirect()->route('maintenance.damaged.index')
                ->with('success', $brokenMsg);
        }

        $successMessage = 'Laporan selesai perbaikan disimpan. Barang sekarang berstatus TERSEDIA kembali.';

        if (in_array($request->input('return_to'), ['show', 'edit'], true)) {
            return redirect()->route('maintenance.show', $id)->with('success', $successMessage);
        }

        return redirect()->route('maintenance.index', ['status' => 'resolved'])
            ->with('success', $successMessage);
    }

    /**
     * Sajikan file foto / PDF struk perbaikan secara aman di balik auth staf.
     */
    public function receipt(string $logId)
    {
        $log = MaintenanceLog::findOrFail($logId);
        $path = ltrim((string) $log->receipt_path, '/\\');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        $contents = null;

        if ($disk->exists($path)) {
            $contents = $disk->get($path);
        } else {
            $publicFallback = public_path('storage/'.$path);
            if (is_file($publicFallback)) {
                $contents = @file_get_contents($publicFallback) ?: null;
            }
        }

        if ($contents === null || $contents === '') {
            abort(404);
        }

        return response($contents, 200, [
            'Content-Type' => $this->detectReceiptMime($contents, $path),
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function detectReceiptMime(string $binary, string $path): string
    {
        if (str_starts_with($binary, '%PDF-')) {
            return 'application/pdf';
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (strlen($binary) >= 12 && substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    /**
     * Tampilkan form edit ticket (dan detail progres terpadu bila berstatus in_progress).
     */
    public function edit(string $id)
    {
        $ticket = MaintenanceTicket::with(['item', 'reportedBy', 'logs.handledBy', 'latestLog', 'preventiveSchedule'])->findOrFail($id);

        return view('maintenance.edit', compact('ticket'));
    }

    /**
     * Update ticket di database (beserta rencana/perubahan tempat perbaikan & catatan opsional).
     */
    public function update(Request $request, string $id)
    {
        $ticket = MaintenanceTicket::with(['latestLog', 'item'])->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
            'outcome' => 'nullable|in:repaired,broken',
            'broken_quantity' => 'nullable|integer|min:1|max:10000',
            'issue_description' => 'nullable|string|max:2000',
            'service_type' => 'nullable|in:internal,vendor_luar',
            'vendor_name' => 'required_if:service_type,vendor_luar|nullable|string|max:150',
            'estimated_finish_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ], [
            'vendor_name.required_if' => 'Nama vendor / bengkel wajib diisi jika memilih penanganan vendor luar.',
            'estimated_finish_at.date' => 'Format tanggal estimasi selesai tidak valid.',
            'broken_quantity.min' => 'Jumlah unit rusak permanen minimal 1 unit.',
        ]);

        $wasResolved = $ticket->status === TicketStatus::RESOLVED;
        $alreadyBroken = $ticket->outcome === 'broken' || DamagedAsset::where('maintenance_ticket_id', $ticket->id)->exists();
        $requestedOutcome = $alreadyBroken
            ? 'broken'
            : (($validated['status'] === 'resolved' && ($validated['outcome'] ?? 'repaired') === 'broken') ? 'broken' : 'repaired');

        if ($requestedOutcome === 'broken' && ! $alreadyBroken && $ticket->item_id && isset($validated['broken_quantity'])) {
            $stockLimit = max(1, (int) ($ticket->item?->stock ?? 1));
            if ((int) $validated['broken_quantity'] > $stockLimit) {
                throw ValidationException::withMessages([
                    'broken_quantity' => "Jumlah unit rusak permanen tidak boleh melebihi stok Master Aset ({$stockLimit} unit).",
                ]);
            }
        }

        $writeOffResult = null;
        $newlyMarkedBroken = false;

        DB::transaction(function () use ($ticket, $validated, $alreadyBroken, $requestedOutcome, &$writeOffResult, &$newlyMarkedBroken) {
            $lockedTicket = MaintenanceTicket::query()->with('latestLog')->lockForUpdate()->findOrFail($ticket->id);

            $ticketUpdate = [
                'status' => $alreadyBroken ? TicketStatus::RESOLVED->value : $validated['status'],
                'outcome' => $validated['status'] === 'resolved' || $alreadyBroken ? $requestedOutcome : 'repaired',
            ];

            if (array_key_exists('issue_description', $validated) && filled($validated['issue_description'])) {
                $ticketUpdate['issue_description'] = trim((string) $validated['issue_description']);
            }

            $lockedTicket->update($ticketUpdate);

            $latestLog = $lockedTicket->latestLog;
            $selectedService = $validated['service_type'] ?? null;
            $notesText = trim((string) ($validated['notes'] ?? ''));
            $vendorName = $selectedService === 'vendor_luar'
                ? trim((string) ($validated['vendor_name'] ?? ''))
                : null;
            $estimatedFinish = $selectedService === 'vendor_luar'
                ? ($validated['estimated_finish_at'] ?? null)
                : null;

            $serviceChanged = filled($selectedService) && (
                ! $latestLog
                || $latestLog->service_type?->value !== $selectedService
                || ($selectedService === 'vendor_luar' && trim((string) ($latestLog->vendor_name ?? '')) !== (string) $vendorName)
                || ($estimatedFinish !== null && optional($latestLog->estimated_finish_at)->format('Y-m-d') !== $estimatedFinish)
            );

            $createdOrLatestLog = $latestLog;
            if ($notesText !== '' || $serviceChanged) {
                $effectiveService = filled($selectedService)
                    ? $selectedService
                    : ($latestLog?->service_type?->value ?? 'internal');
                $effectiveVendor = $effectiveService === 'vendor_luar'
                    ? ($vendorName !== '' ? $vendorName : $latestLog?->vendor_name)
                    : null;

                $actionText = $notesText !== ''
                    ? $notesText
                    : ($effectiveService === 'vendor_luar'
                        ? 'Tempat perbaikan ditetapkan ke Vendor Luar ('.($effectiveVendor ?: '-').').'
                        : 'Tempat perbaikan ditetapkan ke Perbaikan Internal Sekolah.');

                $createdOrLatestLog = MaintenanceLog::create([
                    'ticket_id' => $lockedTicket->id,
                    'handled_by' => auth()->id(),
                    'action_taken' => $actionText,
                    'service_type' => $effectiveService,
                    'vendor_name' => $effectiveVendor,
                    'cost' => 0,
                    'repaired_at' => now(),
                    'estimated_finish_at' => $estimatedFinish,
                ]);
            }

            if ($validated['status'] === 'resolved' && $requestedOutcome === 'broken' && ! $alreadyBroken) {
                $newlyMarkedBroken = true;
                $linkedSchedule = PreventiveSchedule::query()
                    ->where('maintenance_ticket_id', $lockedTicket->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedTicket->item_id) {
                    $brokenQty = isset($validated['broken_quantity'])
                        ? max(1, (int) $validated['broken_quantity'])
                        : max(1, (int) ($linkedSchedule?->damaged_quantity ?: 1));

                    $totalTicketCost = (float) MaintenanceLog::query()
                        ->where('ticket_id', $lockedTicket->id)
                        ->sum('cost');

                    $effectiveReceiptPath = MaintenanceLog::query()
                        ->where('ticket_id', $lockedTicket->id)
                        ->whereNotNull('receipt_path')
                        ->latest('id')
                        ->value('receipt_path');

                    $reason = $notesText !== ''
                        ? $notesText
                        : (trim((string) ($lockedTicket->issue_description ?? '')) ?: 'Dinyatakan rusak permanen saat proses perbaikan.');

                    $writeOffResult = $this->damagedAssetService->recordAndReduceMasterAsset([
                        'item_id' => $lockedTicket->item_id,
                        'quantity' => $brokenQty,
                        'source' => 'repair_ticket',
                        'maintenance_ticket_id' => $lockedTicket->id,
                        'preventive_schedule_id' => $linkedSchedule?->id,
                        'damage_reason' => $reason,
                        'service_type' => $createdOrLatestLog?->service_type?->value,
                        'vendor_name' => $createdOrLatestLog?->vendor_name,
                        'repair_cost' => $totalTicketCost,
                        'receipt_path' => $effectiveReceiptPath,
                    ]);
                }

                if ($linkedSchedule) {
                    $linkedSchedule->update([
                        'workflow_stage' => 'written_off',
                        'status' => 'paused',
                    ]);
                }
            } elseif (! $alreadyBroken && $lockedTicket->item_id) {
                $targetItemStatus = $validated['status'] === 'resolved'
                    ? ItemStatus::TERSEDIA
                    : ItemStatus::UNDER_MAINTENANCE;

                Item::where('id', $lockedTicket->item_id)->update([
                    'status' => $targetItemStatus,
                ]);
            }
        });

        if (
            ! $wasResolved
            && $validated['status'] === 'resolved'
            && $requestedOutcome !== 'broken'
            && config('services.telegram.enabled')
            && config('services.telegram.notify_ticket_resolved', false)
        ) {
            dispatch(new SendMaintenanceTicketTelegramJob($ticket->id, 'resolved'));
        }

        if ($newlyMarkedBroken) {
            $itemLabel = $ticket->item?->name ?? 'Barang';
            $retiredQty = (int) ($writeOffResult['retired_qty'] ?? 1);
            $brokenMsg = ! empty($writeOffResult['item_deleted'])
                ? "\"{$itemLabel}\" ({$retiredQty} unit) dinyatakan Rusak Permanen, dicatat di Barang Rusak, dan dikeluarkan dari Master Aset."
                : "\"{$itemLabel}\" ({$retiredQty} unit) dinyatakan Rusak Permanen dan masuk ke Barang Rusak. Stok Master Aset berkurang menjadi ".($writeOffResult['remaining_stock'] ?? 0).' unit.';

            return redirect()->route('maintenance.damaged.index')
                ->with('success', $brokenMsg);
        }

        $message = $validated['status'] === 'resolved'
            ? ($requestedOutcome === 'broken'
                ? 'Perubahan catatan tiket barang rusak permanen berhasil disimpan.'
                : 'Tiket telah diselesaikan (Resolved). Barang sekarang berstatus TERSEDIA kembali.')
            : 'Perubahan tiket & penanganan berhasil disimpan.';

        if ($request->input('return_to') === 'tab') {
            return redirect()->route('maintenance.index', ['status' => $alreadyBroken ? 'resolved' : $validated['status']])
                ->with('success', $message);
        }

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
