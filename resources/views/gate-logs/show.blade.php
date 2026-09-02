<x-app-layout>
    <div class="space-y-5 max-w-3xl">
        <a href="{{ route('gate-logs.index') }}" class="text-sm text-primary hover:underline">← Kembali ke log pintu</a>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
            <h2 class="text-xl font-bold text-neutral-800">Detail Log Gate</h2>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 text-sm">
                <div>
                    <dt class="text-neutral-500 text-xs">Waktu Deteksi</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ optional($log->created_at)->translatedFormat('d M Y, H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">EPC Tag</dt>
                    <dd class="font-medium text-neutral-800 font-mono mt-1">{{ $log->epc_tag_scanned }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Barang Terdeteksi</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $log->item->name ?? 'Tidak dikenal' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Status Akses</dt>
                    <dd class="mt-1">
                        @if($log->action_status->value === 'allowed')
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-success/10 text-success-dark">ALLOWED</span>
                        @else
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-danger/10 text-danger">DENIED</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if($log->capture_image_path)
                <div class="mt-6">
                    <dt class="text-neutral-500 text-xs mb-2">Foto Pelanggar</dt>
                    <img src="{{ asset('storage/' . $log->capture_image_path) }}" alt="Foto pelanggar" class="rounded-md border border-neutral-200 max-w-sm">
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
