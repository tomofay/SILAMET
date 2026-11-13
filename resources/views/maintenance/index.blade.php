<x-app-layout>
    <x-slot name="heading">Maintenance & Repair (M&R)</x-slot>
    <x-slot name="title">Modul Maintenance</x-slot>

    @php
        $activeTab = request('status', 'open');
        $showWorkflowCols = in_array($activeTab, ['in_progress', 'resolved'], true);
        $tabs = [
            'open' => ['label' => 'Perlu Tindakan (Open)', 'count' => $counts['open'] ?? 0],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'count' => $counts['in_progress'] ?? 0],
            'resolved' => ['label' => 'Selesai & Uji Layak', 'count' => $counts['resolved'] ?? 0],
        ];
    @endphp

    <div class="space-y-4">

        <!-- Header Overview -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Tiket Kerusakan &amp; Perawatan Aset</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Kelola laporan kerusakan dari siswa di Kiosk dan jadwal maintenance seluruh barang Master Aset.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Jadwal Maintenance Barang</span>
                    @if(($unscheduledMasterItemsCount ?? 0) > 0)
                        <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $unscheduledMasterItemsCount }} belum terjadwal
                        </span>
                    @elseif(($dueSchedulesCount ?? 0) > 0)
                        <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                            {{ $dueSchedulesCount }} jatuh tempo
                        </span>
                    @endif
                </a>
                <a href="{{ route('maintenance.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Tiket Kerusakan</span>
                </a>
            </div>
        </div>

        @if($errors->any() && !in_array(old('_modal_mode'), ['start', 'complete'], true))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-bold">Gagal menyimpan data perbaikan:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('maintenance.partials._module_tabs', [
            'activeBar' => 'tickets',
            'unscheduledMasterItemsCount' => $unscheduledMasterItemsCount ?? 0,
            'damagedUnitsCount' => $damagedUnitsCount ?? 0,
            'inMaintenanceStageCount' => $inMaintenanceStageCount ?? 0,
            'needsRepairStageCount' => $needsRepairStageCount ?? 0,
        ])

        @if(($unscheduledMasterItemsCount ?? 0) > 0)
            <div class="bg-amber-50/80 border border-amber-200 rounded-xl px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-2.5 text-xs text-amber-900">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        <span class="font-bold">{{ $unscheduledMasterItemsCount }} dari {{ $totalMasterItemsCount }} barang di Master Aset</span> belum memiliki jadwal maintenance berkala. Pilih barang atau jadwalkan seluruhnya secara serentak.
                    </span>
                </div>
                <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/70 text-amber-900 border border-amber-300 text-xs font-semibold rounded-lg whitespace-nowrap transition-colors">
                    <span>Atur Jadwal Maintenance</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif

        <!-- Filter Tab Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $activeTab === $key; @endphp
                <a href="{{ route('maintenance.index', ['status' => $key]) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang Bermasalah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Deskripsi Kendala</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pelapor</th>
                            @if($showWorkflowCols)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Penanganan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Biaya &amp; Struk</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status Tiket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal Lapor</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($tickets as $ticket)
                            @php
                                $latestLog = $ticket->latestLog;
                                $estimatedDate = $latestLog?->estimated_finish_at
                                    ?? $ticket->logs->first(fn ($l) => $l->estimated_finish_at !== null)?->estimated_finish_at;
                                $totalCost = (float) $ticket->logs->sum('cost');
                                $receiptLogs = $ticket->logs->filter(fn ($l) => filled($l->receipt_path))->values();
                            @endphp
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $ticket->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $ticket->item->item_code ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 max-w-md">
                                    <p class="text-xs text-neutral-700 line-clamp-2">{{ $ticket->issue_description }}</p>
                                    @if($showWorkflowCols && $latestLog)
                                        <p class="text-[11px] text-neutral-500 mt-0.5 line-clamp-1">
                                            {{ $latestLog->action_taken }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs font-medium text-neutral-800">{{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                                </td>

                                @if($showWorkflowCols)
                                    <td class="px-4 py-3">
                                        @if($latestLog)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $latestLog->service_type->value === 'internal' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                                {{ $latestLog->service_type->value === 'internal' ? 'Internal Sekolah' : 'Vendor Luar' }}
                                            </span>
                                            @if($latestLog->service_type->value === 'vendor_luar' && filled($latestLog->vendor_name))
                                                <div class="text-[11px] font-medium text-neutral-700 mt-1 max-w-[180px] truncate" title="{{ $latestLog->vendor_name }}">
                                                    {{ $latestLog->vendor_name }}
                                                </div>
                                            @endif
                                            @if($activeTab === 'in_progress' && $estimatedDate)
                                                <div class="text-[11px] font-mono text-neutral-500 mt-0.5">
                                                    Est: {{ $estimatedDate->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-[11px] text-neutral-400">Belum dicatat</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($totalCost > 0)
                                            <div class="font-mono text-xs font-semibold text-neutral-900">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
                                        @else
                                            <div class="text-[11px] text-neutral-400">Tanpa biaya</div>
                                        @endif

                                        @if($receiptLogs->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-1 mt-1">
                                                @foreach($receiptLogs as $rIdx => $rLog)
                                                    @php
                                                        $isPdf = str_ends_with(strtolower((string) $rLog->receipt_path), '.pdf');
                                                        $receiptLabel = $receiptLogs->count() > 1 ? 'Struk #' . ($rIdx + 1) : 'Lihat Struk';
                                                    @endphp
                                                    @if($isPdf)
                                                        <a href="{{ route('maintenance.receipt', $rLog->id) }}" target="_blank"
                                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-[11px] font-semibold text-neutral-700 transition-colors"
                                                           title="Buka Dokumen Struk PDF">
                                                            <svg class="w-3 h-3 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            <span>PDF</span>
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                                class="open-receipt-modal inline-flex items-center gap-1 px-2 py-0.5 rounded border border-primary-200 bg-primary-50/70 hover:bg-primary-100 text-[11px] font-semibold text-primary-700 transition-colors"
                                                                data-receipt-url="{{ route('maintenance.receipt', $rLog->id) }}"
                                                                data-receipt-title="Struk Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}"
                                                                data-receipt-meta="{{ $rLog->service_type->label() }}{{ filled($rLog->vendor_name) ? ' · ' . $rLog->vendor_name : '' }}{{ (float) $rLog->cost > 0 ? ' · Rp ' . number_format($rLog->cost, 0, ',', '.') : '' }}"
                                                                title="Lihat Foto Struk">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            <span>{{ $receiptLabel }}</span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                @endif

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $badge = match($ticket->status->value) {
                                            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $badge }}">
                                        {{ $ticket->status->label() }}
                                    </span>
                                    @if($ticket->status->value === 'resolved' && $ticket->outcome === 'broken')
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Rusak Permanen
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ optional($ticket->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        @if($ticket->status->value === 'open')
                                            <a href="{{ route('maintenance.edit', $ticket->id) }}" class="btn-icon-action" title="Edit Tiket" aria-label="Edit Tiket #{{ $ticket->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button type="button"
                                                    class="open-start-repair-modal btn-icon-action text-amber-600 hover:text-amber-800 hover:bg-amber-50 hover:border-amber-200"
                                                    data-action="{{ route('maintenance.start', $ticket->id) }}"
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    data-item-name="{{ $ticket->item->name ?? '-' }}"
                                                    data-item-code="{{ $ticket->item->item_code ?? '—' }}"
                                                    data-issue="{{ $ticket->issue_description }}"
                                                    data-service-type="{{ $latestLog?->service_type?->value ?? 'internal' }}"
                                                    data-vendor-name="{{ $latestLog?->vendor_name ?? '' }}"
                                                    data-estimated-finish="{{ optional($estimatedDate)->format('Y-m-d') ?? '' }}"
                                                    title="Mulai Perbaikan"
                                                    aria-label="Mulai Perbaikan Tiket #{{ $ticket->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @elseif($ticket->status->value === 'in_progress')
                                            <a href="{{ route('maintenance.edit', $ticket->id) }}" class="btn-icon-action" title="Edit &amp; Detail Progres Tiket" aria-label="Edit &amp; Detail Tiket #{{ $ticket->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button type="button"
                                                    class="open-complete-repair-modal btn-icon-action text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 hover:border-emerald-200"
                                                    data-action="{{ route('maintenance.complete', $ticket->id) }}"
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    data-item-name="{{ $ticket->item->name ?? '-' }}"
                                                    data-item-code="{{ $ticket->item->item_code ?? '—' }}"
                                                    data-item-stock="{{ max(1, (int) ($ticket->item->stock ?? 1)) }}"
                                                    data-broken-qty="{{ max(1, min(max(1, (int) ($ticket->item->stock ?? 1)), (int) ($ticket->preventiveSchedule?->damaged_quantity ?: 1))) }}"
                                                    data-issue="{{ $ticket->issue_description }}"
                                                    data-service-type="{{ $latestLog?->service_type?->value ?? 'internal' }}"
                                                    data-vendor-name="{{ $latestLog?->vendor_name ?? '' }}"
                                                    data-existing-cost="{{ $totalCost }}"
                                                    title="Selesai &amp; Laporkan Perbaikan"
                                                    aria-label="Selesaikan Tiket #{{ $ticket->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        @else
                                            <a href="{{ route('maintenance.show', $ticket->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Laporan Lengkap" aria-label="Detail Tiket #{{ $ticket->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showWorkflowCols ? 8 : 6 }}" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Tidak Ada Tiket Perbaikan</p>
                                    <p class="text-xs text-neutral-500 mt-1">Semua alat laboratorium dalam kondisi siap pakai.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $tickets->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>

    @include('maintenance.partials._repair_modals', ['returnTo' => 'index'])
</x-app-layout>
