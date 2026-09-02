<x-app-layout>
    @php
        $activeTab = request('status', 'open');
        $tabs = [
            'open' => ['label' => 'Open', 'count' => $counts['open'] ?? 0],
            'in_progress' => ['label' => 'In Progress', 'count' => $counts['in_progress'] ?? 0],
            'resolved' => ['label' => 'Resolved', 'count' => $counts['resolved'] ?? 0],
        ];
    @endphp

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-neutral-500 text-sm">Trouble ticketing & perbaikan aset laboratorium</p>
            <a href="{{ route('maintenance.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">
                + Buat Tiket
            </a>
        </div>

        <!-- Tabs -->
        <div class="inline-flex bg-white border border-neutral-200 rounded-lg p-1 text-sm">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('maintenance.index', ['status' => $key]) }}"
                   class="px-4 py-1.5 rounded-md transition {{ $activeTab === $key ? 'bg-primary text-white font-medium' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    {{ $tab['label'] }} <span class="opacity-70">({{ $tab['count'] }})</span>
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Deskripsi Kendala</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pelapor</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Dibuat</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-neutral-800">{{ $ticket->item->name ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500 font-mono">{{ $ticket->item->item_code ?? '' }}</div>
                                </td>
                                <td class="px-5 py-3 text-neutral-600 max-w-md">
                                    <span class="line-clamp-2">{{ $ticket->issue_description }}</span>
                                </td>
                                <td class="px-5 py-3 text-neutral-600">{{ $ticket->reportedBy->name ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $badge = match($ticket->status->value) {
                                            'open' => 'bg-danger/10 text-danger',
                                            'in_progress' => 'bg-warning/10 text-warning-dark',
                                            'resolved' => 'bg-success/10 text-success-dark',
                                            default => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $ticket->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-neutral-600 whitespace-nowrap">{{ optional($ticket->created_at)->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('maintenance.show', $ticket->id) }}" class="text-primary hover:underline text-xs">Detail</a>
                                    @if($ticket->status->value === 'open')
                                        <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="in_progress">
                                            <button type="submit" class="text-warning-dark hover:underline text-xs">Mulai Perbaikan</button>
                                        </form>
                                    @elseif($ticket->status->value === 'in_progress')
                                        <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="resolved">
                                            <button type="submit" class="text-success-dark hover:underline text-xs">Selesai & Uji Layak</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-neutral-400">Tidak ada tiket dengan status ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">
                    {{ $tickets->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
