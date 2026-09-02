<x-app-layout>
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <a href="{{ route('items.index') }}" class="text-sm text-primary hover:underline">← Kembali ke daftar</a>
            <a href="{{ route('items.edit', $item->id) }}" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Edit Barang</a>
        </div>

        <!-- Info Barang -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl font-bold text-neutral-800">{{ $item->name }}</h2>
                    <p class="text-sm text-neutral-500 mt-0.5 font-mono">{{ $item->item_code }}</p>
                </div>
                @php
                    $badge = match($item->status->value) {
                        'tersedia' => 'bg-success/10 text-success-dark',
                        'dipinjam' => 'bg-info/10 text-info-dark',
                        'under_maintenance' => 'bg-danger/10 text-danger',
                        default => 'bg-neutral-100 text-neutral-600',
                    };
                @endphp
                <span class="text-sm font-semibold px-3 py-1 rounded-full {{ $badge }}">{{ $item->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-sm">
                <div>
                    <dt class="text-neutral-500 text-xs">EPC Tag</dt>
                    <dd class="font-medium text-neutral-800 font-mono text-xs mt-1">{{ $item->epc_tag ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Tipe</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $item->type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Stok</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $item->stock }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Lokasi</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $item->location }}</dd>
                </div>
            </dl>
        </div>

        <!-- Riwayat Peminjaman -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200">
                <h3 class="font-semibold text-neutral-800">Riwayat Peminjaman</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Peminjam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Dipinjam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Batas</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Dikembalikan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($item->loans as $loan)
                            <tr>
                                <td class="px-5 py-3 text-neutral-700">{{ $loan->user->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ optional($loan->borrowed_at)->translatedFormat('d M Y, H:i') }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ optional($loan->due_at)->translatedFormat('d M Y, H:i') }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ optional($loan->returned_at)->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ $loan->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-6 text-center text-neutral-400">Belum ada riwayat peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Maintenance -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200">
                <h3 class="font-semibold text-neutral-800">Riwayat Maintenance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Deskripsi Kendala</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Pelapor</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($item->tickets as $ticket)
                            <tr>
                                <td class="px-5 py-3 text-neutral-700 max-w-md truncate">{{ $ticket->issue_description }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ $ticket->reportedBy->name ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $tbadge = match($ticket->status->value) {
                                            'open' => 'bg-danger/10 text-danger',
                                            'in_progress' => 'bg-warning/10 text-warning-dark',
                                            'resolved' => 'bg-success/10 text-success-dark',
                                            default => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $tbadge }}">{{ $ticket->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-neutral-600">{{ optional($ticket->created_at)->translatedFormat('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-neutral-400">Belum ada riwayat maintenance.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
