<x-app-layout>
    <div class="space-y-6">

        <!-- Kartu Metrik -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Total Aset</p>
                        <p class="text-3xl font-bold text-neutral-800 mt-1">{{ number_format($stats['total_aset']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center text-2xl">📦</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Sedang Dipinjam</p>
                        <p class="text-3xl font-bold text-neutral-800 mt-1">{{ number_format($stats['dipinjam']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-info/10 flex items-center justify-center text-2xl">🔁</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Dalam Perbaikan (M&R)</p>
                        <p class="text-3xl font-bold text-neutral-800 mt-1">{{ number_format($stats['under_maintenance']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-warning/10 flex items-center justify-center text-2xl">🔧</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Insiden Gate Hari Ini</p>
                        <p class="text-3xl font-bold text-danger-dark mt-1">{{ number_format($stats['gate_insiden_hari_ini']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-danger/10 flex items-center justify-center text-2xl">🚨</div>
                </div>
            </div>
        </div>

        <!-- Panel Peringatan -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Overdue -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-800">⚠️ Peminjaman Overdue</h3>
                    <a href="{{ route('loans.index') }}" class="text-xs text-primary hover:underline">Lihat semua →</a>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse($overdueLoans as $loan)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-neutral-800 truncate">{{ $loan->item->name ?? '-' }}</p>
                                <p class="text-xs text-neutral-500 truncate">
                                    {{ $loan->user->name ?? '-' }}
                                    @if($loan->user?->phone)
                                        · {{ $loan->user->phone }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs text-danger font-semibold">
                                    Jatuh tempo {{ optional($loan->due_at)->translatedFormat('d M Y, H:i') }}
                                </span>
                                @if($loan->overdue_notified_at)
                                    <p class="text-[11px] text-success mt-0.5">✓ Sudah dinotifikasi</p>
                                @else
                                    <p class="text-[11px] text-warning-dark mt-0.5">Belum dinotifikasi</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-neutral-400 text-center">Tidak ada peminjaman yang terlambat. 👍</p>
                    @endforelse
                </div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-800">🔧 Tiket Perbaikan Aktif</h3>
                    <a href="{{ route('maintenance.index') }}" class="text-xs text-primary hover:underline">Lihat semua →</a>
                </div>
                <div class="divide-y divide-neutral-100">
                    @forelse($openTickets as $ticket)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-neutral-800 truncate">{{ $ticket->item->name ?? '-' }}</p>
                                <p class="text-xs text-neutral-500 line-clamp-1">{{ $ticket->issue_description }}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold px-2 py-1 rounded-full {{ $ticket->status->value === 'open' ? 'bg-danger/10 text-danger' : 'bg-warning/10 text-warning-dark' }}">
                                {{ $ticket->status->label() }}
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-neutral-400 text-center">Tidak ada tiket perbaikan aktif.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
