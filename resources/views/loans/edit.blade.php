<x-app-layout>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Ubah Status Peminjaman #{{ $loan->id }}</h2>

        <form method="POST" action="{{ route('loans.update', $loan->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="status" value="Status *" />
                <select id="status" name="status" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    <option value="aktif" @selected(old('status', $loan->status->value) === 'aktif')>Aktif</option>
                    <option value="menunggu_approval_kembali" @selected(old('status', $loan->status->value) === 'menunggu_approval_kembali')>Menunggu Approval Kembali</option>
                    <option value="selesai" @selected(old('status', $loan->status->value) === 'selesai')>Selesai</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('loans.show', $loan->id) }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-app-layout>
