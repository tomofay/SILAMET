<x-app-layout>
    <x-slot name="heading">Jadwal Perawatan Preventif</x-slot>
    <x-slot name="title">Perawatan Preventif — M&amp;R</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Jadwal', 'count' => $counts['all'] ?? 0],
            'due_soon' => ['label' => 'Mendekati (<= 7 Hari)', 'count' => $counts['due_soon'] ?? 0],
            'overdue' => ['label' => 'Terlambat', 'count' => $counts['overdue'] ?? 0],
            'paused' => ['label' => 'Dijeda', 'count' => $counts['paused'] ?? 0],
        ];
    @endphp

    <div class="space-y-4">

        <!-- Header Overview -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Penjadwalan Perawatan Preventif Berkala</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Atur siklus servis per aset (misal 3 bulan sekali) untuk kalibrasi berkala dan pengingat otomatis Telegram.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('maintenance.schedules.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Jadwal Preventif</span>
                </a>
            </div>
        </div>

        <!-- Module Sub-Navigation -->
        <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
            <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Tiket Kerusakan (Kuratif)</span>
            </a>
            <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Jadwal Perawatan Preventif</span>
            </a>
        </div>

        <!-- Filter Tab Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $filter === $key; @endphp
                <a href="{{ route('maintenance.schedules.index', ['filter' => $key]) }}"
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aset &amp; Lokasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kegiatan &amp; Siklus</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Servis Terakhir</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jadwal Berikutnya</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Countdown</th>
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
                                    'upcoming' => 'bg-brand-50 text-brand-700 border-brand-200',
                                    'paused' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                    default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                };
                            @endphp
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $schedule->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-sm">
                                    <div class="text-xs font-semibold text-neutral-900">{{ $schedule->title }}</div>
                                    <div class="text-[11px] font-mono text-neutral-500 mt-0.5">
                                        Setiap {{ $schedule->interval_months }} bulan
                                        @if($schedule->last_notified_stage)
                                            &middot; Notif: {{ strtoupper($schedule->last_notified_stage) }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ optional($schedule->last_serviced_at)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-neutral-900 whitespace-nowrap">
                                    {{ optional($schedule->next_due_date)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-block font-mono text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $countdownBadge }}">
                                        {{ $schedule->countdownLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('maintenance.schedules.mark-serviced', $schedule->id) }}" class="inline" onsubmit="return confirm('Catat servis selesai hari ini dan jadwalkan ulang {{ $schedule->interval_months }} bulan ke depan?')">
                                            @csrf
                                            <button type="submit" class="btn-icon-action text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 hover:border-emerald-200" title="Catat Servis Selesai Hari Ini" aria-label="Catat Servis Selesai untuk {{ $schedule->item->name ?? 'Aset' }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                        <a href="{{ route('maintenance.schedules.edit', $schedule->id) }}" class="btn-icon-action" title="Ubah Jadwal Preventif" aria-label="Edit Jadwal #{{ $schedule->id }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $schedule->id) }}" class="inline" onsubmit="return confirm('Hapus jadwal perawatan preventif ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon-action hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200" title="Hapus Jadwal" aria-label="Hapus Jadwal #{{ $schedule->id }}">
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
                                    <p class="font-semibold text-neutral-800 text-sm">Belum Ada Jadwal Perawatan Preventif</p>
                                    <p class="text-xs text-neutral-500 mt-1">Tambahkan jadwal servis berkala per aset agar sistem otomatis mengingatkan sebelum jatuh tempo.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $schedules->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

