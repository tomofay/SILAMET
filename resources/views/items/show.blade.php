<x-app-layout>
    <x-slot name="heading">Detail Aset — {{ $item->item_code }}</x-slot>
    <x-slot name="title">Detail {{ $item->name }} — SILAMET</x-slot>

    @php
        $itemGroupService = app(\App\Services\ItemGroupService::class);
        $itemGroupLabel = filled($item->category) ? $item->category : $itemGroupService->normalizeItemBaseName($item->name);
        $itemGroupSlug = $itemGroupService->canonicalKey($itemGroupLabel);
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div class="flex items-center gap-3">
                <a href="{{ route('items.index', ['group' => $itemGroupSlug]) }}"
                   class="p-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm inline-flex items-center gap-1.5"
                   title="Kembali ke Kelompok {{ $itemGroupLabel }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="text-xs font-semibold pr-1">Kembali ke Kelompok {{ $itemGroupLabel }}</span>
                </a>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                            {{ $item->name }}
                        </h1>
                        <span class="font-mono text-xs font-semibold px-2.5 py-0.5 rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">
                            {{ $item->item_code }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-0.5">Spesifikasi teknis, pemetaan RFID, histori peminjaman &amp; pemeliharaan alat.</p>
                </div>
            </div>

            <!-- Top Right Action Buttons (Pill-shaped ala Dribbble) -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank"
                   class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Barcode</span>
                </a>
                <a href="{{ route('items.edit', $item->id) }}"
                   class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit Barang</span>
                </a>
            </div>
        </div>

        <!-- Info Barang (Rounded-2xl Container) -->
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-sm">
            <div class="flex items-start justify-between flex-wrap gap-4 pb-5 border-b border-neutral-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-700 shadow-inner">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-neutral-900 tracking-tight font-heading">{{ $item->name }}</h2>
                        <div class="flex items-center gap-2 flex-wrap mt-1">
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-800 border border-neutral-200">{{ $item->item_code }}</span>
                            @if(filled($item->category))
                                <a href="{{ route('items.index', ['group' => $itemGroupSlug]) }}" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-indigo-200 hover:bg-primary-100 transition-colors">
                                    Kelompok: {{ $item->category }}
                                </a>
                            @endif
                            @if($item->barcode_token)
                                <span class="font-mono text-xs text-neutral-500">Token: {{ $item->barcode_token }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @php
                    $badge = match($item->status->value) {
                        'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'dipinjam' => 'bg-sky-50 text-sky-700 border-sky-200',
                        'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                        default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                    };
                @endphp
                <span class="text-xs font-semibold px-3 py-1 rounded-full border {{ $badge }}">{{ $item->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 text-sm">
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider">EPC Tag UHF RFID</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1.5 break-all">{{ $item->epc_tag ?? '—' }}</dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider">Tipe Barang</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1.5 flex items-center gap-1.5">
                        @if($item->type === \App\Enums\ItemType::BAHAN_HABIS_PAKAI)
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 16v-8l-2 2" />
                                <path d="M13 16l4-4" />
                                <path d="M17 16l-4-4" />
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 0 0 0-8c-2 0-4 1.33-6 4Z" />
                            </svg>
                        @endif
                        <span>{{ $item->type->label() }}</span>
                    </dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider">Stok Tersedia</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1.5">{{ $item->stock }} unit</dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider">Lokasi / Rak</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1.5">{{ $item->location ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if($item->type === \App\Enums\ItemType::BAHAN_HABIS_PAKAI)
            <!-- Buku Stok Bahan Habis Pakai (Rounded-2xl Card) -->
            <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">Buku Stok &amp; Mutasi Bahan (Stock Ledger)</h3>
                        <p class="text-[11px] text-neutral-500">Catat penambahan stok masuk atau pengambilan bahan praktikum secara tercatat.</p>
                    </div>
                    <a href="{{ route('stock-movements.index', ['item_id' => $item->id]) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                        Buka Buku Stok Penuh &rarr;
                    </a>
                </div>

                <div class="p-5 border-b border-neutral-100 bg-white">
                    @if($errors->any())
                        <div class="mb-3 bg-rose-50 border border-rose-200 text-rose-800 px-3 py-2 rounded-xl text-xs">
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('stock-movements.store') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <div class="md:col-span-2">
                            <x-input-label for="item-stock-type" value="Jenis *" />
                            <select id="item-stock-type" name="type" class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs focus:border-primary focus:ring-primary">
                                <option value="out">Keluar (Pakai)</option>
                                <option value="in">Masuk (Tambah)</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="item-stock-qty" value="Jumlah *" />
                            <x-text-input id="item-stock-qty" name="quantity" type="number" min="1" value="1" required class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input-label for="item-stock-recipient" value="Pengambil / Kelas" />
                            <x-text-input id="item-stock-recipient" name="recipient_name" type="text" placeholder="Contoh: XI SIJA 1" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input-label for="item-stock-desc" value="Keterangan *" />
                            <x-text-input id="item-stock-desc" name="description" type="text" required placeholder="Praktikum / pengadaan" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full transition-colors shadow-sm">
                                Catat Mutasi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Waktu</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Saldo Stok</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Pengambil &amp; Keterangan</th>
                                <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Pencatat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($item->stockMovements as $mv)
                                <tr class="hover:bg-neutral-50/70 transition-colors">
                                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                        {{ $mv->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $mv->type === 'in' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                            {{ $mv->type === 'in' ? 'Masuk (+)' : 'Keluar (-)' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono font-bold text-xs">
                                        {{ $mv->type === 'in' ? '+' : '-' }}{{ $mv->quantity }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-mono text-xs text-neutral-600">
                                        {{ $mv->stock_after ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-neutral-700">
                                        <div class="font-medium text-neutral-900">{{ $mv->recipient_name ?? '—' }}</div>
                                        <div class="text-[11px] text-neutral-500">{{ $mv->description }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-neutral-600 whitespace-nowrap">
                                        {{ $mv->user->name ?? 'Sistem' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-xs text-neutral-400">
                                        Belum ada riwayat mutasi stok untuk bahan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Jadwal Pemeliharaan Preventif (Rounded-2xl Card) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50/70 flex items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">Jadwal Pemeliharaan Preventif</h3>
                    <p class="text-[11px] text-neutral-500">Siklus perawatan rutin berkala untuk menjaga keandalan operasional perangkat.</p>
                </div>
                @if(in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true))
                    <a href="{{ route('maintenance.schedules.index', ['item_id' => $item->id]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-2xs transition-colors">
                        <span>Atur Jadwal Maintenance</span>
                    </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kegiatan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Siklus</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Servis Terakhir</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jadwal Berikutnya</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($item->preventiveSchedules as $schedule)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-3 text-xs font-semibold text-neutral-900">{{ $schedule->title }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-600">Setiap {{ $schedule->interval_months }} bln</td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-600">{{ optional($schedule->last_serviced_at)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-neutral-900">{{ optional($schedule->next_due_date)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <span class="font-mono text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $schedule->status === 'paused' ? 'bg-neutral-100 text-neutral-600 border-neutral-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                        {{ $schedule->countdownLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-xs text-neutral-500">Barang ini belum memiliki jadwal maintenance berkala.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Maintenance (Rounded-2xl Card) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-neutral-200 bg-neutral-50/70">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">Riwayat Maintenance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Deskripsi Kendala</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pelapor</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($item->tickets as $ticket)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-3 text-xs text-neutral-800 max-w-md truncate">{{ $ticket->issue_description }}</td>
                                <td class="px-5 py-3 text-xs text-neutral-700">{{ $ticket->reportedBy->name ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $tbadge = match($ticket->status->value) {
                                            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $tbadge }}">{{ $ticket->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-600">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-xs text-neutral-500">Belum ada riwayat maintenance.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>