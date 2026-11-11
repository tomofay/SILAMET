<x-app-layout>
    <x-slot name="heading">Detail Log Gate #{{ $log->id }}</x-slot>
    <x-slot name="title">Detail Log Gate #{{ $log->id }}</x-slot>

    <div class="space-y-4 max-w-3xl">
        <div>
            <a href="{{ route('gate-logs.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Log Pintu</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <div class="flex items-center justify-between pb-4 border-b border-neutral-100">
                <h2 class="text-lg font-bold text-neutral-900 tracking-tight">Detail Log Gate <span class="font-mono">#{{ $log->id }}</span></h2>
                @if($log->action_status->value === 'allowed')
                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">ALLOWED</span>
                @else
                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200">DENIED</span>
                @endif
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 text-sm">
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Waktu Deteksi</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">EPC Tag Terbaca</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1">{{ $log->epc_tag_scanned }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70 sm:col-span-2">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Barang Terdeteksi</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1 flex items-center gap-1.5">
                        <span>{{ $log->item->name ?? 'Tag Asing / Tidak Terdaftar' }}</span>
                        @if($log->item && $log->item->trashed())
                            <span class="font-mono text-[10px] font-semibold px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-500 border border-neutral-200">DIHAPUS</span>
                        @endif
                    </dd>
                    @if($log->item)
                        <dd class="font-mono text-[11px] text-neutral-500 mt-0.5">{{ $log->item->item_code }} · Rak: {{ $log->item->location ?? '-' }}</dd>
                    @endif
                </div>
            </dl>

            @if($log->capture_image_path)
                <div class="mt-5 pt-4 border-t border-neutral-100">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Snapshot Kamera Gerbang</dt>
                    <div class="rounded-lg border border-neutral-200 overflow-hidden bg-neutral-950 max-w-md flex items-center justify-center min-h-[180px]">
                        <img src="{{ route('gate-logs.photo', $log->id) }}"
                             alt="Foto pelanggar"
                             class="w-full max-h-96 object-contain"
                             onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                        <div class="hidden p-6 text-center text-xs text-neutral-400 font-mono">
                            File foto snapshot tidak ditemukan di penyimpanan server.
                        </div>
                    </div>
                </div>
            @elseif($log->action_status->value === 'denied')
                <div class="mt-5 pt-4 border-t border-neutral-100">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Snapshot Kamera Gerbang</dt>
                    <div class="p-4 rounded-lg border border-neutral-200 bg-neutral-50 max-w-md flex items-center gap-3 text-xs text-neutral-500">
                        <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Foto snapshot tidak tersedia (kamera off atau arsip lama telah dipruning).</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
