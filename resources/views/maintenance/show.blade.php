<x-app-layout>
    <x-slot name="heading">Detail Laporan Tiket M&R #{{ $ticket->id }}</x-slot>
    <x-slot name="title">Laporan Tiket #{{ $ticket->id }}</x-slot>

    @php
        $latestLog = $ticket->latestLog;
        $totalCost = (float) $ticket->logs->sum('cost');
        $receiptLogs = $ticket->logs->filter(fn ($l) => filled($l->receipt_path))->values();
        $allSpareparts = $ticket->logs
            ->pluck('spareparts_used')
            ->filter()
            ->unique()
            ->implode(', ');

        $badge = match($ticket->status->value) {
            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
        };
    @endphp

    <div class="space-y-4 max-w-4xl">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <a href="{{ route('maintenance.index', ['status' => $ticket->status->value]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Tiket</span>
            </a>

            @if($ticket->status->value !== 'resolved')
                <a href="{{ route('maintenance.edit', $ticket->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Kelola &amp; Edit Tiket</span>
                </a>
            @endif
        </div>

        <!-- Ringkasan Eksekutif Laporan Perbaikan -->
        <div class="bg-white rounded-xl border border-neutral-200 p-5 space-y-4">
            <div class="flex items-start justify-between flex-wrap gap-3 pb-4 border-b border-neutral-100">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-bold text-neutral-900 tracking-tight font-heading">
                            Tiket <span class="font-mono">#{{ $ticket->id }}</span> — {{ $ticket->item->name ?? '-' }}
                        </h2>
                        @if(filled($ticket->item?->item_code))
                            <span class="font-mono text-[11px] px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">{{ $ticket->item->item_code }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">
                        Dilaporkan oleh <span class="font-semibold text-neutral-800">{{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                        <span class="mx-1 text-neutral-300">|</span>
                        Masuk: <span class="font-mono">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</span>
                        @if($ticket->status->value === 'resolved' && $latestLog?->repaired_at)
                            <span class="mx-1 text-neutral-300">|</span>
                            Selesai: <span class="font-mono text-emerald-700 font-semibold">{{ $latestLog->repaired_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $badge }}">{{ $ticket->status->label() }}</span>
                    @if($ticket->status->value === 'resolved' && $ticket->outcome === 'broken')
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md border bg-rose-50 text-rose-700 border-rose-200">
                            Rusak Permanen
                        </span>
                    @endif
                </div>
            </div>

            @if($ticket->status->value === 'resolved' && $ticket->outcome === 'broken')
                <div class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-rose-900">
                    <div>
                        <span class="font-bold">Barang Dinyatakan Rusak Permanen.</span>
                        <span class="text-rose-800">Unit tidak layak pakai telah dicatat ke daftar Barang Rusak dan stok Master Aset otomatis dikurangi.</span>
                    </div>
                    <a href="{{ route('maintenance.damaged.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-rose-100 text-rose-800 border border-rose-300 rounded-md font-semibold whitespace-nowrap transition-colors">
                        Lihat Daftar Barang Rusak
                    </a>
                </div>
            @endif

            <div class="p-4 bg-neutral-50 border border-neutral-200/70 rounded-lg">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-semibold mb-1.5">Deskripsi Kendala Awal</p>
                <p class="text-neutral-800 text-xs leading-relaxed">{{ $ticket->issue_description }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="p-3 rounded-lg border border-neutral-200 bg-white">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Penanganan</div>
                    <div class="mt-1">
                        @if($latestLog)
                            <span class="text-xs font-bold {{ $latestLog->service_type->value === 'vendor_luar' ? 'text-amber-700' : 'text-primary-700' }}">
                                {{ $latestLog->service_type->value === 'vendor_luar' ? 'Vendor Luar' : 'Internal Sekolah' }}
                            </span>
                            @if($latestLog->service_type->value === 'vendor_luar' && filled($latestLog->vendor_name))
                                <div class="text-[11px] text-neutral-600 mt-0.5">{{ $latestLog->vendor_name }}</div>
                            @endif
                        @else
                            <span class="text-xs text-neutral-400">Belum dicatat</span>
                        @endif
                    </div>
                </div>

                <div class="p-3 rounded-lg border border-neutral-200 bg-white">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Teknisi Penanggung Jawab</div>
                    <div class="mt-1 text-xs font-semibold text-neutral-800">
                        {{ $latestLog?->handledBy?->name ?? 'Teknisi Lab' }}
                    </div>
                </div>

                <div class="p-3 rounded-lg border border-neutral-200 bg-white">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Sparepart Diganti</div>
                    <div class="mt-1 text-xs text-neutral-800">
                        {{ $allSpareparts !== '' ? $allSpareparts : 'Tidak ada penggantian' }}
                    </div>
                </div>

                <div class="p-3 rounded-lg border border-neutral-200 bg-white">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Total Biaya Perbaikan</div>
                    <div class="mt-1 font-mono text-sm font-bold text-neutral-900">
                        {{ $totalCost > 0 ? 'Rp ' . number_format($totalCost, 0, ',', '.') : 'Rp 0 (Tanpa biaya)' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Dokumen Struk / Nota Pembayaran -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between gap-2">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Lampiran Struk &amp; Nota Pembayaran</h3>
                <span class="font-mono text-[11px] text-neutral-500">{{ $receiptLogs->count() }} berkas</span>
            </div>
            <div class="p-4">
                @if($receiptLogs->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($receiptLogs as $idx => $rLog)
                            @php $isPdf = str_ends_with(strtolower((string) $rLog->receipt_path), '.pdf'); @endphp
                            <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50/70 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-neutral-900 truncate">
                                        Struk #{{ $idx + 1 }} — {{ $rLog->service_type->value === 'vendor_luar' ? ($rLog->vendor_name ?: 'Vendor Luar') : 'Internal Sekolah' }}
                                    </div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ optional($rLog->repaired_at)->format('d/m/Y H:i') }}
                                        @if((float) $rLog->cost > 0)
                                            · Rp {{ number_format($rLog->cost, 0, ',', '.') }}
                                        @endif
                                    </div>
                                </div>
                                @if($isPdf)
                                    <a href="{{ route('maintenance.receipt', $rLog->id) }}" target="_blank"
                                       class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-300 bg-white hover:bg-neutral-100 text-xs font-semibold text-neutral-800 transition-colors">
                                        <span>Buka PDF</span>
                                    </a>
                                @else
                                    <button type="button"
                                            class="open-receipt-modal shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-primary-200 bg-primary-50 hover:bg-primary-100 text-xs font-semibold text-primary-700 transition-colors"
                                            data-receipt-url="{{ route('maintenance.receipt', $rLog->id) }}"
                                            data-receipt-title="Struk Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}"
                                            data-receipt-meta="{{ $rLog->service_type->label() }}{{ filled($rLog->vendor_name) ? ' · ' . $rLog->vendor_name : '' }}{{ (float) $rLog->cost > 0 ? ' · Rp ' . number_format($rLog->cost, 0, ',', '.') : '' }}">
                                        <span>Lihat Struk Nota</span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-neutral-500 py-2">Tidak ada lampiran struk pembayaran (perbaikan internal atau diselesaikan tanpa nota eksternal).</p>
                @endif
            </div>
        </div>

        <!-- Kronologi Riwayat Tindakan Perbaikan -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between gap-2">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Kronologi Riwayat Tindakan Perbaikan</h3>
                @if($totalCost > 0)
                    <span class="font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-white border border-neutral-200 text-neutral-800">
                        Total Biaya: Rp {{ number_format($totalCost, 0, ',', '.') }}
                    </span>
                @endif
            </div>
            <div class="divide-y divide-neutral-200/70">
                @forelse($ticket->logs as $log)
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <p class="text-xs font-semibold text-neutral-900">{{ $log->handledBy->name ?? 'Teknisi' }}</p>
                            <span class="font-mono text-xs text-neutral-500">{{ optional($log->repaired_at)->format('d/m/Y H:i') }}</span>
                        </div>

                        <p class="text-xs text-neutral-700 leading-relaxed">{{ $log->action_taken }}</p>

                        @if(filled($log->spareparts_used))
                            <div class="text-[11px] text-neutral-600 bg-neutral-50 border border-neutral-200/80 rounded-md px-2.5 py-1.5">
                                <span class="font-semibold text-neutral-800">Sparepart diganti:</span> {{ $log->spareparts_used }}
                            </div>
                        @endif

                        <div class="pt-1 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center flex-wrap gap-2 text-xs">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $log->service_type->value === 'internal' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                    {{ $log->service_type->value === 'internal' ? 'Internal Sekolah' : 'Vendor Luar' }}
                                </span>

                                @if($log->service_type->value === 'vendor_luar' && filled($log->vendor_name))
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border border-neutral-200 bg-neutral-50 text-neutral-700">
                                        Vendor: {{ $log->vendor_name }}
                                    </span>
                                @endif

                                @if($log->estimated_finish_at)
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded-md border border-neutral-200 bg-white text-neutral-600">
                                        Est. Selesai: {{ $log->estimated_finish_at->format('d/m/Y') }}
                                    </span>
                                @endif

                                @if((float) $log->cost > 0)
                                    <span class="font-mono text-xs text-neutral-900 font-semibold">Rp {{ number_format($log->cost, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-[11px] text-neutral-400">Tanpa biaya</span>
                                @endif
                            </div>

                            @if(filled($log->receipt_path))
                                @php $isPdfReceipt = str_ends_with(strtolower((string) $log->receipt_path), '.pdf'); @endphp
                                @if($isPdfReceipt)
                                    <a href="{{ route('maintenance.receipt', $log->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-[11px] font-semibold text-neutral-700 transition-colors">
                                        <span>Buka Struk PDF</span>
                                    </a>
                                @else
                                    <button type="button"
                                            class="open-receipt-modal inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-primary-200 bg-primary-50/70 hover:bg-primary-100 text-[11px] font-semibold text-primary-700 transition-colors"
                                            data-receipt-url="{{ route('maintenance.receipt', $log->id) }}"
                                            data-receipt-title="Struk Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}"
                                            data-receipt-meta="{{ $log->service_type->label() }}{{ filled($log->vendor_name) ? ' · ' . $log->vendor_name : '' }}{{ (float) $log->cost > 0 ? ' · Rp ' . number_format($log->cost, 0, ',', '.') : '' }}">
                                        <span>Lihat Struk Nota</span>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-neutral-500 text-xs">Belum ada catatan tindakan perbaikan.</p>
                @endforelse
            </div>
        </div>
    </div>

    @include('maintenance.partials._repair_modals', ['returnTo' => 'show'])
</x-app-layout>
