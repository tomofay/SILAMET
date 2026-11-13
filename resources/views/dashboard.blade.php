<x-app-layout>
    <x-slot name="heading">Dashboard Utama</x-slot>
    <x-slot name="title">Dashboard Utama — SILAMET</x-slot>

    <div class="space-y-4">

        <!-- Header Command Bar (Data-Dense SaaS Hero ala Orbix Studio Seller Admin UI) -->
        <div class="bg-neutral-900 border border-neutral-800 rounded-xl p-5 sm:p-6 text-white shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            OPERASIONAL SIAGA
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-mono text-neutral-400 bg-neutral-800/80 border border-neutral-700">
                            {{ now()->format('d M Y') }} &middot; Lab SIJA SMKN 1 Cibinong
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight font-heading text-white">
                        Pusat Kendali Operasional Laboratorium
                    </h2>
                    <p class="text-neutral-400 text-xs sm:text-sm max-w-2xl">
                        Selamat datang, <span class="text-white font-semibold">{{ auth()->user()?->name }}</span>. Pantau alur sirkulasi alat praktikum, pemeliharaan berkala, serta keamanan gerbang UHF RFID secara terintegrasi.
                    </p>
                </div>

                <!-- Quick Action Buttons Hierarchy -->
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('items.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-600 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Aset</span>
                    </a>
                    <a href="{{ route('kiosk.home') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Buka Kiosk Siswa</span>
                    </a>
                    <a href="{{ route('gate-logs.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 border border-neutral-700 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Log Gate RFID</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 border border-neutral-700 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Laporan</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Summary Cards Row (4 Grid Kartu Data-Dense ala Seller Center UI) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <!-- Card 1: Total Aset Terdata -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between hover:border-neutral-300 transition-colors">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Total Aset Terdata</span>
                            <div class="text-2xl font-bold text-neutral-900 font-mono tracking-tight">
                                {{ number_format($stats['total_aset']) }}
                            </div>
                        </div>
                        <div class="p-2 rounded-xl bg-primary-50 text-primary-700 border border-indigo-100 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $stats['readiness_percent'] }}% Siap Pakai
                        </span>
                        <span class="text-[11px] text-neutral-500 font-mono">{{ number_format($stats['total_units']) }} unit fisik</span>
                    </div>

                    <!-- Mini Segmented Breakdown -->
                    <div class="mt-3 grid grid-cols-3 gap-1.5 text-center text-[10px] font-mono border-t border-neutral-100 pt-2.5">
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Tersedia</span>
                            <span class="font-bold text-emerald-700">{{ $stats['tersedia'] }}</span>
                        </div>
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Dipinjam</span>
                            <span class="font-bold text-sky-700">{{ $stats['dipinjam'] }}</span>
                        </div>
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Perbaikan</span>
                            <span class="font-bold text-amber-700">{{ $stats['under_maintenance'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Katalog alat praktikum</span>
                    <a href="{{ route('items.index') }}" class="font-semibold text-primary hover:text-primary-700 inline-flex items-center gap-1 group">
                        <span>Kelola</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Card 2: Sedang Dipinjam -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between hover:border-neutral-300 transition-colors">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Sedang Dipinjam</span>
                            <div class="text-2xl font-bold text-sky-700 font-mono tracking-tight">
                                {{ number_format($stats['dipinjam']) }}
                            </div>
                        </div>
                        <div class="p-2 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        @if($stats['overdue_count'] > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                {{ $stats['overdue_count'] }} Terlambat
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Seluruhnya Tepat Waktu
                            </span>
                        @endif
                        <span class="text-[11px] text-neutral-500 font-mono">Kiosk Mandiri</span>
                    </div>

                    <!-- Mini Segmented Breakdown -->
                    <div class="mt-3 grid grid-cols-2 gap-1.5 text-center text-[10px] font-mono border-t border-neutral-100 pt-2.5">
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Pinjam Hari Ini</span>
                            <span class="font-bold text-neutral-800">{{ $stats['today_loans_count'] }} transaksi</span>
                        </div>
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Kembali Hari Ini</span>
                            <span class="font-bold text-neutral-800">{{ $stats['today_returns_count'] }} unit</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Peminjaman aktif siswa</span>
                    <a href="{{ route('loans.index') }}" class="font-semibold text-sky-700 hover:text-sky-800 inline-flex items-center gap-1 group">
                        <span>Sirkulasi</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Card 3: Dalam Perbaikan (M&R) -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col justify-between hover:border-neutral-300 transition-colors">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Dalam Perbaikan (M&amp;R)</span>
                            <div class="text-2xl font-bold text-amber-700 font-mono tracking-tight">
                                {{ number_format($stats['under_maintenance']) }}
                            </div>
                        </div>
                        <div class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        @if($stats['open_tickets_count'] > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                {{ $stats['open_tickets_count'] }} Butuh Tindakan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Antrean Bersih
                            </span>
                        @endif
                        <span class="text-[11px] text-neutral-500 font-mono">{{ $stats['in_progress_tickets_count'] }} sedang proses</span>
                    </div>

                    <!-- Mini Segmented Breakdown -->
                    <div class="mt-3 grid grid-cols-2 gap-1.5 text-center text-[10px] font-mono border-t border-neutral-100 pt-2.5">
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Tiket Open</span>
                            <span class="font-bold text-neutral-800">{{ $stats['open_tickets_count'] }} antrean</span>
                        </div>
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Jadwal Preventif</span>
                            <span class="font-bold text-neutral-800">{{ $stats['preventive_due_soon_count'] }} dekat</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Antrean tiket teknisi lab</span>
                    <a href="{{ route('maintenance.index') }}" class="font-semibold text-amber-700 hover:text-amber-800 inline-flex items-center gap-1 group">
                        <span>Cek Tiket</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Card 4: Insiden Gate Hari Ini -->
            <div class="bg-white rounded-xl p-4 sm:p-5 border {{ $stats['gate_insiden_hari_ini'] > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200' }} flex flex-col justify-between hover:border-neutral-300 transition-colors">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Insiden Gate Hari Ini</span>
                            <div class="text-2xl font-bold text-rose-700 font-mono tracking-tight flex items-center gap-2">
                                <span>{{ number_format($stats['gate_insiden_hari_ini']) }}</span>
                                @if($stats['gate_insiden_hari_ini'] > 0)
                                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200 uppercase tracking-wider">
                                        ALARM
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="p-2 rounded-xl {{ $stats['gate_insiden_hari_ini'] > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }} shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-mono text-[11px] font-semibold {{ $stats['gate_insiden_hari_ini'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                            Kepatuhan {{ $stats['gate_compliance_rate'] }}%
                        </span>
                        <span class="text-[11px] text-neutral-500 font-mono">{{ number_format($stats['gate_total_hari_ini']) }} scan</span>
                    </div>

                    <!-- Mini Segmented Breakdown -->
                    <div class="mt-3 grid grid-cols-2 gap-1.5 text-center text-[10px] font-mono border-t border-neutral-100 pt-2.5">
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Akses Sah</span>
                            <span class="font-bold text-emerald-700">{{ max(0, $stats['gate_total_hari_ini'] - $stats['gate_insiden_hari_ini']) }} scan</span>
                        </div>
                        <div class="p-1 rounded bg-neutral-50 border border-neutral-100">
                            <span class="text-neutral-500 block">Pelanggaran</span>
                            <span class="font-bold text-rose-700">{{ $stats['gate_insiden_hari_ini'] }} alarm</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-xs">
                    <span class="text-neutral-500">Antena HW-VX6330</span>
                    <a href="{{ route('gate-logs.index') }}" class="font-semibold text-rose-700 hover:text-rose-800 inline-flex items-center gap-1 group">
                        <span>Log Kamera</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

        </div>

        <!-- Section 2: Stepper / Flow Alur Manajemen Laboratorium (Signature ala Orbix Studio Product Flow) -->
        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <div class="flex items-center justify-between gap-4 mb-3 pb-2.5 border-b border-neutral-100 flex-wrap">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-800 font-heading">Alur Siklus Inventaris &amp; Keamanan Terintegrasi</h3>
                    <p class="text-[11px] text-neutral-500">Rantai kendali alat dari pendaftaran katalog, sirkulasi mandiri, pemeliharaan berkala, hingga proteksi gate RFID.</p>
                </div>
                <span class="text-xs font-mono font-medium text-neutral-400">4 Tahapan Sistem</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Step 1 -->
                <a href="{{ route('items.index') }}" class="group p-3 rounded-lg border border-neutral-200 hover:border-primary/50 hover:bg-neutral-50/70 transition-colors flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-neutral-100 text-neutral-700 font-mono font-bold text-xs flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        1
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-neutral-900 truncate">Registrasi &amp; Barcode</div>
                        <div class="text-[11px] text-neutral-500 truncate mt-0.5">Kelompok barang &amp; EPC Tag</div>
                        <div class="mt-1 font-mono text-[11px] font-semibold text-primary">{{ $stats['total_aset'] }} alat terdata &rarr;</div>
                    </div>
                </a>

                <!-- Step 2 -->
                <a href="{{ route('loans.index') }}" class="group p-3 rounded-lg border border-neutral-200 hover:border-sky-300 hover:bg-neutral-50/70 transition-colors flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-neutral-100 text-neutral-700 font-mono font-bold text-xs flex items-center justify-center shrink-0 group-hover:bg-sky-600 group-hover:text-white transition-colors">
                        2
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-neutral-900 truncate">Sirkulasi Kiosk Siswa</div>
                        <div class="text-[11px] text-neutral-500 truncate mt-0.5">Self-service barcode &amp; foto</div>
                        <div class="mt-1 font-mono text-[11px] font-semibold text-sky-700">{{ $stats['dipinjam'] }} unit aktif &rarr;</div>
                    </div>
                </a>

                <!-- Step 3 -->
                <a href="{{ route('maintenance.index') }}" class="group p-3 rounded-lg border border-neutral-200 hover:border-amber-300 hover:bg-neutral-50/70 transition-colors flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-neutral-100 text-neutral-700 font-mono font-bold text-xs flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        3
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-neutral-900 truncate">Pemeliharaan &amp; M&amp;R</div>
                        <div class="text-[11px] text-neutral-500 truncate mt-0.5">Jadwal preventif &amp; tiket perbaikan</div>
                        <div class="mt-1 font-mono text-[11px] font-semibold text-amber-700">{{ $stats['under_maintenance'] }} unit servis &rarr;</div>
                    </div>
                </a>

                <!-- Step 4 -->
                <a href="{{ route('gate-logs.index') }}" class="group p-3 rounded-lg border border-neutral-200 hover:border-rose-300 hover:bg-neutral-50/70 transition-colors flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-neutral-100 text-neutral-700 font-mono font-bold text-xs flex items-center justify-center shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                        4
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-neutral-900 truncate">Gate Security UHF RFID</div>
                        <div class="text-[11px] text-neutral-500 truncate mt-0.5">Deteksi sirine &amp; kamera gerbang</div>
                        <div class="mt-1 font-mono text-[11px] font-semibold text-rose-700">{{ $stats['gate_total_hari_ini'] }} scan hari ini &rarr;</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Section 3: Dual Workstation Layout (Split Grid 8 / 4 ala SaaS Seller Admin UI) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            <!-- Left / Main Operational Column (lg:col-span-8) -->
            <div class="lg:col-span-8 space-y-4">

                <!-- Panel 1: Sirkulasi & Peminjaman Siswa (Interactive Filter Tabs) -->
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm" x-data="{ tab: 'overdue' }">
                    <div class="p-4 sm:p-5 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-neutral-50/60">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">
                                    Papan Sirkulasi Peminjaman Mandiri
                                </h3>
                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                    Kiosk Siswa
                                </span>
                            </div>
                            <p class="text-xs text-neutral-500 mt-0.5">Daftar transaksi aktif dan pemantauan batas pengembalian alat praktikum.</p>
                        </div>

                        <!-- Tab Selector ala Seller Admin UI -->
                        <div class="inline-flex p-1 rounded-lg bg-neutral-100 border border-neutral-200 text-xs font-semibold shrink-0">
                            <button type="button"
                                    @click="tab = 'overdue'"
                                    :class="tab === 'overdue' ? 'bg-white text-rose-700 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                                    class="px-2.5 py-1 rounded-md transition-all inline-flex items-center gap-1.5">
                                <span>Terlambat</span>
                                @if($stats['overdue_count'] > 0)
                                    <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-rose-100 text-rose-800">
                                        {{ $stats['overdue_count'] }}
                                    </span>
                                @endif
                            </button>

                            <button type="button"
                                    @click="tab = 'active'"
                                    :class="tab === 'active' ? 'bg-white text-primary shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                                    class="px-2.5 py-1 rounded-md transition-all inline-flex items-center gap-1.5">
                                <span>Aktif Hari Ini</span>
                                <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-neutral-200 text-neutral-800">
                                    {{ $activeLoans->count() }}
                                </span>
                            </button>

                            <button type="button"
                                    @click="tab = 'returned'"
                                    :class="tab === 'returned' ? 'bg-white text-emerald-700 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                                    class="px-2.5 py-1 rounded-md transition-all inline-flex items-center gap-1.5">
                                <span>Kembali Hari Ini</span>
                                <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-neutral-200 text-neutral-800">
                                    {{ $todayReturnedLoans->count() }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Tab 1: Overdue Loans -->
                    <div x-show="tab === 'overdue'" class="divide-y divide-neutral-100">
                        @forelse($overdueLoans as $loan)
                            <div class="p-4 hover:bg-neutral-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-neutral-900 text-sm">{{ $loan->item->name ?? '-' }}</span>
                                        @if($loan->item?->item_code)
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                {{ $loan->item->item_code }}
                                            </span>
                                        @endif
                                        @if($loan->item?->category)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                                {{ $loan->item->category }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5 text-xs text-neutral-600 flex-wrap">
                                        <span class="font-semibold text-neutral-800">{{ $loan->user->name ?? 'Siswa' }}</span>
                                        @if($loan->user?->nis_nip)
                                            <span class="font-mono text-neutral-400">({{ $loan->user->nis_nip }})</span>
                                        @endif
                                        @if($loan->user?->phone)
                                            <span class="text-neutral-300">|</span>
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $loan->user->phone) }}" target="_blank" class="font-mono text-[11px] font-semibold text-emerald-700 hover:underline inline-flex items-center gap-1">
                                                <span>WA: {{ $loan->user->phone }}</span>
                                            </a>
                                        @endif
                                        <span class="text-neutral-300">|</span>
                                        <span class="text-neutral-500 font-mono text-[11px]">Pinjam: {{ optional($loan->borrowed_at)->format('d/m H:i') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <div class="text-right">
                                        <span class="inline-block font-mono text-xs font-bold text-rose-700 px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200">
                                            Jatuh Tempo: {{ optional($loan->due_at)->format('d/m H:i') }}
                                        </span>
                                        <div class="text-[10px] text-rose-600 font-semibold mt-0.5">Melewati batas pinjam</div>
                                    </div>
                                    <a href="{{ route('loans.index') }}" class="px-2.5 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 transition-colors shadow-sm">
                                        Cek Sirkulasi
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-neutral-500">
                                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 inline-flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-neutral-800">Tidak Ada Peminjaman Terlambat</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Seluruh peminjaman saat ini berada dalam batas waktu penggunaan resmi.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Tab 2: Active Loans -->
                    <div x-show="tab === 'active'" style="display: none;" class="divide-y divide-neutral-100">
                        @forelse($activeLoans as $loan)
                            <div class="p-4 hover:bg-neutral-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-neutral-900 text-sm">{{ $loan->item->name ?? '-' }}</span>
                                        @if($loan->item?->item_code)
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                {{ $loan->item->item_code }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5 text-xs text-neutral-600 flex-wrap">
                                        <span class="font-semibold text-neutral-800">{{ $loan->user->name ?? 'Siswa' }}</span>
                                        @if($loan->user?->nis_nip)
                                            <span class="font-mono text-neutral-400">({{ $loan->user->nis_nip }})</span>
                                        @endif
                                        <span class="text-neutral-300">|</span>
                                        <span class="text-neutral-500 font-mono text-[11px]">Durasi: {{ $loan->usage_duration?->label() ?? 'Standar' }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <div class="text-right font-mono text-xs">
                                        <span class="text-neutral-500">Kembali:</span>
                                        <span class="font-semibold text-neutral-800">{{ optional($loan->due_at)->format('d/m H:i') }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                        Sedang Digunakan
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-neutral-500">
                                <p class="text-sm font-semibold text-neutral-800">Belum Ada Peminjaman Aktif</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Siswa dapat meminjam alat melalui Kiosk Layanan Mandiri.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Tab 3: Today Returned Loans -->
                    <div x-show="tab === 'returned'" style="display: none;" class="divide-y divide-neutral-100">
                        @forelse($todayReturnedLoans as $loan)
                            <div class="p-4 hover:bg-neutral-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-neutral-900 text-sm">{{ $loan->item->name ?? '-' }}</span>
                                        @if($loan->item?->item_code)
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                {{ $loan->item->item_code }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5 text-xs text-neutral-600">
                                        <span class="font-semibold text-neutral-800">{{ $loan->user->name ?? 'Siswa' }}</span>
                                        <span class="text-neutral-300">|</span>
                                        <span class="text-neutral-500 font-mono text-[11px]">Kembali: {{ optional($loan->returned_at)->format('H:i') }} WIB</span>
                                    </div>
                                </div>

                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Selesai Dikembalikan
                                </span>
                            </div>
                        @empty
                            <div class="p-8 text-center text-neutral-500">
                                <p class="text-sm font-semibold text-neutral-800">Belum Ada Pengembalian Hari Ini</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Riwayat pengembalian yang disetujui hari ini akan muncul di sini.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="p-3 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between text-xs">
                        <span class="text-neutral-500 font-mono text-[11px]">Sinkronisasi Kiosk Mandiri Real-Time</span>
                        <a href="{{ route('loans.index') }}" class="font-semibold text-primary hover:text-primary-700 inline-flex items-center gap-1">
                            <span>Buka Manajemen Sirkulasi Lengkap</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Panel 2: Pipeline Pemeliharaan & Tiket Perbaikan (M&R Active Stream) -->
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm">
                    <div class="p-4 sm:p-5 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-neutral-50/60">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">
                                    Antrean Perbaikan &amp; Pemeliharaan (M&amp;R)
                                </h3>
                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ $openTickets->count() }} Berjalan
                                </span>
                            </div>
                            <p class="text-xs text-neutral-500 mt-0.5">Penanganan kendala alat praktikum dan verifikasi kelayakan teknis.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('maintenance.create') }}" class="px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-600 text-white text-xs font-semibold border border-primary/40 transition-colors inline-flex items-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Buat Tiket</span>
                            </a>
                            <a href="{{ route('maintenance.index') }}" class="px-3 py-1.5 rounded-lg bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold border border-neutral-200 transition-colors">
                                Semua Tiket
                            </a>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        @forelse($openTickets as $ticket)
                            <div class="p-4 hover:bg-neutral-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-neutral-900 text-sm">{{ $ticket->item->name ?? '-' }}</span>
                                        @if($ticket->item?->item_code)
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                {{ $ticket->item->item_code }}
                                            </span>
                                        @endif
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $ticket->status->value === 'open' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                            {{ $ticket->status->label() }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-neutral-600 line-clamp-1 mt-1 leading-relaxed">{{ $ticket->issue_description }}</p>
                                    <div class="flex items-center gap-2 mt-1.5 text-[11px] text-neutral-500 font-mono flex-wrap">
                                        <span>Tiket #{{ $ticket->id }}</span>
                                        <span>&middot;</span>
                                        <span>Pelapor: {{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                                        <span>&middot;</span>
                                        <span>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('maintenance.edit', $ticket->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm">
                                        <span>Proses Tindakan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-neutral-500">
                                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 inline-flex items-center justify-center mb-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-neutral-800">Kondisi Alat Laboratorium Prima</p>
                                <p class="text-xs text-neutral-500 mt-0.5">Tidak ada antrean tiket kendala atau perbaikan yang tertunda.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="p-3 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between text-xs">
                        <span class="text-neutral-500 font-mono text-[11px]">{{ $stats['under_maintenance'] }} unit dalam status perbaikan</span>
                        <a href="{{ route('maintenance.schedules.index') }}" class="font-semibold text-primary hover:text-primary-700 inline-flex items-center gap-1">
                            <span>Jadwal Perawatan Preventif</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Right / Side Column (lg:col-span-4 space-y-4) -->
            <div class="lg:col-span-4 space-y-4">

                <!-- Widget 1: Monitor Keamanan Gate Security RFID Terkini -->
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-neutral-200 bg-neutral-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-neutral-900 text-xs tracking-tight font-heading">Gate Security Live Feed</h3>
                                <p class="text-[10px] text-neutral-500 font-mono">Antena HW-VX6330 Siaga</p>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-mono text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Live
                        </span>
                    </div>

                    <div class="divide-y divide-neutral-100 max-h-72 overflow-y-auto">
                        @forelse($recentGateLogs as $log)
                            <div class="p-3 hover:bg-neutral-50/80 transition-colors flex items-start justify-between gap-2.5">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($log->action_status->value === 'allowed')
                                            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ALLOWED
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                                DENIED
                                            </span>
                                        @endif
                                        <span class="text-xs font-semibold text-neutral-900 truncate">
                                            {{ $log->item->name ?? 'Tag Tidak Terdaftar' }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-neutral-400 font-mono mt-1 truncate">
                                        EPC: {{ $log->epc_tag_scanned }}
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <div class="font-mono text-[11px] text-neutral-600 font-medium">
                                        {{ optional($log->created_at)->format('H:i:s') }}
                                    </div>
                                    @if(filled($log->capture_image_path))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-mono text-primary mt-0.5">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Foto Ada
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-neutral-500">
                                <p class="text-xs font-semibold text-neutral-700">Belum Ada Deteksi Gerbang Hari Ini</p>
                                <p class="text-[11px] text-neutral-400 mt-0.5">Pembacaan tag RFID akan tampil instan di sini.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="p-3 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between text-xs">
                        <span class="text-neutral-500 font-mono text-[11px]">{{ $stats['gate_total_hari_ini'] }} total deteksi</span>
                        <a href="{{ route('gate-logs.index') }}" class="font-semibold text-rose-700 hover:text-rose-800 inline-flex items-center gap-1">
                            <span>Buka Seluruh Log</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Widget 2: Radar Bahan & Kesehatan Inventaris (Low Stock Alert ala Seller Inventory) -->
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-neutral-200 bg-neutral-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-neutral-900 text-xs tracking-tight font-heading">Peringatan Stok &amp; Bahan Praktikum</h3>
                                <p class="text-[10px] text-neutral-500">Bahan habis pakai dengan stok kritis (&le; 5 unit)</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        @forelse($lowStockConsumables as $consumable)
                            <div class="p-3 hover:bg-neutral-50/80 transition-colors flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold text-neutral-900 truncate">{{ $consumable->name }}</div>
                                    <div class="text-[10px] text-neutral-500 font-mono mt-0.5">
                                        {{ $consumable->item_code }} &middot; {{ $consumable->location ?? 'Ruang Alat' }}
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="inline-block px-2 py-0.5 rounded font-mono text-xs font-bold {{ $consumable->stock <= 2 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        Sisa: {{ $consumable->stock }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-5 text-center text-neutral-500">
                                <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 inline-flex items-center justify-center mb-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-neutral-800">Stok Bahan Praktikum Aman</p>
                                <p class="text-[10px] text-neutral-500 mt-0.5">Tidak ada bahan habis pakai di bawah batas minimum.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="p-3 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between text-xs">
                        <span class="text-neutral-500 font-mono text-[11px]">Buku Stok Bahan Praktikum</span>
                        <a href="{{ route('items.index') }}" class="font-semibold text-primary hover:text-primary-700 inline-flex items-center gap-1">
                            <span>Katalog Aset</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Widget 3: Pintasan Cepat Operasional Laboratorium (Quick Launcher) -->
                <div class="bg-neutral-900 rounded-xl p-4 sm:p-5 border border-neutral-800 text-white shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-neutral-400 font-heading">Pintasan Cepat</span>
                        <span class="w-2 h-2 rounded-full bg-primary-400"></span>
                    </div>

                    <div class="space-y-2">
                        <a href="{{ route('kiosk.home') }}" target="_blank" class="w-full flex items-center justify-between p-2.5 rounded-lg bg-neutral-800/90 hover:bg-neutral-800 border border-neutral-700 text-xs font-semibold text-neutral-100 transition-colors group">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1 rounded bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span>Buka Layar Kiosk Siswa</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-neutral-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        <a href="{{ route('maintenance.schedules.index') }}" class="w-full flex items-center justify-between p-2.5 rounded-lg bg-neutral-800/90 hover:bg-neutral-800 border border-neutral-700 text-xs font-semibold text-neutral-100 transition-colors group">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1 rounded bg-amber-950 text-amber-400 border border-amber-800/60">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span>Jadwal Maintenance Preventif</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-neutral-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>

                        <a href="{{ route('reports.index') }}" class="w-full flex items-center justify-between p-2.5 rounded-lg bg-neutral-800/90 hover:bg-neutral-800 border border-neutral-700 text-xs font-semibold text-neutral-100 transition-colors group">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1 rounded bg-sky-950 text-sky-400 border border-sky-800/60">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <span>Cetak Laporan PDF / Excel</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-neutral-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
