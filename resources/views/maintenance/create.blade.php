<x-app-layout>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Buat Tiket Kerusakan</h2>

        <form method="POST" action="{{ route('maintenance.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="item_id" value="Barang Rusak *" />
                <select id="item_id" name="item_id" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    <option value="">— Pilih barang —</option>
                    @foreach(\App\Models\Item::orderBy('name')->get() as $item)
                        <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>
                            {{ $item->name }} ({{ $item->item_code }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="issue_description" value="Deskripsi Kendala *" />
                <textarea id="issue_description" name="issue_description" rows="4"
                          class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary"
                          required placeholder="Jelaskan kerusakan / kendala secara detail...">{{ old('issue_description') }}</textarea>
                <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
            </div>

            <input type="hidden" name="reported_by" value="{{ auth()->id() }}">
            <input type="hidden" name="status" value="open">

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('maintenance.index') }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan Tiket</button>
            </div>
        </form>
    </div>
</x-app-layout>
