<x-app-layout>
    <x-slot name="heading">Detail Peminjaman #{{ $loan->id }}</x-slot>
    <x-slot name="title">Detail Peminjaman #{{ $loan->id }}</x-slot>

    <div class="space-y-4 max-w-4xl">
        <div>
            <a href="{{ route('loans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Peminjaman</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <div class="flex items-start justify-between flex-wrap gap-4 pb-4 border-b border-neutral-100">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900 tracking-tight">Transaksi Peminjaman <span class="font-mono">#{{ $loan->id }}</span></h2>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5">{{ optional($loan->borrowed_at)->format('d/m/Y H:i') }}</p>
                </div>
                @php
                    $lbadge = match($loan->status->value) {
                        'aktif' => 'bg-brand-50 text-brand-700 border-brand-100',
                        'menunggu_approval_kembali' => 'bg-amber-50 text-amber-800 border-amber-200',
                        'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                    };
                @endphp
                <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $lbadge }}">{{ $loan->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-4 text-sm">
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Peminjam</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1">{{ $loan->user->name ?? '-' }}</dd>
                    <dd class="text-[11px] text-neutral-600 font-mono mt-0.5">{{ $loan->user->nis_nip ?? '—' }}</dd>
                    <dd class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $loan->user->phone ?? '(tanpa nomor WA)' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Barang</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1">{{ $loan->item->name ?? '-' }}</dd>
                    <dd class="text-[11px] text-neutral-600 font-mono mt-0.5">{{ $loan->item->item_code ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Durasi Pemakaian</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1">{{ $loan->usage_duration?->label() ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Jatuh Tempo</dt>
                    <dd class="font-semibold font-mono text-xs mt-1 {{ $loan->status->value === 'aktif' && $loan->due_at?->isPast() ? 'text-rose-700' : 'text-neutral-900' }}">
                        {{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}
                    </dd>
                    @if($loan->status->value === 'aktif' && $loan->due_at?->isPast())
                        <dd class="text-[11px] font-semibold text-rose-700 mt-1 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Melewati Batas</span>
                        </dd>
                    @endif
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Dikembalikan</dt>
                    <dd class="font-semibold text-neutral-900 font-mono text-xs mt-1">{{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200/70">
                    <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider">Kondisi Saat Kembali</dt>
                    <dd class="font-semibold text-neutral-900 text-xs mt-1">
                        @if($loan->condition_in)
                            <span class="px-2 py-0.5 rounded border {{ $loan->condition_in === 'bagus' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                {{ $loan->condition_in === 'bagus' ? 'Bagus' : 'Rusak' }}
                            </span>
                        @else
                            <span class="font-mono text-neutral-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-5 pt-4 border-t border-neutral-100">
                <dt class="text-neutral-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Foto Verifikasi Peminjam (Kiosk)</dt>
                @if($loan->capture_image_path)
                    <div class="rounded-lg border border-neutral-200 overflow-hidden bg-neutral-950 max-w-md flex items-center justify-center min-h-[180px]">
                        <img src="{{ route('loans.photo', $loan->id) }}"
                             alt="Foto Verifikasi Peminjam"
                             class="w-full max-h-96 object-contain"
                             onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                        <div class="hidden p-6 text-center text-xs text-neutral-400 font-mono">
                            File foto verifikasi peminjam tidak ditemukan di penyimpanan server.
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-lg border border-neutral-200 bg-neutral-50 max-w-md flex items-center gap-3 text-xs text-neutral-500">
                        <svg class="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Foto verifikasi tidak tersedia (transaksi manual / arsip telah dibersihkan).</span>
                    </div>
                @endif
            </div>
        </div>

        @if(in_array($loan->status->value, ['aktif', 'menunggu_approval_kembali'], true))
            <div class="bg-white rounded-xl border border-neutral-200 p-5">
                <h3 class="font-bold text-neutral-900 text-sm mb-3">Perbarui Status Peminjaman</h3>
                <form method="POST" action="{{ route('loans.update', $loan->id) }}" class="flex items-center gap-3 flex-wrap">
                    @csrf
                    @method('PUT')
                    <select name="status" aria-label="Status Peminjaman" class="rounded-lg border-neutral-200 text-xs px-3 py-2 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="aktif" @selected($loan->status->value === 'aktif')>Aktif</option>
                        <option value="menunggu_approval_kembali" @selected($loan->status->value === 'menunggu_approval_kembali')>Menunggu Approval Kembali</option>
                        <option value="selesai" @selected($loan->status->value === 'selesai')>Selesai</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Perubahan</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
