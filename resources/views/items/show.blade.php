<x-app-layout>
    <x-slot name="heading">Detail Aset — {{ $item->item_code }}</x-slot>
    <x-slot name="title">Detail {{ $item->name }}</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('items.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 text-xs font-semibold rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Barcode</span>
                </a>
                <a href="{{ route('items.edit', $item->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit Barang</span>
                </a>
            </div>
        </div>

        <!-- Info Barang -->
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <div class="flex items-start justify-between flex-wrap gap-4 pb-4 border-b border-neutral-100">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900 tracking-tight">{{ $item->name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-neutral-100 text-neutral-800 border border-neutral-200">{{ $item->item_code }}</span>
                        @if($item->barcode_token)
                            <span class="font-mono text-xs text-neutral-500">Token: {{ $item->barcode_token }}</span>
                        @endif
                    </div>
                </div>
                @php
                    $badge = match($item->status->value) {
                        'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'dipinjam' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                        default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                    };
                @endphp
                <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $badge }}">{{ $item->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">EPC Tag UHF RFID</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1 break-all">{{ $item->epc_tag ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Tipe Barang</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1">{{ $item->type->label() }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Stok Tersedia</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1">{{ $item->stock }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Lokasi / Rak</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1">{{ $item->location ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if($item->type === \App\Enums\ItemType::BAHAN_HABIS_PAKAI)
            <!-- Buku Stok Bahan Habis Pakai -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Buku Stok &amp; Mutasi Bahan (Stock Ledger)</h3>
                        <p class="text-[11px] text-neutral-500">Catat penambahan stok masuk atau pengambilan bahan praktikum secara tercatat.</p>
                    </div>
                    <a href="{{ route('stock-movements.index', ['item_id' => $item->id]) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                        Buka Buku Stok Penuh
                    </a>
                </div>

                <div class="p-4 border-b border-neutral-100 bg-white">
                    @if($errors->any())
                        <div class="mb-3 bg-rose-50 border border-rose-200 text-rose-800 px-3 py-2 rounded-lg text-xs">
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
                            <select id="item-stock-type" name="type" class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                                <option value="out">Keluar (Pakai)</option>
                                <option value="in">Masuk (Tambah)</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="item-stock-qty" value="Jumlah *" />
                            <x-text-input id="item-stock-qty" name="quantity" type="number" min="1" value="1" required class="mt-1 w-full font-mono text-xs" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input-label for="item-stock-recipient" value="Pengambil / Kelas" />
                            <x-text-input id="item-stock-recipient" name="recipient_name" type="text" placeholder="Contoh: XI SIJA 1" class="mt-1 w-full text-xs" />
                        </div>
                        <div class="md:col-span-3">
                            <x-input-label for="item-stock-desc" value="Keterangan *" />
                            <x-text-input id="item-stock-desc" name="description" type="text" required placeholder="Praktikum / pengadaan" class="mt-1 w-full text-xs" />
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                                Catat Mutasi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Saldo Stok</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pengambil &amp; Keterangan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pencatat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200/70">
                            @forelse($item->stockMovements as $mv)
                                <tr class="table-row-clean">
                                    <td class="px-4 py-3 font-mono text-xs text-neutral-600 whitespace-nowrap">{{ optional($mv->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($mv->type->value === 'in')
                                            <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">MASUK</span>
                                        @else
                                            <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200">KELUAR</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-xs {{ $mv->type->value === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $mv->type->value === 'in' ? '+' : '-' }}{{ $mv->quantity }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-xs text-neutral-600 whitespace-nowrap">
                                        {{ $mv->stock_before }} &rarr; <span class="font-bold text-neutral-900">{{ $mv->stock_after }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-neutral-700">
                                        @if($mv->recipient_name)
                                            <span class="font-semibold text-neutral-900">{{ $mv->recipient_name }}:</span>
                                        @endif
                                        {{ $mv->description }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-neutral-600 whitespace-nowrap">{{ $mv->user->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-xs text-neutral-500">Belum ada riwayat mutasi stok untuk bahan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Riwayat Peminjaman -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Riwayat Peminjaman</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Peminjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Dipinjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Batas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Dikembalikan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($item->loans as $loan)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3 text-xs font-medium text-neutral-900">{{ $loan->user->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600">{{ optional($loan->borrowed_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600">{{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600">{{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200">{{ $loan->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-xs text-neutral-500">Belum ada riwayat peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Maintenance -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Riwayat Maintenance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Deskripsi Kendala</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pelapor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($item->tickets as $ticket)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3 text-xs text-neutral-800 max-w-md truncate">{{ $ticket->issue_description }}</td>
                                <td class="px-4 py-3 text-xs text-neutral-700">{{ $ticket->reportedBy->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $tbadge = match($ticket->status->value) {
                                            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $tbadge }}">{{ $ticket->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-neutral-500">Belum ada riwayat maintenance.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
