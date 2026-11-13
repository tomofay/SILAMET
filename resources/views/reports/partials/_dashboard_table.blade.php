@php
    $paginator = $dashboard['paginator'];
    $canOpenMaintenance = in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true);
@endphp

<div class="rounded-2xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-neutral-200/80 bg-neutral-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="text-xs font-semibold text-neutral-700">
            Data Rinci — <span class="text-neutral-900 font-bold">{{ $dashboard['title'] }}</span>
        </div>
        <div class="text-[11px] font-mono text-neutral-500">
            @if($paginator->total() > 0)
                Menampilkan {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} dari {{ number_format($paginator->total()) }} entri
            @else
                0 entri ditemukan
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        @if($activeType === 'inventaris')
            <table class="min-w-full divide-y divide-neutral-100 text-xs">
                <thead class="bg-neutral-50/50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Kode &amp; Token</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Nama Barang</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">EPC Tag RFID</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Tipe</th>
                        <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Stok</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Lokasi / Rak</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Status</th>
                        <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($paginator as $item)
                        @php
                            $badge = match($item->status?->value) {
                                'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'dipinjam' => 'bg-sky-50 text-sky-700 border-sky-200',
                                'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                            };
                        @endphp
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-neutral-900">{{ $item->item_code }}</div>
                                <div class="font-mono text-[11px] text-neutral-400">{{ $item->barcode_token ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-neutral-900">{{ $item->name }}</div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-[11px] text-neutral-600">
                                {{ $item->epc_tag ?: '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-neutral-700">
                                {{ $item->type?->label() ?? '-' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right font-mono font-semibold text-neutral-900">
                                {{ number_format((int) $item->stock) }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-neutral-600">
                                {{ $item->location ?: '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badge }}">
                                    {{ $item->status?->label() ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('items.show', $item->id) }}" class="inline-flex items-center px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-semibold shadow-2xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-neutral-500">
                                Belum ada data aset atau bahan yang sesuai filter laporan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($activeType === 'loans')
            <table class="min-w-full divide-y divide-neutral-100 text-xs">
                <thead class="bg-neutral-50/50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Peminjam</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Barang</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Durasi</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Pinjam / Tempo</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Kembali &amp; Kondisi</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Status</th>
                        <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($paginator as $loan)
                        @php
                            $isLate = $loan->isOverdue();
                            $badge = match(true) {
                                $isLate => 'bg-rose-50 text-rose-700 border-rose-200',
                                $loan->status?->value === 'aktif' => 'bg-sky-50 text-sky-700 border-sky-200',
                                $loan->status?->value === 'menunggu_approval_kembali' => 'bg-amber-50 text-amber-800 border-amber-200',
                                $loan->status?->value === 'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                            };
                            $statusLabel = $isLate ? 'Overdue' : ($loan->status?->label() ?? '-');
                        @endphp
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-neutral-900">{{ $loan->user?->name ?? '-' }}</div>
                                <div class="font-mono text-[11px] text-neutral-400">{{ $loan->user?->nis_nip ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-neutral-900">{{ $loan->item?->name ?? '-' }}</div>
                                <div class="font-mono text-[11px] text-neutral-400">{{ $loan->item?->item_code ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-neutral-700">
                                {{ $loan->usage_duration?->label() ?? '-' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-[11px] text-neutral-600">
                                <div>Pjm: {{ optional($loan->borrowed_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                <div class="{{ $isLate ? 'text-rose-600 font-semibold' : '' }}">Tmp: {{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="font-mono text-[11px] text-neutral-600">{{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                @if($loan->condition_in === 'rusak')
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Rusak</span>
                                @elseif($loan->condition_in === 'bagus')
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Bagus</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badge }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-semibold shadow-2xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-neutral-500">
                                Tidak ada data peminjaman pada periode atau status yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($activeType === 'gate_logs')
            <table class="min-w-full divide-y divide-neutral-100 text-xs">
                <thead class="bg-neutral-50/50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Waktu Deteksi</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">EPC Tag Terbaca</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Barang &amp; Kode</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Lokasi Rak</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Status Gerbang</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Bukti Foto</th>
                        <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($paginator as $log)
                        @php
                            $isDenied = $log->action_status?->value === 'denied';
                            $itemName = $log->item
                                ? ($log->item->trashed() ? $log->item->name . ' (Dihapus)' : $log->item->name)
                                : 'Tag Asing / Tidak Terdaftar';
                        @endphp
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-neutral-700">
                                {{ optional($log->created_at)->format('d/m/Y H:i:s') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-neutral-800">
                                {{ $log->epc_tag_scanned }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold {{ $log->item ? 'text-neutral-900' : 'text-rose-700' }}">{{ $itemName }}</div>
                                @if($log->item?->item_code)
                                    <div class="font-mono text-[11px] text-neutral-400">{{ $log->item->item_code }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-neutral-600">
                                {{ $log->item?->location ?: '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full font-mono text-xs font-bold border {{ $isDenied ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                    {{ strtoupper($log->action_status?->value ?? '-') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($log->capture_image_path)
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">Tersedia</span>
                                @else
                                    <span class="text-neutral-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('gate-logs.show', $log->id) }}" class="inline-flex items-center px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-semibold shadow-2xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-neutral-500">
                                Belum ada log deteksi gerbang RFID pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @else
            <table class="min-w-full divide-y divide-neutral-100 text-xs">
                <thead class="bg-neutral-50/50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Tgl Lapor</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Barang &amp; Pelapor</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Kendala &amp; Tindakan</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Penanganan</th>
                        <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Biaya Servis</th>
                        <th class="px-5 py-3 text-left font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Status</th>
                        @if($canOpenMaintenance)
                            <th class="px-5 py-3 text-right font-semibold text-neutral-500 uppercase tracking-wider text-[11px]">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($paginator as $ticket)
                        @php
                            $ticketCost = (float) $ticket->logs->sum('cost');
                            $latestLog = $ticket->latestLog;
                            $badge = match($ticket->status?->value) {
                                'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                            };
                            $hasReceipt = $ticket->logs->contains(fn ($l) => filled($l->receipt_path));
                        @endphp
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-neutral-600">
                                <div>{{ optional($ticket->created_at)->format('d/m/Y') ?? '—' }}</div>
                                <div class="text-[11px] text-neutral-400">{{ optional($ticket->created_at)->format('H:i') ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-semibold text-neutral-900">{{ $ticket->item?->name ?? '-' }}</div>
                                <div class="font-mono text-[11px] text-neutral-400">{{ $ticket->item?->item_code ?? '—' }}</div>
                                <div class="text-[11px] text-neutral-500 mt-0.5">Oleh: {{ $ticket->reportedBy?->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3 max-w-xs">
                                <div class="text-neutral-800 line-clamp-2">{{ $ticket->issue_description }}</div>
                                @if($latestLog)
                                    <div class="text-[11px] text-neutral-500 mt-1 line-clamp-1">
                                        Log terakhir: {{ $latestLog->action_taken }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($latestLog)
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $latestLog->service_type?->value === 'vendor_luar' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-primary-50 text-primary-700 border-primary-100' }}">
                                        {{ $latestLog->service_type?->value === 'vendor_luar' ? 'Vendor Luar' : 'Internal Sekolah' }}
                                    </span>
                                    @if($latestLog->service_type?->value === 'vendor_luar' && filled($latestLog->vendor_name))
                                        <div class="text-[11px] text-neutral-600 mt-0.5">{{ $latestLog->vendor_name }}</div>
                                    @endif
                                @else
                                    <span class="text-neutral-400">Belum ditangani</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                <div class="font-mono font-semibold text-neutral-900">
                                    {{ $ticketCost > 0 ? 'Rp ' . number_format($ticketCost, 0, ',', '.') : 'Tanpa biaya' }}
                                </div>
                                @if($hasReceipt)
                                    <div class="text-[10px] text-emerald-700 font-medium mt-0.5">Ada lampiran struk</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badge }}">
                                    {{ $ticket->status?->label() ?? '-' }}
                                </span>
                                @if($ticket->outcome === 'broken')
                                    <div class="mt-1">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Rusak Permanen
                                        </span>
                                    </div>
                                @endif
                            </td>
                            @if($canOpenMaintenance)
                                <td class="px-5 py-3 whitespace-nowrap text-right">
                                    @if($ticket->status?->value === 'resolved')
                                        <a href="{{ route('maintenance.show', $ticket->id) }}" class="inline-flex items-center px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-semibold shadow-2xs transition-colors">
                                            Detail
                                        </a>
                                    @else
                                        <a href="{{ route('maintenance.edit', $ticket->id) }}" class="inline-flex items-center px-3 py-1 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-semibold shadow-2xs transition-colors">
                                            Kelola
                                        </a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canOpenMaintenance ? 7 : 6 }}" class="px-5 py-12 text-center text-neutral-500">
                                Tidak ada tiket pemeliharaan atau perbaikan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    @if($paginator->hasPages())
        <div class="px-5 py-4 border-t border-neutral-200/80 bg-neutral-50/50">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
