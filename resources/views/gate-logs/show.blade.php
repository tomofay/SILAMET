<x-app-layout>
    <x-slot name="heading">Detail Log Gate #{{ $log->id }}</x-slot>
    <x-slot name="title">Detail Log Gate #{{ $log->id }}</x-slot>

    <div class="space-y-6 max-w-4xl">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('gate-logs.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">
                            Log Deteksi Gate <span class="font-mono">#{{ $log->id }}</span>
                        </h1>
                        @if($log->action_status->value === 'allowed')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>ALLOWED (AKSES SAH)</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <span>DENIED (PELANGGARAN)</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-neutral-500 mt-0.5">Rincian pembacaan tag UHF RFID gerbang dan bukti tangkapan kamera.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('gate-logs.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Kembali ke Log Pintu
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
            <!-- Data Grid (Left 7 Cols) -->
            <div class="md:col-span-7 space-y-6">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                    <h2 class="text-sm font-bold text-neutral-900 tracking-tight border-b border-neutral-100 pb-3">Informasi Telemetri RFID</h2>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-neutral-50 border border-neutral-200/80">
                            <dt class="text-neutral-500 text-[10px] font-bold uppercase tracking-wider">Waktu Deteksi</dt>
                            <dd class="font-bold text-neutral-900 font-mono text-sm mt-1">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</dd>
                        </div>
                        <div class="p-3.5 rounded-xl bg-neutral-50 border border-neutral-200/80">
                            <dt class="text-neutral-500 text-[10px] font-bold uppercase tracking-wider">Status Respon Gate</dt>
                            <dd class="font-bold font-mono text-sm mt-1 {{ $log->action_status->value === 'allowed' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ strtoupper($log->action_status->value) }}
                            </dd>
                        </div>
                        <div class="p-3.5 rounded-xl bg-neutral-50 border border-neutral-200/80 sm:col-span-2">
                            <dt class="text-neutral-500 text-[10px] font-bold uppercase tracking-wider">EPC Tag Terbaca (Antena Gerbang)</dt>
                            <dd class="font-bold text-neutral-900 font-mono text-xs mt-1 break-all bg-white p-2 rounded-lg border border-neutral-200">
                                {{ $log->epc_tag_scanned }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-neutral-900 tracking-tight border-b border-neutral-100 pb-3">Entitas Barang Terdeteksi</h2>

                    @if($log->item)
                        <div class="flex items-start gap-3.5 p-4 rounded-xl bg-neutral-50 border border-neutral-200/80">
                            <div class="w-10 h-10 rounded-xl bg-white border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-700 shadow-2xs">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-neutral-900 text-sm truncate">{{ $log->item->name }}</h3>
                                    @if($log->item->trashed())
                                        <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded-full bg-neutral-200 text-neutral-600">DIHAPUS</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500 font-mono">
                                    <span>{{ $log->item->item_code }}</span>
                                    <span>·</span>
                                    <span>Lokasi: {{ $log->item->location ?? 'Lab SIJA' }}</span>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('items.show', $log->item->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-900 hover:underline">
                                        <span>Buka Detail Master Aset</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                            <span class="font-bold block">Tag Asing / Tidak Terdaftar di Database</span>
                            <p class="text-rose-700 text-[11px]">EPC tag ini belum diasosiasikan dengan barang apapun di Master Aset laboratorium.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Snapshot Photo (Right 5 Cols) -->
            <div class="md:col-span-5 space-y-5">
                <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Bukti Snapshot Kamera Gerbang</h3>

                    @if($log->capture_image_path)
                        <div class="rounded-xl border border-neutral-200 overflow-hidden bg-neutral-950 flex items-center justify-center min-h-[220px]">
                            <img src="{{ route('gate-logs.photo', $log->id) }}"
                                 alt="Foto pelanggar gerbang"
                                 class="w-full max-h-96 object-contain"
                                 onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                            <div class="hidden p-6 text-center text-xs text-neutral-400 font-mono">
                                Berkas foto snapshot tidak ditemukan di storage server.
                            </div>
                        </div>
                        <p class="text-[11px] text-neutral-500">
                            Foto diambil secara otomatis oleh daemon Python saat sensor gerbang mendeteksi insiden.
                        </p>
                    @else
                        <div class="p-6 rounded-xl border border-dashed border-neutral-200 bg-neutral-50 text-center space-y-2">
                            <svg class="w-8 h-8 text-neutral-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="text-xs text-neutral-500 font-medium">Foto snapshot tidak tersedia.</p>
                            <p class="text-[11px] text-neutral-400">Tidak ada tangkapan webcam pada insiden ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
