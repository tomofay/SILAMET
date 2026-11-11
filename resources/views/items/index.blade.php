<x-app-layout>
    <x-slot name="heading">Katalog Master Aset</x-slot>
    <x-slot name="title">Master Aset</x-slot>

    <div class="space-y-4">

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Mutasi stok tidak dapat diproses:</span>
                </div>
                <ul class="list-disc pl-6 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header Actions -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Inventaris Alat & Bahan Praktikum</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Kelola mikrotik, router, switch, kabel, dan modul trainer Lab SIJA.</p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('stock-movements.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Buku Stok (BHP)</span>
                </a>
                <a href="{{ route('items.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Barang</span>
                </a>
                <a href="{{ route('items.barcodes') }}" target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg border border-neutral-800 transition-colors">
                    <svg class="w-4 h-4 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Cetak Semua Barcode</span>
                </a>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div id="bulk-actions-items" class="hidden bg-primary-50 border border-primary-100 rounded-xl p-3.5 flex items-center justify-between gap-3">
            <span class="text-xs font-semibold text-primary-700">
                <span id="selected-item-count" class="font-mono font-bold text-sm">0</span> barang dipilih untuk tindakan massal
            </span>
            <button id="btn-print-selected" type="button" class="px-3.5 py-2 bg-primary hover:bg-primary-700 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Barcode Terpilih</span>
            </button>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider w-10">
                                <input type="checkbox" id="select-all-items" aria-label="Pilih semua barang" class="rounded border-neutral-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kode Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Nama Barang & Tag RFID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Stok</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Lokasi Rak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($items as $item)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <input type="checkbox" aria-label="Pilih {{ $item->name }}" class="item-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $item->id }}">
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-800 font-mono text-xs font-semibold border border-neutral-200">
                                        {{ $item->item_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $item->name }}</div>
                                    @if($item->epc_tag)
                                        <div class="text-[11px] text-neutral-500 font-mono mt-0.5">EPC: {{ $item->epc_tag }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $item->type->value === 'aset' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                        {{ $item->type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-semibold text-xs">
                                    @if($item->type->value === 'bahan_habis_pakai' && $item->stock <= 0)
                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">0 (Habis)</span>
                                    @elseif($item->type->value === 'bahan_habis_pakai' && $item->stock <= 5)
                                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">{{ $item->stock }} (Tipis)</span>
                                    @else
                                        <span class="text-neutral-800">{{ $item->stock }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-xs text-neutral-600 bg-neutral-50 px-2 py-0.5 rounded border border-neutral-200">
                                        {{ $item->location ?? 'Rak Utama' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $badge = match($item->status->value) {
                                            'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'dipinjam' => 'bg-brand-50 text-brand-700 border-brand-100',
                                            'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $badge }}">
                                        {{ $item->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        @if($item->type->value === 'bahan_habis_pakai')
                                            <button type="button"
                                                class="open-stock-modal btn-icon-action text-amber-700 hover:text-amber-900 hover:bg-amber-50 hover:border-amber-200"
                                                data-item-id="{{ $item->id }}"
                                                data-item-name="{{ $item->name }}"
                                                data-item-code="{{ $item->item_code }}"
                                                data-item-stock="{{ $item->stock }}"
                                                title="Catat Stok Masuk / Keluar"
                                                aria-label="Mutasi Stok {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                </svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank" class="btn-icon-action" title="Cetak Barcode" aria-label="Cetak Barcode {{ $item->item_code }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('items.show', $item->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Aset" aria-label="Detail {{ $item->name }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('items.edit', $item->id) }}" class="btn-icon-action hover:text-neutral-900" title="Edit Aset" aria-label="Edit {{ $item->name }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Hapus barang ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon-action hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200" title="Hapus Aset" aria-label="Hapus {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Belum Ada Barang Terdata</p>
                                    <p class="text-xs text-neutral-500 mt-1">Klik tombol Tambah Barang untuk mulai mendaftarkan aset laboratorium.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $items->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Modal Catat Mutasi Stok Bahan Habis Pakai -->
    <div id="stock-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-neutral-950/60" id="stock-modal-backdrop"></div>
        <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 overflow-hidden">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="font-bold text-neutral-900 text-sm">Catat Mutasi Stok (BHP)</h3>
                    <p id="stock-modal-subtitle" class="text-[11px] font-mono text-neutral-500 mt-0.5"></p>
                </div>
                <button type="button" id="stock-modal-close" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100" aria-label="Tutup modal stok">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('stock-movements.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="item_id" id="stock-modal-item-id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="stock-modal-type" value="Jenis Mutasi *" />
                        <select id="stock-modal-type" name="type" class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary" required>
                            <option value="out">Keluar (Pakai)</option>
                            <option value="in">Masuk (Tambah)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="stock-modal-qty" value="Jumlah *" />
                        <x-text-input id="stock-modal-qty" name="quantity" type="number" min="1" value="1" required class="mt-1 w-full font-mono text-xs" />
                    </div>
                </div>

                <div>
                    <x-input-label for="stock-modal-recipient" value="Pengambil / Kelas / Vendor" />
                    <x-text-input id="stock-modal-recipient" name="recipient_name" type="text" placeholder="Contoh: XII SIJA 1 / Pembelian Lab" class="mt-1 w-full text-xs" />
                </div>

                <div>
                    <x-input-label for="stock-modal-desc" value="Keterangan *" />
                    <x-text-input id="stock-modal-desc" name="description" type="text" required placeholder="Contoh: Praktikum instalasi kabel LAN / Stok baru" class="mt-1 w-full text-xs" />
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" id="stock-modal-cancel" class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40">Simpan Mutasi</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-items');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const bulkBar = document.getElementById('bulk-actions-items');
            const countLabel = document.getElementById('selected-item-count');
            const btnPrint = document.getElementById('btn-print-selected');

            const stockModal = document.getElementById('stock-modal');
            const stockItemId = document.getElementById('stock-modal-item-id');
            const stockSubtitle = document.getElementById('stock-modal-subtitle');
            const stockClose = document.getElementById('stock-modal-close');
            const stockCancel = document.getElementById('stock-modal-cancel');
            const stockBackdrop = document.getElementById('stock-modal-backdrop');

            function openStockModal(btn) {
                stockItemId.value = btn.dataset.itemId;
                stockSubtitle.textContent = `${btn.dataset.itemName} (${btn.dataset.itemCode}) · Sisa stok: ${btn.dataset.itemStock}`;
                stockModal.classList.remove('hidden');
                stockModal.classList.add('flex');
            }

            function closeStockModal() {
                stockModal.classList.add('hidden');
                stockModal.classList.remove('flex');
            }

            document.querySelectorAll('.open-stock-modal').forEach(btn => {
                btn.addEventListener('click', () => openStockModal(btn));
            });
            if (stockClose) stockClose.addEventListener('click', closeStockModal);
            if (stockCancel) stockCancel.addEventListener('click', closeStockModal);
            if (stockBackdrop) stockBackdrop.addEventListener('click', closeStockModal);

            function updateBulkBar() {
                const checked = Array.from(checkboxes).filter(cb => cb.checked);
                countLabel.textContent = checked.length;
                if (checked.length > 0) {
                    bulkBar.classList.remove('hidden');
                } else {
                    bulkBar.classList.add('hidden');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    updateBulkBar();
                    if (selectAll) {
                        selectAll.checked = Array.from(checkboxes).every(c => c.checked);
                    }
                });
            });

            if (btnPrint) {
                btnPrint.addEventListener('click', function () {
                    const ids = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                    if (ids.length > 0) {
                        window.open("{{ route('items.barcodes') }}?ids=" + ids.join(','), '_blank');
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
