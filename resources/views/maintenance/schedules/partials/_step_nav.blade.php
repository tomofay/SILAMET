@php
    $currentStep = $currentStep ?? 'buat';
    $wf = $workflowCounts ?? [];
    $steps = [
        [
            'key' => 'buat',
            'number' => '1',
            'label' => 'Buat Jadwal Maintenance',
            'desc' => 'Pilih paket barang, siklus & catatan',
            'href' => route('maintenance.schedules.index'),
            'badge' => ($wf['unscheduled_master'] ?? 0) > 0 ? ($wf['unscheduled_master'] . ' blm jadwal') : null,
            'badgeClass' => 'bg-amber-100 text-amber-800 border-amber-200',
        ],
        [
            'key' => 'progres',
            'number' => '2',
            'label' => 'Sedang Di-Maintenance',
            'desc' => 'Pilah barang aman vs rusak',
            'href' => route('maintenance.schedules.in-progress'),
            'badge' => (int) ($wf['in_maintenance'] ?? 0),
            'badgeClass' => ($wf['in_maintenance'] ?? 0) > 0
                ? 'bg-sky-100 text-sky-800 border-sky-200'
                : 'bg-neutral-100 text-neutral-600 border-neutral-200',
        ],
        [
            'key' => 'aman',
            'number' => '3',
            'label' => 'Barang Aman',
            'desc' => 'Layak pakai & jadwal berkala',
            'href' => route('maintenance.schedules.safe'),
            'badge' => (int) ($wf['safe'] ?? 0),
            'badgeClass' => ($wf['safe'] ?? 0) > 0
                ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
                : 'bg-neutral-100 text-neutral-600 border-neutral-200',
        ],
        [
            'key' => 'rusak',
            'number' => '4',
            'label' => 'Perlu Diperbaiki',
            'desc' => 'Ajukan ke tiket barang rusak',
            'href' => route('maintenance.schedules.repairs'),
            'badge' => (int) ($wf['needs_repair'] ?? 0),
            'badgeClass' => ($wf['needs_repair'] ?? 0) > 0
                ? 'bg-rose-100 text-rose-800 border-rose-200'
                : 'bg-neutral-100 text-neutral-600 border-neutral-200',
        ],
    ];
@endphp

<!-- Header Overview (Dribbble Orbix Studio Header) -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
            Penjadwalan Perawatan Preventif Berkala
        </h1>
        <p class="text-xs text-neutral-500 mt-0.5">
            Alur maintenance terstruktur: jadwalkan paket barang Master Aset, pilah kondisi saat dicek, lalu ajukan kerusakan ke tiket.
        </p>
    </div>
</div>

<!-- Module Top Sub-Navigation -->
@include('maintenance.partials._module_tabs', [
    'activeBar' => 'schedules',
    'workflowCounts' => $wf,
])

<!-- 4-Step Pipeline Navigation (Orbix Studio Guided Stepper) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    @foreach($steps as $step)
        @php $isCurrent = $currentStep === $step['key']; @endphp
        <a href="{{ $step['href'] }}"
           class="group rounded-2xl p-4 border transition-all duration-150 flex items-start justify-between gap-3 shadow-sm {{ $isCurrent ? 'bg-white border-neutral-900 ring-1 ring-neutral-900' : 'bg-white border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50/50' }}">
            <div class="flex items-start gap-3 min-w-0">
                <span class="w-7 h-7 rounded-xl font-mono text-xs font-bold flex items-center justify-center shrink-0 {{ $isCurrent ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700 border border-neutral-200' }}">
                    {{ $step['number'] }}
                </span>
                <div class="min-w-0">
                    <div class="text-xs font-bold truncate font-heading {{ $isCurrent ? 'text-neutral-900' : 'text-neutral-700' }}">
                        {{ $step['label'] }}
                    </div>
                    <div class="text-[11px] text-neutral-500 mt-0.5 truncate">{{ $step['desc'] }}</div>
                </div>
            </div>
            @if($step['badge'] !== null)
                <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold border shrink-0 {{ $step['badgeClass'] }}">
                    {{ $step['badge'] }}
                </span>
            @endif
        </a>
    @endforeach
</div>