<x-app-layout>
    <x-slot name="heading">Pusat Laporan &amp; Ekspor Data</x-slot>
    <x-slot name="title">Laporan &amp; Ekspor — SILAMET</x-slot>

    @php
        $activeStart = $filters['start_date'] ?? '';
        $activeEnd = $filters['end_date'] ?? '';

        $reports = [
            [
                'type' => 'inventaris',
                'short_label' => 'Inventarisasi Aset',
                'title' => 'Laporan Inventarisasi Aset',
                'desc' => 'Daftar lengkap seluruh barang, kode barcode, tag EPC, lokasi rak, dan kondisi stok terkini.',
                'count' => $counts['inventaris'] ?? 0,
                'count_label' => 'Item',
                'uses_dates' => false,
                'statuses' => [
                    '' => 'Semua Status Barang',
                    'tersedia' => 'Tersedia',
                    'dipinjam' => 'Sedang Dipinjam',
                    'under_maintenance' => 'Under Maintenance',
                ],
                'icon' => 'box',
            ],
            [
                'type' => 'loans',
                'short_label' => 'Peminjaman Siswa',
                'title' => 'Rekapitulasi Peminjaman Siswa',
                'desc' => 'Riwayat transaksi peminjaman, durasi pakai, kondisi kembali, dan status kepatuhan jatuh tempo.',
                'count' => $counts['loans'] ?? 0,
                'count_label' => 'Transaksi',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Status Peminjaman',
                    'aktif' => 'Aktif Dipinjam',
                    'overdue' => 'Melewati Jatuh Tempo (Overdue)',
                    'menunggu_approval_kembali' => 'Menunggu Approval Kembali',
                    'selesai' => 'Selesai Dikembalikan',
                ],
                'icon' => 'refresh',
            ],
            [
                'type' => 'gate_logs',
                'short_label' => 'Gate Logs RFID',
                'title' => 'Log Keamanan Gate Security RFID',
                'desc' => 'Catatan deteksi antena gerbang HW-VX6330, insiden sirene terpicu (denied), dan status foto bukti.',
                'count' => $counts['gate_logs'] ?? 0,
                'count_label' => 'Log',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Aktivitas Gerbang',
                    'denied' => 'Hanya Pelanggaran (Denied)',
                    'allowed' => 'Hanya Akses Sah (Allowed)',
                ],
                'icon' => 'shield',
            ],
            [
                'type' => 'maintenance',
                'short_label' => 'Maintenance & Biaya',
                'title' => 'Laporan Pemeliharaan & Biaya (M&R)',
                'desc' => 'Rekap tiket kendala, rincian tindakan teknisi, vendor pihak ketiga, dan total biaya perbaikan.',
                'count' => $counts['maintenance'] ?? 0,
                'count_label' => 'Tiket',
                'uses_dates' => true,
                'statuses' => [
                    '' => 'Semua Status Tiket',
                    'open' => 'Open (Menunggu)',
                    'in_progress' => 'Dalam Perbaikan',
                    'resolved' => 'Resolved (Selesai)',
                ],
                'icon' => 'tool',
            ],
        ];
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto">

        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Pusat Laporan &amp; Ekspor Data
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Generate berkas rekapitulasi operasional Laboratorium SIJA SMKN 1 Cibinong ke PDF dan Excel (.xlsx).
                </p>
            </div>
        </div>

        @if($errors->any() || ! empty($filterErrors))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs space-y-1 shadow-sm">
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

        <!-- Header & Filter Periode Global (Rounded-2xl Card) -->
        <form id="report-filter-form" method="GET" action="{{ route('reports.index') }}" class="bg-white rounded-2xl p-6 border border-neutral-200 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Unduh Berkas Laporan Resmi</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Filter tanggal berlaku otomatis untuk seluruh modul laporan berkala di bawah.</p>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" data-preset="today" class="report-preset px-3 py-1.5 rounded-full text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">Hari Ini</button>
                    <button type="button" data-preset="week" class="report-preset px-3 py-1.5 rounded-full text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">7 Hari Terakhir</button>
                    <button type="button" data-preset="month" class="report-preset px-3 py-1.5 rounded-full text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">Bulan Ini</button>
                    <button type="button" data-preset="all" class="report-preset px-3 py-1.5 rounded-full text-xs font-semibold bg-white hover:bg-neutral-50 text-neutral-600 border border-neutral-200 transition-colors">Semua Periode</button>
                </div>
            </div>

            <div class="pt-4 border-t border-neutral-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <div class="lg:col-span-3">
                    <x-input-label for="report-start-date" value="Tanggal Mulai" />
                    <x-text-input id="report-start-date" name="start_date" type="date" value="{{ $activeStart }}" class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                </div>
                <div class="lg:col-span-3">
                    <x-input-label for="report-end-date" value="Tanggal Akhir" />
                    <x-text-input id="report-end-date" name="end_date" type="date" value="{{ $activeEnd }}" class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                </div>
                <div class="lg:col-span-4 flex items-center gap-2 text-xs text-neutral-500 font-mono bg-neutral-50 border border-neutral-200 rounded-xl px-3.5 py-2.5 min-h-[40px]">
                    <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span id="report-period-preview" class="truncate">Periode aktif: Semua data tercatat</span>
                </div>
                <div class="lg:col-span-2 flex items-center justify-end">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full transition-colors min-h-[40px] shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- 4 Kartu Pilihan Modul Laporan (Rounded-2xl Cards ala Dribbble) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($reports as $report)
                @php
                    $cardParams = array_filter([
                        'start_date' => $report['uses_dates'] ? $activeStart : null,
                        'end_date' => $report['uses_dates'] ? $activeEnd : null,
                    ], fn ($v) => $v !== null && $v !== '');
                    $dashboardUrl = route('reports.dashboard', array_merge(['type' => $report['type']], $cardParams));
                    $pdfUrl = route('reports.pdf', array_merge(['type' => $report['type']], $cardParams));
                    $excelUrl = route('reports.excel', array_merge(['type' => $report['type']], $cardParams));
                @endphp
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 flex flex-col justify-between gap-5 transition-all duration-150 hover:border-neutral-300 shadow-sm report-card"
                     data-type="{{ $report['type'] }}"
                     data-uses-dates="{{ $report['uses_dates'] ? '1' : '0' }}"
                     data-view-base="{{ route('reports.dashboard', $report['type']) }}"
                     data-pdf-base="{{ route('reports.pdf', $report['type']) }}"
                     data-excel-base="{{ route('reports.excel', $report['type']) }}">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-700">
                                    @if($report['icon'] === 'box')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    @elseif($report['icon'] === 'refresh')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    @elseif($report['icon'] === 'shield')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-neutral-900 font-heading">{{ $report['title'] }}</h3>
                                    <span class="text-[11px] font-mono text-neutral-500">{{ $report['short_label'] }}</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full font-mono text-xs font-bold bg-neutral-100 text-neutral-900 border border-neutral-200">
                                {{ number_format($report['count']) }} {{ $report['count_label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-neutral-500 mt-3 leading-relaxed">{{ $report['desc'] }}</p>

                        <div class="mt-4 pt-3 border-t border-neutral-100 flex items-center gap-2">
                            <label class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading shrink-0" for="status-{{ $report['type'] }}">Status:</label>
                            <select id="status-{{ $report['type'] }}" class="report-status-select rounded-full border border-neutral-200 bg-neutral-50 text-xs py-1.5 px-3 focus:ring-1 focus:ring-primary focus:border-primary w-full">
                                @foreach($report['statuses'] as $val => $txt)
                                    <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $txt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-3 border-t border-neutral-100 flex-wrap">
                        <a href="{{ $dashboardUrl }}" class="report-btn-view flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Lihat Data</span>
                        </a>

                        <a href="{{ $pdfUrl }}" target="_blank" class="report-btn-pdf inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>PDF</span>
                        </a>

                        <a href="{{ $excelUrl }}" class="report-btn-excel inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Excel</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('report-start-date');
            const endInput = document.getElementById('report-end-date');
            const preview = document.getElementById('report-period-preview');

            function formatDate(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function updatePreview() {
                const s = startInput.value;
                const e = endInput.value;
                if (!s && !e) {
                    preview.textContent = 'Periode aktif: Semua data tercatat';
                } else if (s && !e) {
                    preview.textContent = `Periode aktif: Mulai ${s}`;
                } else if (!s && e) {
                    preview.textContent = `Periode aktif: Sampai ${e}`;
                } else {
                    preview.textContent = `Periode aktif: ${s} s.d. ${e}`;
                }
                updateAllCardUrls();
            }

            function updateAllCardUrls() {
                const s = startInput.value;
                const e = endInput.value;

                document.querySelectorAll('.report-card').forEach(card => {
                    const usesDates = card.dataset.usesDates === '1';
                    const viewBase = card.dataset.viewBase;
                    const pdfBase = card.dataset.pdfBase;
                    const excelBase = card.dataset.excelBase;
                    const statusSelect = card.querySelector('.report-status-select');
                    const status = statusSelect ? statusSelect.value : '';

                    function buildUrl(base) {
                        const params = new URLSearchParams();
                        if (usesDates && s) params.set('start_date', s);
                        if (usesDates && e) params.set('end_date', e);
                        if (status) params.set('status', status);
                        const qs = params.toString();
                        return qs ? `${base}?${qs}` : base;
                    }

                    const btnView = card.querySelector('.report-btn-view');
                    const btnPdf = card.querySelector('.report-btn-pdf');
                    const btnExcel = card.querySelector('.report-btn-excel');

                    if (btnView) btnView.href = buildUrl(viewBase);
                    if (btnPdf) btnPdf.href = buildUrl(pdfBase);
                    if (btnExcel) btnExcel.href = buildUrl(excelBase);
                });
            }

            document.querySelectorAll('.report-preset').forEach(btn => {
                btn.addEventListener('click', function () {
                    const preset = this.dataset.preset;
                    const now = new Date();

                    if (preset === 'today') {
                        const todayStr = formatDate(now);
                        startInput.value = todayStr;
                        endInput.value = todayStr;
                    } else if (preset === 'week') {
                        const weekAgo = new Date();
                        weekAgo.setDate(now.getDate() - 6);
                        startInput.value = formatDate(weekAgo);
                        endInput.value = formatDate(now);
                    } else if (preset === 'month') {
                        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                        startInput.value = formatDate(firstDay);
                        endInput.value = formatDate(now);
                    } else if (preset === 'all') {
                        startInput.value = '';
                        endInput.value = '';
                    }

                    updatePreview();
                });
            });

            startInput.addEventListener('change', updatePreview);
            endInput.addEventListener('change', updatePreview);

            document.querySelectorAll('.report-status-select').forEach(sel => {
                sel.addEventListener('change', updateAllCardUrls);
            });

            updatePreview();
        });
    </script>
    @endpush
</x-app-layout>