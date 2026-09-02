<x-app-layout>
    <div class="space-y-4">
        <p class="text-neutral-500 text-sm">Riwayat peminjaman alat laboratorium</p>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Peminjam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Dipinjam</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jatuh Tempo</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kondisi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($loans as $loan)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <div class="text-neutral-800 font-medium">{{ $loan->user->name ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500 font-mono">{{ $loan->user->nis_nip ?? '' }}</div>
                                </td>
                                <td class="px-5 py-3 text-neutral-700">{{ $loan->item->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ $loan->usage_duration?->label() ?? '—' }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ optional($loan->borrowed_at)->translatedFormat('d M Y, H:i') }}</td>
                                <td class="px-5 py-3">
                                    @if($loan->status->value === 'aktif' && $loan->due_at?->isPast())
                                        <span class="text-danger font-semibold text-xs">{{ optional($loan->due_at)->translatedFormat('d M Y, H:i') }}</span>
                                    @else
                                        <span class="text-neutral-600">{{ optional($loan->due_at)->translatedFormat('d M Y, H:i') ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if($loan->condition_in)
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $loan->condition_in === 'bagus' ? 'bg-success/10 text-success-dark' : 'bg-danger/10 text-danger' }}">
                                            {{ $loan->condition_in === 'bagus' ? 'Bagus' : 'Rusak' }}
                                        </span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $lbadge = match($loan->status->value) {
                                            'aktif' => 'bg-info/10 text-info-dark',
                                            'menunggu_approval_kembali' => 'bg-warning/10 text-warning-dark',
                                            'selesai' => 'bg-success/10 text-success-dark',
                                            default => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $lbadge }}">{{ $loan->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('loans.show', $loan->id) }}" class="text-primary hover:underline text-xs">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-neutral-400">Belum ada data peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
