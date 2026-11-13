<x-app-layout>
    <x-slot name="heading">Barang Rusak Permanen (Write-Off)</x-slot>
    <x-slot name="title">Barang Rusak — Modul M&amp;R</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Catatan', 'count' => $counts['all'] ?? 0],
            'repair_ticket' => ['label' => 'Dari Tiket Perbaikan', 'count' => $counts['repair_ticket'] ?? 0],
            'preventive_maintenance' => ['label' => 'Dari Maintenance Preventif', 'count' => $counts['preventive_maintenance'] ?? 0],
        ];
    @endphp

    <div class="space-y-4">

        <!-- Header Overview -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Inventaris Barang Rusak Permanen</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Catatan unit yang tidak layak pakai dari hasil maintenance maupun perbaikan. Stok di Master Aset otomatis dikurangi atau dihapus.</p>
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 shrink-0">
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

        <!-- Ringkasan Statistik -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white rounded-xl p-4 border border-neutral-200">
                <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">Total Unit Rusak Permanen</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-rose-700">{{ number_format($totalUnitsDamaged) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['all']) }} catatan)</span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 border border-neutral-200">
                <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">Dari Tiket Perbaikan</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-neutral-900">{{ number_format($repairTicketUnits) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['repair_ticket']) }} tiket)</span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 border border-neutral-200">
                <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">Dari Maintenance Preventif</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-neutral-900">{{ number_format($preventiveUnits) }}</span>
                    <span class="text-xs text-neutral-500">unit ({{ number_format($counts['preventive_maintenance']) }} pengecekan)</span>
                </div>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach($tabs as $key => $tab)
                    @php $isActive = $source === $key; @endphp
                    <a href="{{ route('maintenance.damaged.index', array_filter(['source' => $key, 'q' => $search !== '' ? $search : null])) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('maintenance.damaged.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="source" value="{{ $source }}" />
                <input type="search"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Cari kode, nama barang, alasan..."
                       class="w-full sm:w-64 rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary" />
                <button type="submit" class="px-3 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    Cari
                </button>
            </form>
        </div>

        <!-- Tabel Catatan Barang Rusak -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang &amp; Kode Aset</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Sumber</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Alasan Kerusakan &amp; Penanganan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Biaya</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Petugas &amp; Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($damagedAssets as $record)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-semibold text-neutral-900 text-xs">{{ $record->item_name }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                            {{ $record->typeLabel() }}
                                        </span>
                                        @if(filled($record->item_category))
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
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
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $record->quantity }} unit
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($record->source === 'repair_ticket')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
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
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                                            Maintenance Preventif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-md">
                                    <p class="text-xs text-neutral-800 leading-relaxed">{{ $record->damage_reason }}</p>
                                    @if(filled($record->service_type) || filled($record->vendor_name))
                                        <div class="text-[11px] text-neutral-500 mt-1">
                                            Penanganan:
                                            {{ $record->service_type === 'vendor_luar' ? 'Vendor Luar' : 'Internal Sekolah' }}
                                            @if(filled($record->vendor_name))
                                                ({{ $record->vendor_name }})
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if((float) $record->repair_cost > 0)
                                        <span class="font-mono text-xs font-semibold text-neutral-900">
                                            Rp {{ number_format($record->repair_cost, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-neutral-400">Tanpa biaya</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs font-medium text-neutral-800">{{ $record->recordedBy->name ?? 'Teknisi Lab' }}</div>
                                    <div class="text-[11px] font-mono text-neutral-500 mt-0.5">
                                        {{ optional($record->damaged_at)->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Tidak Ada Barang Rusak Permanen</p>
                                    <p class="text-xs text-neutral-500 mt-1">Unit yang dinyatakan rusak permanen saat maintenance atau perbaikan akan tercatat otomatis di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($damagedAssets->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $damagedAssets->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

