<x-app-layout>
    <x-slot name="heading">Maintenance &amp; Repair (M&amp;R)</x-slot>
    <x-slot name="title">Modul Maintenance — SILAMET</x-slot>

    @php
        $activeTab = request('status', 'open');
        $showWorkflowCols = in_array($activeTab, ['in_progress', 'resolved'], true);
        $tabs = [
            'open' => ['label' => 'Perlu Tindakan (Open)', 'count' => $counts['open'] ?? 0],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'count' => $counts['in_progress'] ?? 0],
            'resolved' => ['label' => 'Selesai & Uji Layak', 'count' => $counts['resolved'] ?? 0],
        ];
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto">

        <!-- Header Overview (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Tiket Kerusakan &amp; Perawatan Aset
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Kelola laporan kerusakan dari siswa di Kiosk dan jadwal maintenance seluruh barang Master Aset.
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('maintenance.schedules.index') }}"
                   class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Jadwal Maintenance Barang</span>
                    @if(($unscheduledMasterItemsCount ?? 0) > 0)
                        <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $unscheduledMasterItemsCount }} belum terjadwal
                        </span>
                    @elseif(($dueSchedulesCount ?? 0) > 0)
                        <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                            {{ $dueSchedulesCount }} jatuh tempo
                        </span>
                    @endif
                </a>
                <a href="{{ route('maintenance.create') }}"
                   class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Tiket Kerusakan</span>
                </a>
            </div>
        </div>

        @if($errors->any() && !in_array(old('_modal_mode'), ['start', 'complete'], true))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs space-y-1">
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
            <div class="bg-amber-50/80 border border-amber-200 rounded-2xl px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-start sm:items-center gap-2.5 text-xs text-amber-900">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        <span class="font-bold">{{ $unscheduledMasterItemsCount }} dari {{ $totalMasterItemsCount }} barang di Master Aset</span> belum memiliki jadwal maintenance berkala.
                    </span>
                </div>
                <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-1.5 bg-white hover:bg-amber-100/70 text-amber-900 border border-amber-300 text-xs font-semibold rounded-full whitespace-nowrap transition-colors shadow-sm">
                    <span>Atur Jadwal</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif

        <!-- Filter Tab Buttons (Capsule Pills) -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $activeTab === $key; @endphp
                <a href="{{ route('maintenance.index', ['status' => $key]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900 shadow-sm' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Table Card (Rounded-2xl ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Barang Bermasalah</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Deskripsi Kendala</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Pelapor</th>
                            @if($showWorkflowCols)
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Penanganan</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Biaya &amp; Struk</th>
                            @endif
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status Tiket</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tanggal Lapor</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($tickets as $ticket)
                            @php
                                $latestLog = $ticket->latestLog;
                                $estimatedDate = $latestLog?->estimated_finish_at
                                    ?? $ticket->logs->first(fn ($l) => $l->estimated_finish_at !== null)?->estimated_finish_at;
                                $totalCost = (float) $ticket->logs->sum('cost');
                                $receiptLogs = $ticket->logs->filter(fn ($l) => filled($l->receipt_path))->values();
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-neutral-900 text-xs font-heading">{{ $ticket->item->name ?? '-' }}</div>
                                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $ticket->item->item_code ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 max-w-md">
                                    <p class="text-xs text-neutral-700 line-clamp-2">{{ $ticket->issue_description }}</p>
                                    @if($showWorkflowCols && $latestLog)
                                        <p class="text-[11px] text-neutral-500 mt-0.5 line-clamp-1">
                                            {{ $latestLog->action_taken }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold text-neutral-800">{{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                                </td>

                                @if($showWorkflowCols)
                                    <td class="px-5 py-4">
                                        @if($latestLog)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-semibold {{ $latestLog->service_type->value === 'internal' ? 'bg-primary-50 text-primary-700 border-indigo-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                                {{ $latestLog->service_type->value === 'internal' ? 'Internal Sekolah' : 'Vendor Luar' }}
                                            </span>
                                            @if($latestLog->service_type->value === 'vendor_luar' && filled($latestLog->vendor_name))
                                                <div class="text-[11px] text-neutral-600 mt-0.5 font-medium">{{ $latestLog->vendor_name }}</div>
                                            @endif
                                            @if($estimatedDate)
                                                <div class="text-[10px] font-mono text-neutral-500 mt-0.5">
                                                    Est: {{ $estimatedDate->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-neutral-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($totalCost > 0)
                                            <div class="font-mono text-xs font-bold text-neutral-900">
                                                Rp {{ number_format($totalCost, 0, ',', '.') }}
                                            </div>
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
                                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-[10px] font-semibold text-neutral-700 transition-colors"
                                                           title="Buka Dokumen Struk PDF">
                                                            <svg class="w-3 h-3 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                            <span>{{ $receiptLabel }}</span>
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                                class="open-receipt-modal inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-primary-200 bg-primary-50/70 hover:bg-primary-100 text-[10px] font-semibold text-primary transition-colors"
                                                                data-receipt-url="{{ route('maintenance.receipt', $rLog->id) }}"
                                                                data-receipt-title="Struk Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}"
                                                                data-receipt-meta="{{ $rLog->service_type->label() }}{{ filled($rLog->vendor_name) ? ' · ' . $rLog->vendor_name : '' }}{{ (float) $rLog->cost > 0 ? ' · Rp ' . number_format($rLog->cost, 0, ',', '.') : '' }}"
                                                                title="Lihat Gambar Bukti Struk">
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

                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $tbadge = match($ticket->status->value) {
                                            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $tbadge }}">
                                        {{ $ticket->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if($ticket->status->value === 'open')
                                            <a href="{{ route('maintenance.edit', $ticket->id) }}"
                                               class="px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold shadow-2xs transition-colors"
                                               title="Edit Tiket" aria-label="Edit Tiket #{{ $ticket->id }}">
                                                Edit
                                            </a>
                                            <button type="button"
                                                    class="open-start-repair-modal px-3 py-1 rounded-full border border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold shadow-2xs transition-colors"
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
                                                Mulai
                                            </button>
                                        @elseif($ticket->status->value === 'in_progress')
                                            <a href="{{ route('maintenance.edit', $ticket->id) }}"
                                               class="px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold shadow-2xs transition-colors"
                                               title="Kelola Tiket" aria-label="Kelola Tiket #{{ $ticket->id }}">
                                                Kelola
                                            </a>
                                            <button type="button"
                                                    class="open-complete-repair-modal px-3 py-1 rounded-full border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-semibold shadow-2xs transition-colors"
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
                                                    title="Selesai &amp; Uji Layak"
                                                    aria-label="Selesai Tiket #{{ $ticket->id }}">
                                                Selesai
                                            </button>
                                        @else
                                            <a href="{{ route('maintenance.show', $ticket->id) }}"
                                               class="px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold shadow-2xs transition-colors"
                                               title="Detail Tiket" aria-label="Detail {{ $ticket->id }}">
                                                Detail
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showWorkflowCols ? 8 : 6 }}" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Tidak Ada Tiket di Kategori Ini</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Seluruh perangkat beroperasi normal atau belum ada pengajuan tiket baru.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>

    </div>

    @include('maintenance.partials._repair_modals', ['returnTo' => 'index'])
</x-app-layout>