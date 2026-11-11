<x-app-layout>
    <x-slot name="heading">Ubah Status Peminjaman #{{ $loan->id }}</x-slot>
    <x-slot name="title">Edit Peminjaman #{{ $loan->id }}</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Detail Peminjaman</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">
                Ubah Status Peminjaman <span class="font-mono">#{{ $loan->id }}</span>
            </h2>

            <form method="POST" action="{{ route('loans.update', $loan->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="status" value="Status Peminjaman *" />
                    <select id="status" name="status" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="aktif" @selected(old('status', $loan->status->value) === 'aktif')>Aktif</option>
                        <option value="menunggu_approval_kembali" @selected(old('status', $loan->status->value) === 'menunggu_approval_kembali')>Menunggu Approval Kembali</option>
                        <option value="selesai" @selected(old('status', $loan->status->value) === 'selesai')>Selesai</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('loans.show', $loan->id) }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
