@php
    $summary = $dashboard['summary'] ?? [];
    $breakdown = $dashboard['breakdown'] ?? [];
    $breakdownTitle = $dashboard['breakdown_title'] ?? 'Distribusi Data';
    $totalRows = (int) ($summary['total'] ?? 0);
@endphp

<!-- Row 1: KPI Cards per Tipe Laporan -->
@if($activeType === 'inventaris')
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Total Item</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">{{ number_format($summary['total'] ?? 0) }}</div>
            <div class="text-[11px] text-neutral-500 mt-1 font-mono">{{ number_format($summary['total_stock'] ?? 0) }} unit stok</div>
        </div>
        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Tersedia</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-emerald-700">{{ number_format($summary['tersedia'] ?? 0) }}</div>
            <div class="text-[11px] text-emerald-700/80 mt-1">Siap dipinjam</div>
        </div>
        <div class="p-3.5 rounded-xl border border-sky-200 bg-brand-50/60">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Sedang Dipinjam</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-brand-700">{{ number_format($summary['dipinjam'] ?? 0) }}</div>
            <div class="text-[11px] text-brand-700/80 mt-1">Aktif di luar lab</div>
        </div>
        <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50/50">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Maintenance</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-amber-800">{{ number_format($summary['maintenance'] ?? 0) }}</div>
            <div class="text-[11px] text-amber-700 mt-1">Terkunci perbaikan</div>
        </div>
        <div class="col-span-2 sm:col-span-1 p-3.5 rounded-xl border border-neutral-200 bg-neutral-50/60">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Komposisi Tipe</div>
            <div class="mt-1 text-sm font-bold font-mono tabular-nums text-neutral-900">
                {{ number_format($summary['aset'] ?? 0) }} Aset
            </div>
            <div class="text-[11px] text-neutral-500 mt-1 font-mono">{{ number_format($summary['bhp'] ?? 0) }} Bahan Habis Pakai</div>
        </div>
    </div>
@elseif($activeType === 'loans')
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Total Transaksi</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">{{ number_format($summary['total'] ?? 0) }}</div>
            <div class="text-[11px] text-neutral-500 mt-1">Sesuai filter aktif</div>
        </div>
        <div class="p-3.5 rounded-xl border border-sky-200 bg-brand-50/60">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Aktif Dipinjam</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-brand-700">{{ number_format($summary['aktif'] ?? 0) }}</div>
            <div class="text-[11px] text-brand-700/80 mt-1 font-mono">{{ number_format($summary['menunggu_approval'] ?? 0) }} tunggu approval</div>
        </div>
        <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Jatuh Tempo (Overdue)</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-rose-700">{{ number_format($summary['overdue'] ?? 0) }}</div>
            <div class="text-[11px] text-rose-700/80 mt-1">Belum dikembalikan</div>
        </div>
        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Selesai Kembali</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-emerald-700">{{ number_format($summary['selesai'] ?? 0) }}</div>
            <div class="text-[11px] text-emerald-700/80 mt-1">Tercatat lengkap</div>
        </div>
        <div class="col-span-2 sm:col-span-1 p-3.5 rounded-xl border border-amber-200 bg-amber-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Kondisi Kembali Rusak</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-amber-800">{{ number_format($summary['rusak'] ?? 0) }}</div>
            <div class="text-[11px] text-amber-700 mt-1">Memicu tiket M&amp;R</div>
        </div>
    </div>
@elseif($activeType === 'gate_logs')
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Total Deteksi Gate</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">{{ number_format($summary['total'] ?? 0) }}</div>
            <div class="text-[11px] text-neutral-500 mt-1">Pembacaan antena RFID</div>
        </div>
        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Akses Sah (Allowed)</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-emerald-700">{{ number_format($summary['allowed'] ?? 0) }}</div>
            <div class="text-[11px] text-emerald-700/80 mt-1">Sesuai status pinjam</div>
        </div>
        <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Pelanggaran (Denied)</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-rose-700">{{ number_format($summary['denied'] ?? 0) }}</div>
            <div class="text-[11px] text-rose-700/80 mt-1">Sirine gerbang terpicu</div>
        </div>
        <div class="p-3.5 rounded-xl border border-neutral-200 bg-neutral-50/60">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Tingkat Kepatuhan</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">
                {{ $summary['compliance_rate'] !== null ? number_format((float) $summary['compliance_rate'], 1, ',', '.') . '%' : '—' }}
            </div>
            <div class="text-[11px] text-neutral-500 mt-1">Rasio akses sah</div>
        </div>
        <div class="col-span-2 sm:col-span-1 p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Bukti Foto Kamera</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">{{ number_format($summary['with_photo'] ?? 0) }}</div>
            <div class="text-[11px] text-neutral-500 mt-1">Tangkapan webcam gate</div>
        </div>
    </div>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Total Tiket M&amp;R</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-neutral-900">{{ number_format($summary['total'] ?? 0) }}</div>
            <div class="text-[11px] text-neutral-500 mt-1 font-mono">{{ number_format($summary['receipt_count'] ?? 0) }} lampiran struk</div>
        </div>
        <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Open (Menunggu)</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-rose-700">{{ number_format($summary['open'] ?? 0) }}</div>
            <div class="text-[11px] text-rose-700/80 mt-1">Perlu tindak lanjut</div>
        </div>
        <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50/50">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Sedang Diperbaiki</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-amber-800">{{ number_format($summary['in_progress'] ?? 0) }}</div>
            <div class="text-[11px] text-amber-700 mt-1">Dalam pengerjaan</div>
        </div>
        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/40">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Selesai (Resolved)</div>
            <div class="mt-1 text-xl font-bold font-mono tabular-nums text-emerald-700">{{ number_format($summary['resolved'] ?? 0) }}</div>
            <div class="text-[11px] text-emerald-700/80 mt-1">Alat layak pakai lagi</div>
        </div>
        <div class="col-span-2 sm:col-span-1 p-3.5 rounded-xl border border-indigo-200 bg-primary-50/50">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-primary-700">Total Biaya Servis</div>
            <div class="mt-1 text-lg font-bold font-mono tabular-nums text-indigo-950">
                Rp {{ number_format((float) ($summary['total_cost'] ?? 0), 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-primary-700/90 mt-1 font-mono">
                Int: Rp {{ number_format((float) ($summary['internal_cost'] ?? 0), 0, ',', '.') }}
            </div>
        </div>
    </div>
@endif

<!-- Row 2: Visual Komposisi Proporsional & Breakdown Top 5 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <!-- Komposisi Status / Biaya -->
    <div class="lg:col-span-6 p-4 rounded-xl border border-neutral-200 bg-white space-y-3">
        <div class="flex items-center justify-between gap-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-700">
                {{ $activeType === 'maintenance' ? 'Komposisi Status & Biaya Perbaikan' : 'Komposisi Status Laporan' }}
            </h4>
            <span class="text-[11px] font-mono text-neutral-500">{{ number_format($totalRows) }} total data</span>
        </div>

        @php
            $segments = match ($activeType) {
                'inventaris' => [
                    ['label' => 'Tersedia', 'count' => (int) ($summary['tersedia'] ?? 0), 'color' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
                    ['label' => 'Dipinjam', 'count' => (int) ($summary['dipinjam'] ?? 0), 'color' => 'bg-sky-500', 'dot' => 'bg-sky-500'],
                    ['label' => 'Maintenance', 'count' => (int) ($summary['maintenance'] ?? 0), 'color' => 'bg-amber-500', 'dot' => 'bg-amber-500'],
                ],
                'loans' => [
                    ['label' => 'Selesai', 'count' => (int) ($summary['selesai'] ?? 0), 'color' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
                    ['label' => 'Aktif Normal', 'count' => (int) ($summary['aktif_normal'] ?? 0), 'color' => 'bg-sky-500', 'dot' => 'bg-sky-500'],
                    ['label' => 'Approval Kembali', 'count' => (int) ($summary['menunggu_approval'] ?? 0), 'color' => 'bg-amber-500', 'dot' => 'bg-amber-500'],
                    ['label' => 'Overdue', 'count' => (int) ($summary['overdue'] ?? 0), 'color' => 'bg-rose-500', 'dot' => 'bg-rose-500'],
                ],
                'gate_logs' => [
                    ['label' => 'Allowed (Sah)', 'count' => (int) ($summary['allowed'] ?? 0), 'color' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
                    ['label' => 'Denied (Alarm)', 'count' => (int) ($summary['denied'] ?? 0), 'color' => 'bg-rose-500', 'dot' => 'bg-rose-500'],
                ],
                default => [
                    ['label' => 'Resolved (Selesai)', 'count' => (int) ($summary['resolved'] ?? 0), 'color' => 'bg-emerald-500', 'dot' => 'bg-emerald-500'],
                    ['label' => 'Sedang Diperbaiki', 'count' => (int) ($summary['in_progress'] ?? 0), 'color' => 'bg-amber-500', 'dot' => 'bg-amber-500'],
                    ['label' => 'Open (Menunggu)', 'count' => (int) ($summary['open'] ?? 0), 'color' => 'bg-rose-500', 'dot' => 'bg-rose-500'],
                ],
            };
        @endphp

        @if($totalRows > 0)
            <div class="w-full h-3 rounded-full bg-neutral-100 overflow-hidden flex border border-neutral-200/70">
                @foreach($segments as $seg)
                    @php $pct = ($seg['count'] / $totalRows) * 100; @endphp
                    @if($pct > 0)
                        <div class="{{ $seg['color'] }} h-full" style="width: {{ number_format($pct, 2, '.', '') }}%" title="{{ $seg['label'] }}: {{ $seg['count'] }} ({{ number_format($pct, 1) }}%)"></div>
                    @endif
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                @foreach($segments as $seg)
                    @php $pct = ($seg['count'] / $totalRows) * 100; @endphp
                    <div class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-neutral-50 border border-neutral-200/70 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-neutral-700 font-medium truncate">
                            <span class="w-2 h-2 rounded-full {{ $seg['dot'] }} shrink-0"></span>
                            <span class="truncate">{{ $seg['label'] }}</span>
                        </span>
                        <span class="font-mono text-neutral-900 font-semibold shrink-0">
                            {{ number_format($seg['count']) }} <span class="text-neutral-400 font-normal">({{ number_format($pct, 1) }}%)</span>
                        </span>
                    </div>
                @endforeach
            </div>

            @if($activeType === 'maintenance')
                <div class="pt-2 border-t border-neutral-100 grid grid-cols-2 gap-2 text-xs">
                    <div class="px-2.5 py-2 rounded-lg bg-primary-50/50 border border-primary-100">
                        <div class="text-[10px] font-semibold uppercase text-primary-700">Biaya Internal Sekolah</div>
                        <div class="font-mono font-bold text-indigo-950 mt-0.5">
                            Rp {{ number_format((float) ($summary['internal_cost'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="px-2.5 py-2 rounded-lg bg-amber-50/60 border border-amber-200/80">
                        <div class="text-[10px] font-semibold uppercase text-amber-800">Biaya Vendor Luar</div>
                        <div class="font-mono font-bold text-amber-900 mt-0.5">
                            Rp {{ number_format((float) ($summary['vendor_cost'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="py-6 text-center text-xs text-neutral-500 border border-dashed border-neutral-200 rounded-lg bg-neutral-50/60">
                Belum ada data yang sesuai filter periode/status ini.
            </div>
        @endif
    </div>

    <!-- Breakdown Top 5 -->
    <div class="lg:col-span-6 p-4 rounded-xl border border-neutral-200 bg-white space-y-3">
        <div class="flex items-center justify-between gap-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-700">{{ $breakdownTitle }}</h4>
            <span class="text-[11px] font-mono text-neutral-400">Data Riil DB</span>
        </div>

        @if(! empty($breakdown))
            <div class="space-y-2.5">
                @foreach($breakdown as $row)
                    @php $widthPct = min(100, max(4, (float) ($row['percent'] ?? 0))); @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <div class="min-w-0 truncate">
                                <span class="font-semibold text-neutral-800">{{ $row['label'] }}</span>
                                @if(filled($row['sublabel'] ?? null))
                                    <span class="font-mono text-[11px] text-neutral-500 ml-1">{{ $row['sublabel'] }}</span>
                                @endif
                            </div>
                            <span class="font-mono text-xs font-semibold text-neutral-900 shrink-0">
                                {{ number_format($row['count']) }}
                                <span class="text-neutral-400 font-normal">({{ number_format((float) $row['percent'], 1) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-neutral-100 overflow-hidden">
                            <div class="h-full rounded-full bg-primary" style="width: {{ number_format($widthPct, 1, '.', '') }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 text-center text-xs text-neutral-500 border border-dashed border-neutral-200 rounded-lg bg-neutral-50/60">
                Belum ada rincian distribusi data untuk ditampilkan.
            </div>
        @endif
    </div>
</div>
