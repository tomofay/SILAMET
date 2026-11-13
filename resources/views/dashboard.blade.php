<x-app-layout>
    <x-slot name="heading">Dashboard Utama</x-slot>
    <x-slot name="title">Dashboard Utama</x-slot>

    <div class="space-y-4">

        <!-- Header Command Bar -->
        <div class="bg-neutral-900 border border-neutral-800 rounded-xl p-5 sm:p-6 text-white">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight font-heading text-white">
                        Selamat Datang, {{ auth()->user()?->name }}
                    </h2>
                    <p class="text-neutral-400 text-xs sm:text-sm mt-1 max-w-2xl">
                        Pantau sirkulasi alat praktikum, tiket perbaikan, serta pembaca UHF RFID Gate Security pintu gerbang lab secara terintegrasi.
                    </p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <a href="{{ route('items.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-500 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Aset</span>
                    </a>
                    <a href="{{ route('gate-logs.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 border border-neutral-700 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Log Gate RFID</span>
                    </a>
                    <a href="{{ route('kiosk.home') }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Buka Kiosk Siswa</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Overview Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            
            <!-- Metric 1: Total Aset -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Total Aset Terdata</span>
                        <div class="text-2xl font-bold text-neutral-900 mt-1 font-mono tabular-nums">
                            {{ number_format($stats['total_aset']) }}
                        </div>
                    </div>
                    <div class="p-2 rounded-lg bg-primary/10 text-primary-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Katalog alat lab</span>
                    <a href="{{ route('items.index') }}" class="font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                        <span>Kelola</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Metric 2: Sedang Dipinjam -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Sedang Dipinjam</span>
                        <div class="text-2xl font-bold text-brand-700 mt-1 font-mono tabular-nums">
                            {{ number_format($stats['dipinjam']) }}
                        </div>
                    </div>
                    <div class="p-2 rounded-lg bg-brand-50 text-brand-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Digunakan siswa</span>
                    <a href="{{ route('loans.index') }}" class="font-semibold text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                        <span>Sirkulasi</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Metric 3: Dalam Perbaikan -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Dalam Perbaikan (M&R)</span>
                        <div class="text-2xl font-bold text-amber-700 mt-1 font-mono tabular-nums">
                            {{ number_format($stats['under_maintenance']) }}
                        </div>
                    </div>
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Tiket perbaikan aktif</span>
                    <a href="{{ route('maintenance.index') }}" class="font-semibold text-amber-700 hover:text-amber-800 inline-flex items-center gap-1">
                        <span>Cek Tiket</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Metric 4: Insiden Gate Hari Ini -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border {{ $stats['gate_insiden_hari_ini'] > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200' }} flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Insiden Gate Hari Ini</span>
                        <div class="text-2xl font-bold text-rose-700 mt-1 font-mono tabular-nums flex items-center gap-2">
                            <span>{{ number_format($stats['gate_insiden_hari_ini']) }}</span>
                            @if($stats['gate_insiden_hari_ini'] > 0)
                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200 uppercase">
                                    ALARM
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-2 rounded-lg bg-rose-50 text-rose-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Pelanggaran RFID</span>
                    <a href="{{ route('gate-logs.index') }}" class="font-semibold text-rose-700 hover:text-rose-800 inline-flex items-center gap-1">
                        <span>Log Kamera</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

        </div>

        <!-- Data Panels: Overdue & Maintenance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- Panel 1: Peminjaman Overdue -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden flex flex-col">
                <div class="px-4 py-3.5 border-b border-neutral-200 flex items-center justify-between bg-neutral-50">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-rose-50 text-rose-600 border border-rose-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Peminjaman Melewati Batas (Overdue)</h3>
                            <p class="text-[11px] text-neutral-500">Perlu konfirmasi pengembalian segera</p>
                        </div>
                    </div>
                    <a href="{{ route('loans.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                        <span>Semua</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div class="divide-y divide-neutral-100 max-h-80 overflow-y-auto">
                    @forelse($overdueLoans as $loan)
                        <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-neutral-50 transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ $loan->item->name ?? '-' }}</p>
                                    @if($loan->item?->item_code)
                                        <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">{{ $loan->item->item_code }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500">
                                    <span class="font-medium text-neutral-700">{{ $loan->user->name ?? '-' }}</span>
                                    @if($loan->user?->nis_nip)
                                        <span class="font-mono text-[11px] text-neutral-400">({{ $loan->user->nis_nip }})</span>
                                    @endif
                                    @if($loan->user?->phone)
                                        <span class="text-neutral-300">|</span>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $loan->user->phone) }}" target="_blank" class="font-mono text-[11px] text-emerald-700 hover:underline">
                                            {{ $loan->user->phone }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block font-mono text-[11px] font-semibold text-rose-700 px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200">
                                    {{ optional($loan->due_at)->format('d/m H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 inline-flex items-center justify-center mb-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-neutral-800">Tidak Ada Keterlambatan</p>
                            <p class="text-xs text-neutral-500 mt-0.5">Seluruh peminjaman berada dalam batas waktu aktif.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel 2: Tiket Perbaikan Aktif (M&R) -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden flex flex-col">
                <div class="px-4 py-3.5 border-b border-neutral-200 flex items-center justify-between bg-neutral-50">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-amber-50 text-amber-600 border border-amber-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Tiket Perbaikan Aktif (M&R)</h3>
                            <p class="text-[11px] text-neutral-500">Antrean aset membutuhkan tindakan teknisi</p>
                        </div>
                    </div>
                    <a href="{{ route('maintenance.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                        <span>Modul M&R</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div class="divide-y divide-neutral-100 max-h-80 overflow-y-auto">
                    @forelse($openTickets as $ticket)
                        <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-neutral-50 transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ $ticket->item->name ?? '-' }}</p>
                                    @if($ticket->item?->item_code)
                                        <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">{{ $ticket->item->item_code }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-neutral-500 line-clamp-1 mt-0.5">{{ $ticket->issue_description }}</p>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $ticket->status->value === 'open' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                    {{ $ticket->status->label() }}
                                </span>
                                <a href="{{ route('maintenance.edit', $ticket->id) }}" class="btn-icon-action" title="Proses Tindakan Perbaikan" aria-label="Proses Tindakan Perbaikan">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 inline-flex items-center justify-center mb-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-neutral-800">Kondisi Alat Prima</p>
                            <p class="text-xs text-neutral-500 mt-0.5">Tidak ada tiket perbaikan yang tertunda saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
