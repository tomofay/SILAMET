<x-app-layout>
    <x-slot name="heading">Pusat Laporan &amp; Ekspor Data</x-slot>
    <x-slot name="title">Laporan &amp; Ekspor</x-slot>

    <div class="space-y-4">

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header & Filter Periode Global -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Unduh Berkas Laporan Resmi</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Ekspor data riil Laboratorium SIJA SMKN 1 Cibinong ke format dokumen PDF resmi berkop sekolah atau Excel (.xlsx).</p>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <button type="button" data-preset="today" class="report-preset px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">Hari Ini</button>
                    <button type="button" data-preset="week" class="report-preset px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">7 Hari Terakhir</button>
                    <button type="button" data-preset="month" class="report-preset px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200 transition-colors">Bulan Ini</button>
                    <button type="button" data-preset="all" class="report-preset px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-neutral-50 text-neutral-600 border border-neutral-200 transition-colors">Semua Periode</button>
                </div>
            </div>

            <div class="pt-3 border-t border-neutral-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div>
                    <x-input-label for="report-start-date" value="Tanggal Mulai" />
                    <x-text-input id="report-start-date" type="date" value="{{ request('start_date') }}" class="mt-1 w-full font-mono text-xs" />
                </div>
                <div>
                    <x-input-label for="report-end-date" value="Tanggal Akhir" />
                    <x-text-input id="report-end-date" type="date" value="{{ request('end_date') }}" class="mt-1 w-full font-mono text-xs" />
                </div>
                <div class="sm:col-span-2 flex items-center gap-2 text-xs text-neutral-500 font-mono bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span id="report-period-preview">Periode aktif: Semua data tercatat (kosongkan tanggal untuk ekspor penuh)</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $reports = [
                    [
                        'type' => 'inventaris',
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
                        'title' => 'Rekapitulasi Peminjaman Siswa',
                        'desc' => 'Riwayat transaksi peminjaman, durasi pakai, kondisi kembali, dan status kepatuhan jatuh tempo.',
                        'count' => $counts['loans'] ?? 0,
                        'count_label' => 'Transaksi',
                        'uses_dates' => true,
                        'statuses' => [
                            '' => 'Semua Status Peminjaman',
                            'aktif' => 'Aktif Dipinjam',
                            'overdue' => 'Melewati Jatuh Tempo (Overdue)',
                            'selesai' => 'Selesai Dikembalikan',
                        ],
                        'icon' => 'refresh',
                    ],
                    [
                        'type' => 'gate_logs',
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

            @foreach($reports as $report)
                <div class="bg-white rounded-xl border border-neutral-200 p-5 flex flex-col justify-between gap-4 report-card"
                     data-type="{{ $report['type'] }}"
                     data-uses-dates="{{ $report['uses_dates'] ? '1' : '0' }}"
                     data-pdf-base="{{ route('reports.pdf', $report['type']) }}"
                     data-excel-base="{{ route('reports.excel', $report['type']) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3.5 min-w-0">
                            <div class="p-2.5 rounded-lg bg-neutral-100 text-neutral-700 border border-neutral-200 shrink-0">
                                @if($report['icon'] === 'box')
                                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                @elseif($report['icon'] === 'refresh')
                                    <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                @elseif($report['icon'] === 'shield')
                                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">{{ $report['title'] }}</h3>
                                <p class="text-xs text-neutral-500 mt-1 leading-relaxed">{{ $report['desc'] }}</p>
                            </div>
                        </div>

                        <span class="font-mono text-[11px] font-semibold px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200 shrink-0">
                            {{ number_format($report['count']) }} {{ $report['count_label'] }}
                        </span>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                        <div class="sm:w-52">
                            <select aria-label="Filter status {{ $report['title'] }}"
                                    class="report-status-select w-full rounded-lg border-neutral-200 text-xs py-1.5 px-2.5 focus:border-primary focus:ring-primary">
                                @foreach($report['statuses'] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('reports.pdf', $report['type']) }}"
                               class="report-pdf-link inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Unduh PDF</span>
                            </a>
                            <a href="{{ route('reports.excel', $report['type']) }}"
                               class="report-excel-link inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Unduh Excel</span>
                            </a>
                        </div>
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
            const cards = document.querySelectorAll('.report-card');

            function fmtLocalDate(dateObj) {
                const y = dateObj.getFullYear();
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                const d = String(dateObj.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function syncLinks() {
                const start = startInput.value;
                const end = endInput.value;

                if (start && end) {
                    preview.textContent = `Periode aktif: ${start} s.d. ${end}`;
                } else if (start) {
                    preview.textContent = `Periode aktif: Mulai ${start}`;
                } else if (end) {
                    preview.textContent = `Periode aktif: Sampai ${end}`;
                } else {
                    preview.textContent = 'Periode aktif: Semua data tercatat (kosongkan tanggal untuk ekspor penuh)';
                }

                cards.forEach(function (card) {
                    const usesDates = card.dataset.usesDates === '1';
                    const statusSelect = card.querySelector('.report-status-select');
                    const pdfLink = card.querySelector('.report-pdf-link');
                    const excelLink = card.querySelector('.report-excel-link');

                    const params = new URLSearchParams();
                    if (usesDates && start) params.set('start_date', start);
                    if (usesDates && end) params.set('end_date', end);
                    if (statusSelect && statusSelect.value) params.set('status', statusSelect.value);

                    const query = params.toString();
                    pdfLink.href = query ? `${card.dataset.pdfBase}?${query}` : card.dataset.pdfBase;
                    excelLink.href = query ? `${card.dataset.excelBase}?${query}` : card.dataset.excelBase;
                });
            }

            if (startInput) startInput.addEventListener('change', syncLinks);
            if (endInput) endInput.addEventListener('change', syncLinks);

            cards.forEach(function (card) {
                const sel = card.querySelector('.report-status-select');
                if (sel) sel.addEventListener('change', syncLinks);
            });

            document.querySelectorAll('.report-preset').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const preset = btn.dataset.preset;
                    const now = new Date();
                    if (preset === 'today') {
                        const t = fmtLocalDate(now);
                        startInput.value = t;
                        endInput.value = t;
                    } else if (preset === 'week') {
                        const s = new Date(now);
                        s.setDate(now.getDate() - 6);
                        startInput.value = fmtLocalDate(s);
                        endInput.value = fmtLocalDate(now);
                    } else if (preset === 'month') {
                        const s = new Date(now.getFullYear(), now.getMonth(), 1);
                        startInput.value = fmtLocalDate(s);
                        endInput.value = fmtLocalDate(now);
                    } else {
                        startInput.value = '';
                        endInput.value = '';
                    }
                    syncLinks();
                });
            });

            syncLinks();
        });
    </script>
    @endpush
</x-app-layout>
