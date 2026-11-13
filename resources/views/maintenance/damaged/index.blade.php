<x-app-layout>
    <x-slot name="heading">Barang Rusak Permanen (Write-Off)</x-slot>
    <x-slot name="title">Barang Rusak — Modul M&amp;R — SILAMET</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Catatan', 'count' => $counts['all'] ?? 0],
            'repair_ticket' => ['label' => 'Dari Tiket Perbaikan', 'count' => $counts['repair_ticket'] ?? 0],
            'preventive_maintenance' => ['label' => 'Dari Maintenance Preventif', 'count' => $counts['preventive_maintenance'] ?? 0],
        ];
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto">

        <!-- Header Overview (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Inventaris Barang Rusak Permanen
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Catatan unit tidak layak pakai hasil maintenance atau perbaikan. Stok Master Aset terkurangi otomatis.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Master Aset Terkurangi Otomatis</span>
            </div>
        </div>

        @include('maintenance.partials._module_tabs', [
            'activeBar' => 'damaged',
            'unscheduledMasterItemsCount' => $unscheduledMasterItemsCount,
            'damagedUnitsCount' => $totalUnitsDamaged,
            'inMaintenanceStageCount' => $inMaintenanceStageCount,
            'needsRepairStageCount' => $needsRepairStageCount,
        ])

        <!-- Ringkasan Statistik (3 Rounded-2xl Metric Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm">
                <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Total Unit Rusak Permanen</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold font-heading text-rose-700">{{ number_format($totalUnitsDamaged) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['all']) }} catatan)</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm">
                <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Dari Tiket Perbaikan</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold font-heading text-neutral-900">{{ number_format($repairTicketUnits) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['repair_ticket']) }} tiket)</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm">
                <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Dari Maintenance Preventif</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold font-heading text-neutral-900">{{ number_format($preventiveUnits) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['preventive_maintenance']) }} cek)</span>
                </div>
            </div>
        </div>

        <!-- Filter & Pencarian (Capsule Pills) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach($tabs as $key => $tab)
                    @php $isActive = $source === $key; @endphp
                    <a href="{{ route('maintenance.damaged.index', array_filter(['source' => $key, 'q' => $search !== '' ? $search : null])) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900 shadow-sm' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('maintenance.damaged.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="source" value="{{ $source }}" />
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Cari kode, nama, alasan..."
                           class="w-full pl-9 pr-4 py-2 rounded-full border border-neutral-200 bg-white text-xs focus:ring-1 focus:ring-primary focus:border-primary shadow-sm" />
                </div>
                <button type="submit" class="px-4 py-2 rounded-full bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold border border-neutral-200 shadow-sm transition-colors shrink-0">
                    Cari
                </button>
            </form>
        </div>

        <!-- Tabel Catatan Barang Rusak (Rounded-2xl Table) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Barang &amp; Kode Aset</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Sumber</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Alasan Kerusakan &amp; Penanganan</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Total Biaya</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Petugas &amp; Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($damagedAssets as $record)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-neutral-900 text-xs font-heading">{{ $record->item_name }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                            {{ $record->typeLabel() }}
                                        </span>
                                        @if(filled($record->item_category))
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                                {{ $record->item_category }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $record->item_code }}
                                        @if(filled($record->location))
                                            &middot; {{ $record->location }}
                                        @endif
                                        @if(filled($record->epc_tag))
                                            &middot; EPC: {{ $record->epc_tag }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $record->quantity }} unit
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($record->source === 'repair_ticket')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Tiket Perbaikan
                                        </span>
                                        @if($record->maintenance_ticket_id)
                                            <div class="mt-1">
                                                <a href="{{ route('maintenance.show', $record->maintenance_ticket_id) }}"
                                                   class="text-[11px] font-mono font-semibold text-primary-700 hover:underline">
                                                    Tiket #{{ $record->maintenance_ticket_id }}
                                                </a>
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                                            Jadwal Preventif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 max-w-sm text-xs text-neutral-700">
                                    <p class="font-medium text-neutral-900">{{ $record->damage_reason }}</p>
                                    @if(filled($record->action_taken))
                                        <p class="text-[11px] text-neutral-500 mt-0.5">{{ $record->action_taken }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-xs font-bold text-neutral-900">
                                    Rp {{ number_format($record->cost, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs">
                                    <div class="font-medium text-neutral-800">{{ $record->recordedBy?->name ?? 'Teknisi' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $record->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Unit Rusak Permanen</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Seluruh aset lab SIJA dalam kondisi operasional dan terpelihara baik.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($damagedAssets->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $damagedAssets->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>