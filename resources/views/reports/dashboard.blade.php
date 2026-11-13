<x-app-layout>
    @php
        $activeStart = $filters['start_date'] ?? '';
        $activeEnd = $filters['end_date'] ?? '';
        $activeStatus = $filters['status'] ?? '';
        $activeSearch = $filters['q'] ?? '';

        $reports = [
            [
                'type' => 'inventaris',
                'short_label' => 'Inventarisasi Aset',
                'title' => 'Laporan Inventarisasi Aset',
                'uses_dates' => false,
                'statuses' => [
                    '' => 'Semua Status Barang',
                    'tersedia' => 'Tersedia',
                    'dipinjam' => 'Sedang Dipinjam',
                    'under_maintenance' => 'Under Maintenance',
                ],
            ],
            [
                'type' => 'loans',
                'short_label' => 'Peminjaman Siswa',
                'title' => 'Rekapitulasi Peminjaman Siswa',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Status Peminjaman',
                    'aktif' => 'Aktif Dipinjam',
                    'overdue' => 'Melewati Jatuh Tempo (Overdue)',
                    'menunggu_approval_kembali' => 'Menunggu Approval Kembali',
                    'selesai' => 'Selesai Dikembalikan',
                ],
            ],
            [
                'type' => 'gate_logs',
                'short_label' => 'Gate Logs RFID',
                'title' => 'Log Keamanan Gate Security RFID',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Aktivitas Gerbang',
                    'denied' => 'Hanya Pelanggaran (Denied)',
                    'allowed' => 'Hanya Akses Sah (Allowed)',
                ],
            ],
            [
                'type' => 'maintenance',
                'short_label' => 'Maintenance & Biaya',
                'title' => 'Laporan Pemeliharaan & Biaya (M&R)',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Status Tiket',
                    'open' => 'Open (Menunggu)',
                    'in_progress' => 'Dalam Perbaikan',
                    'resolved' => 'Resolved (Selesai)',
                ],
            ],
        ];

        $activeReport = collect($reports)->firstWhere('type', $activeType) ?? $reports[0];

        $activeExportParams = array_filter([
            'start_date' => $activeReport['uses_dates'] ? $activeStart : null,
            'end_date' => $activeReport['uses_dates'] ? $activeEnd : null,
            'status' => $activeStatus !== '' ? $activeStatus : null,
            'q' => $activeSearch !== '' ? $activeSearch : null,
        ], fn ($v) => $v !== null && $v !== '');

        $backParams = array_filter([
            'start_date' => $activeStart ?: null,
            'end_date' => $activeEnd ?: null,
        ]);
    @endphp

    <x-slot name="heading">Dasbor {{ $activeReport['short_label'] }}</x-slot>
    <x-slot name="title">Dasbor {{ $activeReport['short_label'] }} — Laporan</x-slot>

    <div class="space-y-4">

        <!-- Bar Kembali & Switcher Antar Dasbor Laporan -->
        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('reports.index', $backParams) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-2.5 py-1 rounded-lg border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Kembali ke Pusat Laporan</span>
                        </a>
                        <span class="px-2 py-0.5 rounded bg-neutral-100 border border-neutral-200 font-mono text-[11px] text-neutral-700">
                            {{ $activeReport['uses_dates'] ? $dashboard['period_label'] : 'Snapshot Inventaris Terkini' }}
                        </span>
                    </div>
                    <h2 class="text-base sm:text-lg font-bold text-neutral-900 tracking-tight font-heading pt-1">
                        Dasbor Data Laporan — {{ $activeReport['title'] }}
                    </h2>
                    <p class="text-xs text-neutral-500">
                        Ringkasan metrik dan data tabel langsung dari database — sinkron dengan berkas PDF &amp; Excel yang diunduh.
                    </p>
                </div>

                <!-- Tab Switcher Cepat Antar Halaman Dasbor -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach($reports as $tabItem)
                        @php
                            $isTabActive = $tabItem['type'] === $activeType;
                            $tabHref = route('reports.dashboard', array_merge(
                                ['type' => $tabItem['type']],
                                array_filter([
                                    'start_date' => $tabItem['uses_dates'] ? $activeStart : null,
                                    'end_date' => $tabItem['uses_dates'] ? $activeEnd : null,
                                ], fn ($v) => $v !== null && $v !== '')
                            ));
                        @endphp
                        <a href="{{ $tabHref }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors {{ $isTabActive ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white hover:bg-neutral-50 text-neutral-600 border-neutral-200' }}">
                            {{ $tabItem['short_label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if($errors->any() || ! empty($filterErrors))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                    @foreach(($filterErrors ?? []) as $fErr)
                        <li>{{ $fErr }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Panel Filter & Ekspor -->
        <section id="report-dashboard-panel" class="space-y-4">
            <div class="bg-white rounded-xl border border-neutral-200 p-4 flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                <form id="dashboard-filter-form" method="GET" action="{{ route('reports.dashboard', $activeType) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 flex-1 items-end">
                    <div class="{{ $activeReport['uses_dates'] ? 'lg:col-span-4' : 'lg:col-span-6' }} relative">
                        <label for="dashboard-search" class="block text-[11px] font-semibold text-neutral-600 mb-1">Kata Kunci</label>
                        <div class="relative">
                            <input id="dashboard-search" type="text" name="q" value="{{ $activeSearch }}"
                                   placeholder="Cari kode barang, nama, NIS/NIP, tag EPC, atau kendala..."
                                   class="w-full pl-8 pr-3 py-1.5 rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary bg-white">
                            <svg class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="dashboard-status" class="block text-[11px] font-semibold text-neutral-600 mb-1">Filter Status</label>
                        <select id="dashboard-status" name="status" onchange="this.form.submit()" class="w-full rounded-lg border-neutral-300 text-xs py-1.5 px-2.5 focus:border-primary focus:ring-primary bg-white">
                            @foreach($activeReport['statuses'] as $val => $label)
                                <option value="{{ $val }}" @selected($activeStatus === (string) $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($activeReport['uses_dates'])
                        <div class="lg:col-span-2">
                            <label for="dashboard-start" class="block text-[11px] font-semibold text-neutral-600 mb-1">Mulai</label>
                            <input id="dashboard-start" type="date" name="start_date" value="{{ $activeStart }}" class="w-full rounded-lg border-neutral-300 text-xs py-1.5 px-2 font-mono focus:border-primary focus:ring-primary bg-white">
                        </div>
                        <div class="lg:col-span-2">
                            <label for="dashboard-end" class="block text-[11px] font-semibold text-neutral-600 mb-1">Sampai</label>
                            <input id="dashboard-end" type="date" name="end_date" value="{{ $activeEnd }}" class="w-full rounded-lg border-neutral-300 text-xs py-1.5 px-2 font-mono focus:border-primary focus:ring-primary bg-white">
                        </div>
                    @endif

                    <div class="{{ $activeReport['uses_dates'] ? 'lg:col-span-1' : 'lg:col-span-3' }} flex items-center gap-1.5">
                        <button type="submit" class="px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition-colors">
                            Terapkan
                        </button>
                        @if($activeStatus !== '' || $activeSearch !== '' || ($activeReport['uses_dates'] && ($activeStart !== '' || $activeEnd !== '')))
                            <a href="{{ route('reports.dashboard', $activeType) }}"
                               class="px-2.5 py-1.5 bg-white hover:bg-neutral-100 text-neutral-600 border border-neutral-200 text-xs font-semibold rounded-lg transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('reports.pdf', array_merge(['type' => $activeType], $activeExportParams)) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor PDF Tampilan Ini</span>
                    </a>
                    <a href="{{ route('reports.excel', array_merge(['type' => $activeType], $activeExportParams)) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel Tampilan Ini</span>
                    </a>
                </div>
            </div>

            @include('reports.partials._dashboard_metrics')

            @include('reports.partials._dashboard_table')
        </section>

    </div>
</x-app-layout>

