<x-app-layout>
    <x-slot name="heading">Master Aset</x-slot>
    <x-slot name="title">Master Aset</x-slot>

    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-neutral-500 text-sm">Kelola katalog aset & bahan praktikum</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('items.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark transition">
                    + Tambah Barang
                </a>
                <a href="{{ route('items.barcodes') }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-info text-white text-sm font-medium rounded-md hover:bg-info-dark transition">
                    🏷️ Cetak Barcode
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-items" class="rounded border-neutral-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kode</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Nama</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Stok</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($items as $item)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <input type="checkbox" class="item-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $item->id }}">
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-600">{{ $item->item_code }}</td>
                                <td class="px-5 py-3 font-medium text-neutral-800">{{ $item->name }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $item->type->value === 'aset' ? 'bg-primary/10 text-primary-dark' : 'bg-neutral-100 text-neutral-600' }}">
                                        {{ $item->type->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-neutral-700">{{ $item->stock }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ $item->location }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $badge = match($item->status->value) {
                                            'tersedia' => 'bg-success/10 text-success-dark',
                                            'dipinjam' => 'bg-info/10 text-info-dark',
                                            'under_maintenance' => 'bg-danger/10 text-danger',
                                            default => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $item->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank" class="text-info-dark hover:underline text-xs" title="Cetak Barcode">🏷️</a>
                                    <a href="{{ route('items.show', $item->id) }}" class="text-primary hover:underline text-xs">Detail</a>
                                    <a href="{{ route('items.edit', $item->id) }}" class="text-info-dark hover:underline text-xs">Edit</a>
                                    <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Hapus barang ini? (soft delete)')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger hover:underline text-xs">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-neutral-400">Belum ada barang. Klik "Tambah Barang".</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">
                    {{ $items->links() }}
                </div>
            @endif
        </div>

        {{-- Tombol cetak barcode terpilih --}}
        <div id="bulk-actions-items" class="hidden bg-primary/5 border border-primary/20 rounded-lg p-3 flex items-center gap-3">
            <span class="text-sm text-primary-dark font-medium"><span id="selected-item-count">0</span> barang dipilih</span>
            <a id="print-selected-items-btn" href="#" target="_blank"
                class="inline-flex items-center px-3 py-1.5 bg-info text-white text-xs font-medium rounded-md hover:bg-info-dark transition">
                🏷️ Cetak Barcode Terpilih
            </a>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-items');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const bulkActions = document.getElementById('bulk-actions-items');
            const selectedCount = document.getElementById('selected-item-count');
            const printBtn = document.getElementById('print-selected-items-btn');

            function updateBulk() {
                const checked = document.querySelectorAll('.item-checkbox:checked');
                const count = checked.length;
                selectedCount.textContent = count;
                bulkActions.classList.toggle('hidden', count === 0);

                if (count > 0) {
                    const ids = Array.from(checked).map(c => c.value).join(',');
                    printBtn.href = '{{ route("items.barcodes") }}?ids=' + ids;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulk();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateBulk));
        });
    </script>
    @endpush
</x-app-layout>
