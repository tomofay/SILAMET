<x-app-layout>
    <div class="space-y-5 max-w-4xl">
        <a href="{{ route('maintenance.index') }}" class="text-sm text-primary hover:underline">← Kembali ke tiket</a>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-bold text-neutral-800">Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}</h2>
                    <p class="text-sm text-neutral-500 mt-0.5">Dilaporkan oleh {{ $ticket->reportedBy->name ?? '-' }} · {{ optional($ticket->created_at)->translatedFormat('d M Y, H:i') }}</p>
                </div>
                @php
                    $badge = match($ticket->status->value) {
                        'open' => 'bg-danger/10 text-danger',
                        'in_progress' => 'bg-warning/10 text-warning-dark',
                        'resolved' => 'bg-success/10 text-success-dark',
                        default => 'bg-neutral-100 text-neutral-600',
                    };
                @endphp
                <span class="text-sm font-semibold px-3 py-1 rounded-full {{ $badge }}">{{ $ticket->status->label() }}</span>
            </div>

            <div class="mt-4 p-4 bg-neutral-50 rounded-md">
                <p class="text-xs text-neutral-500 uppercase font-semibold mb-1">Deskripsi Kendala</p>
                <p class="text-neutral-800 text-sm">{{ $ticket->issue_description }}</p>
            </div>

            @if($ticket->status->value === 'open')
                <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="mt-4 inline-block">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" class="px-4 py-2 bg-warning text-white text-sm font-medium rounded-md hover:opacity-90">Mulai Perbaikan</button>
                </form>
            @elseif($ticket->status->value === 'in_progress')
                <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="mt-4 inline-block">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="resolved">
                    <button type="submit" class="px-4 py-2 bg-success text-white text-sm font-medium rounded-md hover:opacity-90">Selesai & Uji Layak</button>
                </form>
            @endif
        </div>

        <!-- Riwayat Log Perbaikan -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-200">
                <h3 class="font-semibold text-neutral-800">Riwayat Tindakan Perbaikan</h3>
            </div>
            <div class="divide-y divide-neutral-100">
                @forelse($ticket->logs as $log)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <p class="text-sm font-medium text-neutral-800">{{ $log->handledBy->name ?? 'Teknisi' }}</p>
                            <span class="text-xs text-neutral-500">{{ optional($log->repaired_at)->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <p class="text-sm text-neutral-600 mt-1">{{ $log->action_taken }}</p>
                        <div class="mt-2 flex items-center gap-3 text-xs">
                            <span class="px-2 py-0.5 rounded-full {{ $log->service_type->value === 'internal' ? 'bg-primary/10 text-primary-dark' : 'bg-warning/10 text-warning-dark' }}">
                                {{ $log->service_type->label() }}
                            </span>
                            @if((float) $log->cost > 0)
                                <span class="text-neutral-700 font-semibold">Rp {{ number_format($log->cost, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-neutral-400 text-sm">Belum ada catatan tindakan perbaikan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
