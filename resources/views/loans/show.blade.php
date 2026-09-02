<x-app-layout>
    <div class="space-y-5 max-w-4xl">
        <a href="{{ route('loans.index') }}" class="text-sm text-primary hover:underline">← Kembali ke daftar peminjaman</a>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl font-bold text-neutral-800">Detail Peminjaman #{{ $loan->id }}</h2>
                    <p class="text-sm text-neutral-500 mt-0.5">{{ optional($loan->borrowed_at)->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <span class="text-sm font-semibold px-3 py-1 rounded-full bg-info/10 text-info-dark">{{ $loan->status->label() }}</span>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-3 gap-5 mt-6 text-sm">
                <div>
                    <dt class="text-neutral-500 text-xs">Peminjam</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $loan->user->name ?? '-' }}</dd>
                    <dd class="text-xs text-neutral-500 font-mono">{{ $loan->user->nis_nip ?? '' }}</dd>
                    <dd class="text-xs text-neutral-500">{{ $loan->user->phone ?? '(tanpa nomor WA)' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Barang</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $loan->item->name ?? '-' }}</dd>
                    <dd class="text-xs text-neutral-500 font-mono">{{ $loan->item->item_code ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Durasi Pemakaian</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ $loan->usage_duration?->label() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Jatuh Tempo</dt>
                    <dd class="font-medium text-neutral-800 mt-1 {{ $loan->status->value === 'aktif' && $loan->due_at?->isPast() ? 'text-danger' : '' }}">
                        {{ optional($loan->due_at)->translatedFormat('d M Y, H:i') ?? '—' }}
                    </dd>
                    @if($loan->status->value === 'aktif' && $loan->due_at?->isPast())
                        <dd class="text-xs text-danger mt-0.5">⚠️ Terlambat</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Dikembalikan</dt>
                    <dd class="font-medium text-neutral-800 mt-1">{{ optional($loan->returned_at)->translatedFormat('d M Y, H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500 text-xs">Kondisi Saat Kembali</dt>
                    <dd class="font-medium text-neutral-800 mt-1">
                        @if($loan->condition_in)
                            {{ $loan->condition_in === 'bagus' ? 'Bagus' : 'Rusak' }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>

            @if($loan->overdue_notified_at)
                <p class="mt-4 text-xs text-success">✓ Notifikasi keterlambatan WhatsApp telah dikirim pada {{ $loan->overdue_notified_at->translatedFormat('d M Y, H:i') }}</p>
            @endif
        </div>

        @if(in_array($loan->status->value, ['aktif', 'menunggu_approval_kembali'], true))
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
                <h3 class="font-semibold text-neutral-800 mb-3">Perbarui Status</h3>
                <form method="POST" action="{{ route('loans.update', $loan->id) }}" class="flex items-center gap-3">
                    @csrf
                    @method('PUT')
                    <select name="status" class="rounded-md border-neutral-300 shadow-sm text-sm focus:border-primary focus:ring-primary">
                        <option value="aktif" @selected($loan->status->value === 'aktif')>Aktif</option>
                        <option value="menunggu_approval_kembali" @selected($loan->status->value === 'menunggu_approval_kembali')>Menunggu Approval Kembali</option>
                        <option value="selesai" @selected($loan->status->value === 'selesai')>Selesai</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
