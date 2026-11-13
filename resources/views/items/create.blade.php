<x-app-layout>
    @php
        $locked = (bool) ($isLockedGroup ?? false);
        $groupLabel = $preselectedCategory ?? null;
        $backUrl = filled($returnGroup ?? null)
            ? route('items.index', ['group' => $returnGroup])
            : route('items.index');
    @endphp

    <x-slot name="heading">{{ $locked ? 'Tambah Barang — ' . $groupLabel : 'Tambah Barang Baru' }}</x-slot>
    <x-slot name="title">Tambah Barang</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>{{ $locked ? 'Kembali ke Kelompok ' . $groupLabel : 'Kembali ke Daftar Aset' }}</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">
                {{ $locked ? 'Formulir Tambah Barang — Kelompok ' . $groupLabel : 'Formulir Registrasi Barang' }}
            </h2>

            <form method="POST" action="{{ route('items.store') }}" class="space-y-4" x-data="{ category: @js(old('category', $groupLabel ?? '')) }">
                @csrf
                @if(filled($returnGroup ?? null))
                    <input type="hidden" name="return_group" value="{{ $returnGroup }}">
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="item_code" value="Kode Barang *" />
                        <x-text-input id="item_code" name="item_code" class="mt-1 w-full font-mono text-sm" value="{{ old('item_code') }}" required placeholder="SLM-001" />
                        <x-input-error :messages="$errors->get('item_code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="epc_tag" value="EPC UHF RFID Tag (opsional)" />
                        <x-text-input id="epc_tag" name="epc_tag" class="mt-1 w-full font-mono text-sm" value="{{ old('epc_tag') }}" placeholder="E280689400004001" />
                        <x-input-error :messages="$errors->get('epc_tag')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        @if($locked)
                            <x-input-label value="Kelompok Barang" />
                            <input type="hidden" name="category" value="{{ $groupLabel }}">
                            <div class="mt-1 flex items-center justify-between gap-2 rounded-lg border border-indigo-200 bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700">
                                <span>{{ $groupLabel }}</span>
                                <svg class="w-4 h-4 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-1">Barang otomatis masuk ke kelompok {{ $groupLabel }}.</p>
                        @else
                            <x-input-label for="category" value="Kelompok / Kategori Barang" />
                            <x-text-input id="category" name="category" x-model="category" list="item-category-suggestions" class="mt-1 w-full text-sm" placeholder="Contoh: TPLINK, PC, MIKROTIK" />
                            <datalist id="item-category-suggestions">
                                @foreach(($categorySuggestions ?? []) as $cat)
                                    <option value="{{ $cat }}"></option>
                                @endforeach
                            </datalist>
                        @endif
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Nama Unit Barang *" />
                        <x-text-input id="name" name="name" class="mt-1 w-full text-sm" value="{{ old('name') }}" required placeholder="{{ $locked ? 'Contoh: ' . $groupLabel . ' Unit 01' : 'Contoh: TPLINK Router 18' }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" value="Tipe *" />
                        <select id="type" name="type" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                            <option value="aset" @selected(old('type', 'aset') === 'aset')>Aset (Unit Tunggal)</option>
                            <option value="bahan_habis_pakai" @selected(old('type') === 'bahan_habis_pakai')>Bahan Habis Pakai</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="stock" value="Stok" />
                        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 w-full font-mono text-sm" value="{{ old('stock', 1) }}" />
                        <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="location" value="Lokasi / Rak Penyimpanan *" />
                        <x-text-input id="location" name="location" class="mt-1 w-full font-mono text-sm" value="{{ old('location') }}" required placeholder="LAB1-RAK02" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" value="Status Awal *" />
                        <select id="status" name="status" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                            <option value="tersedia" @selected(old('status', 'tersedia') === 'tersedia')>Tersedia</option>
                            <option value="dipinjam" @selected(old('status') === 'dipinjam')>Dipinjam</option>
                            <option value="under_maintenance" @selected(old('status') === 'under_maintenance')>Under Maintenance</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ $backUrl }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Aset</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
