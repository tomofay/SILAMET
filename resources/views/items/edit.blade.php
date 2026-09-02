<x-app-layout>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Edit Barang: {{ $item->name }}</h2>

        <form method="POST" action="{{ route('items.update', $item->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="item_code" value="Kode Barang *" />
                <x-text-input id="item_code" name="item_code" class="mt-1 w-full" value="{{ old('item_code', $item->item_code) }}" required />
                <x-input-error :messages="$errors->get('item_code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="epc_tag" value="EPC UHF RFID Tag (opsional)" />
                <x-text-input id="epc_tag" name="epc_tag" class="mt-1 w-full" value="{{ old('epc_tag', $item->epc_tag) }}" />
                <x-input-error :messages="$errors->get('epc_tag')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="name" value="Nama Barang *" />
                <x-text-input id="name" name="name" class="mt-1 w-full" value="{{ old('name', $item->name) }}" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="type" value="Tipe *" />
                    <select id="type" name="type" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                        <option value="aset" @selected(old('type', $item->type->value) === 'aset')>Aset (unit tunggal)</option>
                        <option value="bahan_habis_pakai" @selected(old('type', $item->type->value) === 'bahan_habis_pakai')>Bahan Habis Pakai</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="stock" value="Stok" />
                    <x-text-input id="stock" name="stock" type="number" min="1" class="mt-1 w-full" value="{{ old('stock', $item->stock) }}" />
                    <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="location" value="Lokasi Penyimpanan *" />
                <x-text-input id="location" name="location" class="mt-1 w-full" value="{{ old('location', $item->location) }}" required />
                <x-input-error :messages="$errors->get('location')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" value="Status *" />
                <select id="status" name="status" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    <option value="tersedia" @selected(old('status', $item->status->value) === 'tersedia')>Tersedia</option>
                    <option value="dipinjam" @selected(old('status', $item->status->value) === 'dipinjam')>Dipinjam</option>
                    <option value="under_maintenance" @selected(old('status', $item->status->value) === 'under_maintenance')>Under Maintenance</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('items.index') }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-app-layout>
