@php
    $activeBar = $activeBar ?? 'tickets';
    $wf = $workflowCounts ?? [];
    $unschedCount = (int) ($unscheduledMasterItemsCount ?? ($wf['unscheduled_master'] ?? 0));
    $damagedBadgeCount = (int) ($damagedUnitsCount ?? ($wf['damaged_units'] ?? 0));
    $inMaintCount = (int) ($inMaintenanceStageCount ?? ($wf['in_maintenance'] ?? 0));
    $needsRepairCount = (int) ($needsRepairStageCount ?? ($wf['needs_repair'] ?? 0));
@endphp

<!-- Module Top Sub-Navigation (3 Bar Utama M&R) -->
<div class="flex items-center gap-2 border-b border-neutral-200 pb-3 flex-wrap">
    <a href="{{ route('maintenance.index') }}"
       class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors {{ $activeBar === 'tickets' ? 'bg-primary-50 text-primary-700 border-indigo-200' : 'text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border-neutral-200' }}">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>Tiket Kerusakan (Kuratif)</span>
    </a>

    <a href="{{ route('maintenance.schedules.index') }}"
       class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors {{ $activeBar === 'schedules' ? 'bg-primary-50 text-primary-700 border-indigo-200' : 'text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border-neutral-200' }}">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Jadwal Maintenance Barang (Preventif)</span>
        @if($unschedCount > 0 && $activeBar !== 'schedules')
            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                {{ $unschedCount }}
            </span>
        @endif
    </a>

    <a href="{{ route('maintenance.damaged.index') }}"
       class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors {{ $activeBar === 'damaged' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border-neutral-200' }}">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        <span>Barang Rusak</span>
        @if($damagedBadgeCount > 0)
            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold {{ $activeBar === 'damaged' ? 'bg-white text-rose-700 border border-rose-200' : 'bg-rose-100 text-rose-700 border border-rose-200' }}">
                {{ $damagedBadgeCount }}
            </span>
        @endif
    </a>

    @if($activeBar === 'tickets')
        @if($inMaintCount > 0)
            <a href="{{ route('maintenance.schedules.in-progress') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-sky-800 bg-sky-50 hover:bg-sky-100 border border-sky-200 transition-colors">
                <span>Sedang Di-Maintenance</span>
                <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-white text-sky-700 border border-sky-200">{{ $inMaintCount }}</span>
            </a>
        @endif
        @if($needsRepairCount > 0)
            <a href="{{ route('maintenance.schedules.repairs', ['filter' => 'pending']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-800 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                <span>Perlu Diajukan Tiket</span>
                <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-white text-rose-700 border border-rose-200">{{ $needsRepairCount }}</span>
            </a>
        @endif
    @endif
</div>

