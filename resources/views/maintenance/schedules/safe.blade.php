<x-app-layout>
    <x-slot name="heading">3. Barang Aman Hasil Maintenance</x-slot>
    <x-slot name="title">Barang Aman — M&amp;R</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Barang Aman', 'count' => $counts['all'] ?? 0],
            'due_soon' => ['label' => 'Mendekati Jadwal (<= 7 Hari)', 'count' => $counts['due_soon'] ?? 0],
            'overdue' => ['label' => 'Waktunya Cek Ulang', 'count' => $counts['overdue'] ?? 0],
            'paused' => ['label' => 'Dijeda', 'count' => $counts['paused'] ?? 0],
        ];
    @endphp

    <div class="space-y-4">

        @include('maintenance.schedules.partials._step_nav', [
            'currentStep' => 'aman',
            'workflowCounts' => $workflowCounts,
        ])

        <!-- Filter Tab Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $filter === $key; @endphp
                <a href="{{ route('maintenance.schedules.safe', ['filter' => $key]) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Tabel Barang Aman -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang (Master Aset)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kegiatan &amp; Hasil</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah Aman</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal Maintenance</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jadwal Berikutnya</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
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
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $schedule->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-sm">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-semibold text-neutral-900">{{ $schedule->title }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
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
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $safeCount }} unit
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ optional($schedule->maintenance_date ?? $schedule->last_serviced_at)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-neutral-900">
                                        {{ optional($schedule->next_due_date)->format('d/m/Y') }}
                                    </div>
                                    <span class="mt-0.5 inline-block font-mono text-[10px] font-semibold px-2 py-0.5 rounded border {{ $countdownBadge }}">
                                        {{ $schedule->countdownLabel() }} &middot; {{ $schedule->interval_months }} bln
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <form method="POST" action="{{ route('maintenance.schedules.restart', $schedule->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-neutral-50 text-neutral-800 border border-neutral-200 text-[11px] font-semibold transition-colors"
                                                    title="Mulai sesi maintenance ulang sekarang">
                                                <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Maintenance Lagi</span>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $schedule->id) }}" class="inline" onsubmit="return confirm('Hapus catatan barang aman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon-action hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200" title="Hapus" aria-label="Hapus #{{ $schedule->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Belum Ada Data Barang Aman</p>
                                    <p class="text-xs text-neutral-500 mt-1">Barang yang dinyatakan Aman pada tahap Sedang Di-Maintenance akan otomatis tampil di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

