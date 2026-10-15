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
                    <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Sedang Dipinjam</p>
                        <p class="text-3xl font-bold text-neutral-800 mt-1">{{ number_format($stats['dipinjam']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-info/10 text-info flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Dalam Perbaikan (M&R)</p>
                        <p class="text-3xl font-bold text-neutral-800 mt-1">{{ number_format($stats['under_maintenance']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-warning/10 text-warning-dark flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-neutral-500 font-medium">Insiden Gate Hari Ini</p>
                        <p class="text-3xl font-bold text-danger-dark mt-1">{{ number_format($stats['gate_insiden_hari_ini']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-danger/10 text-danger flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Peringatan -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Overdue -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-warning-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Peminjaman Overdue</span>
                    </h3>
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
                                    <p class="text-[11px] text-success mt-0.5 flex items-center justify-end gap-1">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Sudah dinotifikasi</span>
                                    </p>
                                @else
                                    <p class="text-[11px] text-warning-dark mt-0.5">Belum dinotifikasi</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-neutral-400 text-center">Tidak ada peminjaman yang terlambat saat ini.</p>
                    @endforelse
                </div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h3 class="font-semibold text-neutral-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Tiket Perbaikan Aktif</span>
                    </h3>
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
