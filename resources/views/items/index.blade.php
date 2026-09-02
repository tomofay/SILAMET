<x-app-layout>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-neutral-500 text-sm">Kelola katalog aset & bahan praktikum</p>
            </div>
            <a href="{{ route('items.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark transition">
                + Tambah Barang
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
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
                                <td colspan="7" class="px-5 py-8 text-center text-neutral-400">Belum ada barang. Klik "Tambah Barang".</td>
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
    </div>
</x-app-layout>
