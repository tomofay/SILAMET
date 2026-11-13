<x-app-layout>
    <x-slot name="heading">3. Barang Aman Hasil Maintenance</x-slot>
    <x-slot name="title">Barang Aman — M&amp;R — SILAMET</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Barang Aman', 'count' => $counts['all'] ?? 0],
            'due_soon' => ['label' => 'Mendekati Jadwal (<= 7 Hari)', 'count' => $counts['due_soon'] ?? 0],
            'overdue' => ['label' => 'Waktunya Cek Ulang', 'count' => $counts['overdue'] ?? 0],
            'paused' => ['label' => 'Dijeda', 'count' => $counts['paused'] ?? 0],
        ];
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto">

        @include('maintenance.schedules.partials._step_nav', [
            'currentStep' => 'aman',
            'workflowCounts' => $workflowCounts,
        ])

        <!-- Filter Tab Buttons (Capsule Pills) -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $filter === $key; @endphp
                <a href="{{ route('maintenance.schedules.safe', ['filter' => $key]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900 shadow-sm' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Tabel Barang Aman (Rounded-2xl ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Barang (Master Aset)</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kegiatan &amp; Hasil</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jumlah Aman</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tanggal Maintenance</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jadwal Berikutnya</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($schedules as $schedule)
                            @php
                                $urgency = $schedule->urgencyLevel();
                                $countdownBadge = match ($urgency) {
                                    'overdue' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'due_soon' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'upcoming' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'paused' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                    default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                };
                                $safeCount = max(1, (int) ($schedule->safe_quantity ?: $schedule->quantity));
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-neutral-900 text-xs font-heading">{{ $schedule->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 max-w-sm">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-semibold text-neutral-900">{{ $schedule->title }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aman
                                        </span>
                                    </div>
                                    @if(filled($schedule->notes))
                                        <p class="text-[11px] text-neutral-600 mt-1 whitespace-pre-line">{{ $schedule->notes }}</p>
                                    @endif
                                    @if($schedule->inspectedBy)
                                        <div class="text-[11px] text-neutral-500 mt-1">
                                            Diperiksa oleh: {{ $schedule->inspectedBy->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $safeCount }} unit
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ optional($schedule->maintenance_date ?? $schedule->last_serviced_at)->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-mono text-xs font-bold text-neutral-900">
                                        {{ optional($schedule->next_due_date)->format('d/m/Y') }}
                                    </div>
                                    <span class="mt-0.5 inline-block font-mono text-[10px] font-semibold px-2 py-0.5 rounded-full border {{ $countdownBadge }}">
                                        {{ $schedule->countdownLabel() }} &middot; {{ $schedule->interval_months }} bln
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a href="{{ route('maintenance.schedules.edit', $schedule->id) }}"
                                           class="px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold shadow-sm transition-colors"
                                           title="Ubah Jadwal" aria-label="Edit #{{ $schedule->id }}">
                                            Edit &rarr;
                                        </a>
                                    </div>
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
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Barang Berstatus Aman</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Barang yang selesai dicek di halaman Sedang Di-Maintenance akan tercatat di sini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>