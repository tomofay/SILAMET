<x-app-layout>
    <x-slot name="heading">Ubah Status Peminjaman #{{ $loan->id }}</x-slot>
    <x-slot name="title">Edit Peminjaman #{{ $loan->id }}</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors" title="Kembali ke Daftar Peminjaman">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">
                        Ubah Status Peminjaman <span class="font-mono">#{{ $loan->id }}</span>
                    </h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Penyesuaian status sirkulasi peminjaman alat secara administratif.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="edit-loan-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>

        <form id="edit-loan-form" method="POST" action="{{ route('loans.update', $loan->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
                 x-data="{
                     loanStatus: '{{ old('status', $loan->status->value) }}',
                     condition: '{{ old('condition_in', $loan->condition_in ?? 'bagus') }}'
                 }">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Status Sirkulasi</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Ubah status peminjaman alat.</p>
                        </div>

                        <div>
                            <x-input-label for="status" value="Status Peminjaman *" />
                            <select id="status" name="status" x-model="loanStatus" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                <option value="aktif">Aktif (Sedang Dipinjam)</option>
                                <option value="menunggu_approval_kembali">Menunggu Approval Kembali</option>
                                <option value="selesai">Selesai (Sudah Dikembalikan)</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <!-- Opsi Kondisi Barang Saat Kembali -->
                        <div x-show="loanStatus === 'selesai'" x-transition class="space-y-4 pt-4 border-t border-neutral-100">
                            <div>
                                <x-input-label for="condition_in" value="Kondisi Fisik Saat Pengembalian *" />
                                <select id="condition_in" name="condition_in" x-model="condition" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900">
                                    <option value="bagus">Kondisi Bagus / Normal</option>
                                    <option value="rusak">Rusak / Bermasalah</option>
                                </select>
                                <x-input-error :messages="$errors->get('condition_in')" class="mt-2" />
                            </div>

                            <div x-show="condition === 'rusak'" x-transition class="space-y-1.5">
                                <x-input-label for="damage_note" value="Penjelasan Kerusakan / Kendala Alat *" />
                                <textarea id="damage_note"
                                          name="damage_note"
                                          rows="3"
                                          placeholder="Jelaskan bagian alat yang rusak, patah, hilang kelengkapan, atau gagal fungsi..."
                                          class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-rose-600 focus:ring-rose-600">{{ old('damage_note', $loan->damageNote()) }}</textarea>
                                <p class="text-[11px] text-neutral-500">Penjelasan ini akan tercatat di histori peminjaman dan otomatis membuka tiket maintenance untuk teknisi.</p>
                                <x-input-error :messages="$errors->get('damage_note')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ringkasan Peminjaman</h3>
                        <dl class="space-y-2 text-xs">
                            <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
                                <dt class="text-neutral-500">Peminjam</dt>
                                <dd class="font-semibold text-neutral-900">{{ $loan->user?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
                                <dt class="text-neutral-500">Barang</dt>
                                <dd class="font-semibold text-neutral-900">{{ $loan->item?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500">Waktu Pinjam</dt>
                                <dd class="font-mono text-neutral-900">{{ optional($loan->borrowed_at)->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
