<x-app-layout>
    <x-slot name="heading">Detail Peminjaman #{{ $loan->id }}</x-slot>
    <x-slot name="title">Detail Peminjaman #{{ $loan->id }} — SILAMET</x-slot>

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div class="flex items-center gap-3">
                <a href="{{ $loan->status->value === 'selesai' ? route('loans.history') : route('loans.index') }}"
                   class="p-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm inline-flex items-center gap-1.5"
                   title="Kembali">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="text-xs font-semibold pr-1">{{ $loan->status->value === 'selesai' ? 'Kembali ke Histori' : 'Kembali ke Peminjaman Aktif' }}</span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                            Transaksi Peminjaman <span class="font-mono">#{{ $loan->id }}</span>
                        </h1>
                    </div>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5">Waktu pinjam: {{ optional($loan->borrowed_at)->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($loan->status->value !== 'selesai')
                    <a href="{{ route('loans.edit', $loan->id) }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-2xs inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Ubah Status / Kembali</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('loans.destroy', $loan->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan peminjaman ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-rose-50 text-rose-600 hover:border-rose-200 text-xs font-semibold transition-colors shadow-2xs inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus Peminjaman</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Peminjaman (Rounded-2xl Card) -->
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-sm">
            <div class="flex items-start justify-between flex-wrap gap-4 pb-5 border-b border-neutral-100">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 font-bold text-neutral-700 text-sm font-heading">
                        {{ strtoupper(substr($loan->user->name ?? 'S', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">{{ $loan->user->name ?? '-' }}</h2>
                        <div class="flex items-center gap-2 text-xs text-neutral-500 font-mono mt-0.5">
                            <span>NIS: {{ $loan->user->nis_nip ?? '—' }}</span>
                            <span>&middot;</span>
                            <span>{{ $loan->user->phone ?? '(tanpa nomor WA)' }}</span>
                        </div>
                    </div>
                </div>
                @php
                    $lbadge = match($loan->status->value) {
                        'aktif' => 'bg-sky-50 text-sky-700 border-sky-200',
                        'menunggu_approval_kembali' => 'bg-amber-50 text-amber-800 border-amber-200',
                        'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                    };
                @endphp
                <span class="text-xs font-semibold px-3 py-1 rounded-full border {{ $lbadge }}">{{ $loan->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-5 text-sm">
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading">Barang Dipinjam</dt>
                    <dd class="font-bold text-neutral-900 text-xs mt-1.5 font-heading">
                        {{ $loan->item->name ?? '-' }}
                        @if(($loan->quantity_borrowed ?? 1) > 1 || optional($loan->item)->type?->value === 'bahan_habis_pakai')
                            <span class="ml-1 px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                {{ $loan->quantity_borrowed ?? 1 }} pcs
                            </span>
                        @endif
                    </dd>
                    <dd class="text-[11px] text-neutral-600 font-mono mt-0.5">{{ $loan->item->item_code ?? '—' }}</dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading">Durasi Pemakaian</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1.5">{{ $loan->usage_duration?->label() ?? '—' }}</dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading">Batas Jatuh Tempo</dt>
                    <dd class="font-semibold font-mono text-xs mt-1.5 {{ $loan->status->value === 'aktif' && $loan->due_at?->isPast() ? 'text-rose-700' : 'text-neutral-900' }}">
                        {{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}
                    </dd>
                    @if($loan->status->value === 'aktif' && $loan->due_at?->isPast())
                        <dd class="text-[10px] font-semibold text-rose-700 mt-1 inline-flex items-center gap-1 font-mono">
                            <svg class="w-3 h-3 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>MELEWATI BATAS</span>
                        </dd>
                    @endif
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading">Waktu Dikembalikan</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1.5">{{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div class="p-3.5 rounded-xl bg-neutral-50/70 border border-neutral-200/80">
                    <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading">Kondisi Saat Kembali</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1.5">
                        @if($loan->condition_in)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $loan->condition_in === 'bagus' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                {{ $loan->condition_in === 'bagus' ? 'Bagus' : 'Rusak' }}
                            </span>
                        @else
                            <span class="font-mono text-neutral-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if($loan->condition_in === 'rusak')
                @php
                    $damageExplanation = $loan->damageNote();
                @endphp
                <div class="mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200">
                    <div class="flex items-center gap-2 text-rose-800 font-bold text-xs font-heading">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Penjelasan Kerusakan / Kendala Saat Pengembalian:</span>
                    </div>
                    <p class="mt-1.5 text-xs text-rose-900 leading-relaxed font-medium">
                        {{ $damageExplanation ?: 'Barang dilaporkan rusak saat pengembalian (tidak ada rincian catatan tambahan).' }}
                    </p>
                </div>
            @endif

            <div class="mt-6 pt-5 border-t border-neutral-100">
                <dt class="text-neutral-500 text-[11px] font-bold uppercase tracking-wider font-heading mb-3">Foto Verifikasi Peminjam (Kiosk)</dt>
                @if($loan->capture_image_path)
                    <div class="rounded-2xl border border-neutral-200 overflow-hidden bg-neutral-950 max-w-md flex items-center justify-center min-h-[180px] shadow-sm">
                        <img src="{{ route('loans.photo', $loan->id) }}"
                             alt="Foto Verifikasi Peminjam"
                             class="w-full max-h-96 object-contain"
                             onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                        <div class="hidden p-6 text-center text-xs text-neutral-400 font-mono">
                            File foto verifikasi peminjam tidak ditemukan di penyimpanan server.
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl border border-neutral-200 bg-neutral-50 max-w-md flex items-center gap-3 text-xs text-neutral-500">
                        <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Foto verifikasi tidak tersedia (transaksi manual / arsip telah dibersihkan).</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>