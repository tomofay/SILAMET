<x-app-layout>
    <x-slot name="heading">Detail Tiket M&R #{{ $ticket->id }}</x-slot>
    <x-slot name="title">Tiket #{{ $ticket->id }}</x-slot>

    <div class="space-y-4 max-w-4xl">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Tiket</span>
            </a>
            <a href="{{ route('maintenance.edit', $ticket->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Catat Log Perbaikan</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <div class="flex items-start justify-between flex-wrap gap-3 pb-4 border-b border-neutral-100">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900 tracking-tight">Tiket <span class="font-mono">#{{ $ticket->id }}</span> — {{ $ticket->item->name ?? '-' }}</h2>
                    <p class="text-xs text-neutral-500 mt-1">
                        Dilaporkan oleh <span class="font-semibold text-neutral-800">{{ $ticket->reportedBy->name ?? '-' }}</span>
                        <span class="mx-1 text-neutral-300">|</span>
                        <span class="font-mono">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</span>
                    </p>
                </div>
                @php
                    $badge = match($ticket->status->value) {
                        'open' => 'bg-rose-50 text-rose-700 border-rose-200',
                        'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
                        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                    };
                @endphp
                <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $badge }}">{{ $ticket->status->label() }}</span>
            </div>

            <div class="mt-4 p-4 bg-neutral-50 border border-neutral-200/70 rounded-lg">
                <p class="text-[11px] text-neutral-500 uppercase tracking-wider font-semibold mb-1.5">Deskripsi Kendala</p>
                <p class="text-neutral-800 text-xs leading-relaxed">{{ $ticket->issue_description }}</p>
            </div>

            <div class="mt-4 flex items-center gap-2">
                @if($ticket->status->value === 'open')
                    <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline-block">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg border border-amber-700 transition-colors">Mulai Perbaikan</button>
                    </form>
                @elseif($ticket->status->value === 'in_progress')
                    <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="inline-block">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="resolved">
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-600 transition-colors">Selesai & Uji Layak</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Riwayat Log Perbaikan -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Riwayat Tindakan Perbaikan</h3>
            </div>
            <div class="divide-y divide-neutral-200/70">
                @forelse($ticket->logs as $log)
                    <div class="p-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <p class="text-xs font-semibold text-neutral-900">{{ $log->handledBy->name ?? 'Teknisi' }}</p>
                            <span class="font-mono text-xs text-neutral-500">{{ optional($log->repaired_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="text-xs text-neutral-700 mt-1.5">{{ $log->action_taken }}</p>
                        <div class="mt-2.5 flex items-center gap-2.5 text-xs">
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $log->service_type->value === 'internal' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                {{ $log->service_type->label() }}
                            </span>
                            @if((float) $log->cost > 0)
                                <span class="font-mono text-neutral-800 font-semibold">Rp {{ number_format($log->cost, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-neutral-500 text-xs">Belum ada catatan tindakan perbaikan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
