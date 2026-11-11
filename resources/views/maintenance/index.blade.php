<x-app-layout>
    <x-slot name="heading">Maintenance & Repair (M&R)</x-slot>
    <x-slot name="title">Modul Maintenance</x-slot>

    @php
        $activeTab = request('status', 'open');
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
                <p class="text-xs text-neutral-500 mt-0.5">Kelola laporan kerusakan dari siswa di Kiosk dan jadwal pemeliharaan berkala teknisi lab.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Jadwal Preventif</span>
                </a>
                <a href="{{ route('maintenance.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Tiket Kerusakan</span>
                </a>
            </div>
        </div>

        <!-- Module Sub-Navigation -->
        <div class="flex items-center gap-2 border-b border-neutral-200 pb-3">
            <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 border border-primary-200">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Tiket Kerusakan (Kuratif)</span>
            </a>
            <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Jadwal Perawatan Preventif</span>
            </a>
        </div>

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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status Tiket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal Lapor</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($tickets as $ticket)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $ticket->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $ticket->item->item_code ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 max-w-md">
                                    <p class="text-xs text-neutral-700 line-clamp-2">{{ $ticket->issue_description }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-xs font-medium text-neutral-800">{{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                                </td>
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
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ optional($ticket->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <a href="{{ route('maintenance.show', $ticket->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Tiket" aria-label="Detail Tiket #{{ $ticket->id }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('maintenance.edit', $ticket->id) }}" class="btn-icon-action" title="Catat Tindakan & Edit Tiket" aria-label="Edit Tiket #{{ $ticket->id }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        @if($ticket->status->value === 'open')
                                            <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="btn-icon-action text-amber-600 hover:text-amber-800 hover:bg-amber-50 hover:border-amber-200" title="Mulai Perbaikan" aria-label="Mulai Perbaikan Tiket #{{ $ticket->id }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @elseif($ticket->status->value === 'in_progress')
                                            <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" class="btn-icon-action text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 hover:border-emerald-200" title="Selesai & Uji Layak" aria-label="Selesaikan Tiket #{{ $ticket->id }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
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
</x-app-layout>
