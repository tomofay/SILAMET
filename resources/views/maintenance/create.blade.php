<x-app-layout>
    <x-slot name="heading">Buat Tiket Kerusakan</x-slot>
    <x-slot name="title">Buat Tiket M&R</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('maintenance.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Tiket</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">Formulir Tiket Kerusakan Barang</h2>

            <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="item_id" value="Nama Barang Rusak (Master Aset) *" />
                    <select id="item_id" name="item_id" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="">— Pilih nama alat / barang dari Master Aset —</option>
                        @foreach(($items ?? \App\Models\Item::orderBy('name')->orderBy('item_code')->get()) as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id', $preselectedItemId ?? null) == $item->id)>
                                {{ $item->name }} ({{ $item->item_code }}) · {{ $item->type->label() }} — {{ $item->location }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="issue_description" value="Deskripsi Kendala *" />
                    <textarea id="issue_description" name="issue_description" rows="4"
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary"
                              required placeholder="Jelaskan kerusakan / kendala secara detail...">{{ old('issue_description') }}</textarea>
                    <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
                </div>

                <input type="hidden" name="reported_by" value="{{ auth()->id() }}">
                <input type="hidden" name="status" value="open">

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('maintenance.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Tiket</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
