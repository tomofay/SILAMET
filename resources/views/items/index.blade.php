<x-app-layout>
    <x-slot name="heading">Katalog Master Aset</x-slot>
    <x-slot name="title">Master Aset</x-slot>

    @php
        $canManageMaintenance = in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true);
        $activeGroupBarcodeIds = ($activeGroup && !empty($activeGroup['item_ids']))
            ? implode(',', $activeGroup['item_ids'])
            : null;
        $showGroupBarcodeButton = $viewMode === 'groups'
            || ($activeGroup !== null && $activeGroupBarcodeIds !== null)
            || ($activeGroup === null && $totalItemCount > 0);
    @endphp

    <div class="space-y-4"
         x-data="{ groupModalOpen: @js($errors->has('name') && old('_form') === 'create_group') }"
         @keydown.escape.window="groupModalOpen = false">

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Aksi Master Aset tidak dapat diproses:</span>
                </div>
                <ul class="list-disc pl-6 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header Overview & Actions -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Inventaris Alat &amp; Bahan Praktikum</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Kelompokkan perangkat (TPLINK, PC, MIKROTIK, dll.) agar pengecekan unit, cetak barcode, dan penjadwalan maintenance lebih terstruktur.</p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                @if($viewMode === 'groups')
                    <button type="button"
                            @click="groupModalOpen = true"
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Kelompok Barang</span>
                    </button>
                @else
                    <a href="{{ route('items.create', $activeGroup ? ['category' => $activeGroup['label'], 'return_group' => $activeGroup['slug']] : []) }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Barang</span>
                    </a>
                @endif

                @if($showGroupBarcodeButton)
                    <a href="{{ $activeGroupBarcodeIds ? route('items.barcodes', ['ids' => $activeGroupBarcodeIds]) : route('items.barcodes') }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3.5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg border border-neutral-800 transition-colors">
                        <svg class="w-4 h-4 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>{{ $activeGroup ? 'Cetak Barcode ' . $activeGroup['label'] : 'Cetak Semua Barcode' }}</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Bar Filter Tampilan & Pencarian -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('items.index') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $viewMode === 'groups' ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span>Daftar Kelompok Barang</span>
                    <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $viewMode === 'groups' ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ count($groups) }}
                    </span>
                </a>

                <a href="{{ route('items.index', ['view' => 'all']) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $viewMode === 'all' && $search === '' ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>Semua Unit</span>
                    <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $viewMode === 'all' && $search === '' ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $totalItemCount }}
                    </span>
                </a>
            </div>

            <form method="GET" action="{{ route('items.index') }}" class="flex items-center gap-2">
                @if($activeGroup)
                    <input type="hidden" name="group" value="{{ $activeGroup['slug'] }}">
                @endif
                <input type="search"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Cari kelompok, nama, kode, EPC..."
                       class="w-full sm:w-64 rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary" />
                <button type="submit" class="px-3 py-2 bg-white hover:bg-neutral-50 text-neutral-800 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    Cari
                </button>
                @if($search !== '')
                    <a href="{{ $activeGroup ? route('items.index', ['group' => $activeGroup['slug']]) : route('items.index', $viewMode === 'all' ? ['view' => 'all'] : []) }}"
                       class="px-2.5 py-2 text-xs font-semibold text-neutral-500 hover:text-neutral-800">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        @if($viewMode === 'groups')
            <!-- Tampilan Utama: List Nama Kelompok Barang (TPLINK, PC, MIKROTIK, dll) -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kelompok / Nama Barang</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe &amp; Lokasi</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah Unit</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Ringkasan Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200/70">
                            @forelse($groups as $grp)
                                <tr class="table-row-clean group">
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                               class="text-sm font-bold text-neutral-900 group-hover:text-primary transition-colors">
                                                {{ $grp['label'] }}
                                            </a>
                                            @if($grp['is_explicit_category'])
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                                    Kelompok
                                                </span>
                                            @endif
                                        </div>
                                        @if(!empty($grp['preview_codes']))
                                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                                Kode: {{ implode(', ', $grp['preview_codes']) }}@if($grp['item_count'] > count($grp['preview_codes'])) +{{ $grp['item_count'] - count($grp['preview_codes']) }} lainnya @endif
                                            </div>
                                        @elseif($grp['item_count'] === 0)
                                            <div class="text-[11px] text-neutral-400 mt-0.5">
                                                Kelompok kosong — klik Buka Daftar untuk menambah unit
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($grp['aset_count'] > 0 || $grp['bhp_count'] > 0)
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if($grp['aset_count'] > 0)
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border bg-primary-50 text-primary-700 border-indigo-200">
                                                        Aset ({{ $grp['aset_count'] }})
                                                    </span>
                                                @endif
                                                @if($grp['bhp_count'] > 0)
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border bg-neutral-100 text-neutral-700 border-neutral-200">
                                                        BHP ({{ $grp['bhp_count'] }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] font-mono text-neutral-500 mt-1">
                                                {{ !empty($grp['locations']) ? implode(', ', array_slice($grp['locations'], 0, 2)) : '—' }}
                                            </div>
                                        @else
                                            <span class="text-[11px] text-neutral-400">Belum ada unit</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md font-mono text-xs font-bold bg-neutral-100 text-neutral-900 border border-neutral-200">
                                            {{ $grp['item_count'] }} item
                                        </span>
                                        @if($grp['total_units'] !== $grp['item_count'])
                                            <div class="text-[10px] font-mono text-neutral-500 mt-0.5">
                                                Total stok: {{ $grp['total_units'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($grp['item_count'] === 0)
                                            <span class="text-[11px] font-medium text-neutral-400">Belum ada unit</span>
                                        @else
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if($grp['available_count'] > 0)
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border bg-emerald-50 text-emerald-700 border-emerald-200 font-mono">
                                                        {{ $grp['available_count'] }} Tersedia
                                                    </span>
                                                @endif
                                                @if($grp['borrowed_count'] > 0)
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border bg-sky-50 text-sky-700 border-sky-200 font-mono">
                                                        {{ $grp['borrowed_count'] }} Dipinjam
                                                    </span>
                                                @endif
                                                @if($grp['maintenance_count'] > 0)
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border bg-amber-50 text-amber-800 border-amber-200 font-mono">
                                                        {{ $grp['maintenance_count'] }} Maint
                                                    </span>
                                                @endif
                                                @if($grp['unscheduled_count'] > 0 && $canManageMaintenance)
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border bg-neutral-50 text-neutral-600 border-neutral-200 font-mono">
                                                        {{ $grp['unscheduled_count'] }} blm jadwal
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
                                            <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-700 text-white text-[11px] font-semibold border border-primary/40 transition-colors">
                                                <span>Buka Daftar</span>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>

                                            @if(!empty($grp['item_ids']))
                                                <a href="{{ route('items.barcodes', ['ids' => implode(',', $grp['item_ids'])]) }}"
                                                   target="_blank"
                                                   class="btn-icon-action"
                                                   title="Cetak Semua Barcode {{ $grp['label'] }}"
                                                   aria-label="Cetak Barcode {{ $grp['label'] }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-neutral-500">
                                        <p class="font-semibold text-neutral-800 text-sm">Belum Ada Kelompok Barang</p>
                                        <p class="text-xs text-neutral-500 mt-1">Buat kelompok barang pertama (misal: TPLINK, PC, MIKROTIK) lalu buka daftarnya untuk menambahkan unit.</p>
                                        <button type="button"
                                                @click="groupModalOpen = true"
                                                class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                                            Tambah Kelompok Barang
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Header Kelompok Aktif (ketika membuka salah satu kelompok misal TPLINK) -->
            @if($activeGroup)
                <div class="bg-white rounded-xl border border-indigo-200 p-4">
                    <div class="flex items-start sm:items-center gap-3 flex-wrap">
                        <a href="{{ route('items.index') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-xs font-semibold text-neutral-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Semua Kelompok</span>
                        </a>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-neutral-900">Kelompok: {{ $activeGroup['label'] }}</h3>
                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-bold bg-primary-50 text-primary-700 border border-indigo-200">
                                    {{ $activeGroup['item_count'] }} barang ({{ $activeGroup['total_units'] }} unit)
                                </span>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Menampilkan seluruh unit {{ $activeGroup['label'] }} beserta kode aset, tag RFID, barcode, dan status maintenance.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bulk Action Bar (Cetak Barcode Terpilih + Atur Kelompok Barang) -->
            <div id="bulk-actions-items" class="hidden bg-primary-50 border border-indigo-200 rounded-xl p-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <span class="text-xs font-semibold text-primary-700">
                    <span id="selected-item-count" class="font-mono font-bold text-sm">0</span> barang dipilih
                </span>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <form id="bulk-category-form" method="POST" action="{{ route('items.bulk-category') }}" class="flex items-center gap-1.5 flex-wrap">
                        @csrf
                        <div id="bulk-category-hidden-ids"></div>
                        <input type="text"
                               name="category"
                               list="bulk-category-suggestions"
                               placeholder="Kelompok misal: TPLINK"
                               class="w-44 rounded-lg border-neutral-300 bg-white text-xs focus:border-primary focus:ring-primary" />
                        <datalist id="bulk-category-suggestions">
                            @foreach($categorySuggestions as $catSuggestion)
                                <option value="{{ $catSuggestion }}"></option>
                            @endforeach
                        </datalist>
                        <button type="submit" class="px-3 py-2 bg-white hover:bg-neutral-50 text-neutral-800 border border-neutral-300 font-semibold text-xs rounded-lg transition-colors">
                            Tetapkan Kelompok
                        </button>
                    </form>

                    <button id="btn-print-selected" type="button" class="px-3.5 py-2 bg-primary hover:bg-primary-700 text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Barcode Terpilih</span>
                    </button>
                </div>
            </div>

            <!-- Table Container (Isi Penuh Unit Kelompok atau Semua Unit) -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider w-10">
                                    <input type="checkbox" id="select-all-items" aria-label="Pilih semua barang" class="rounded border-neutral-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kode Aset</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Nama Barang &amp; Tag RFID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Stok</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Lokasi Rak</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200/70">
                            @forelse($items as $item)
                                <tr class="table-row-clean">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" aria-label="Pilih {{ $item->name }}" class="item-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $item->id }}">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-block px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-800 font-mono text-xs font-semibold border border-neutral-200">
                                            {{ $item->item_code }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-semibold text-neutral-900 text-xs">{{ $item->name }}</span>
                                            @if(filled($item->category) && !$activeGroup)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                    {{ $item->category }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($item->epc_tag)
                                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5">EPC: {{ $item->epc_tag }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $item->type->value === 'aset' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                            {{ $item->type->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-semibold text-xs">
                                        @if($item->type->value === 'bahan_habis_pakai' && $item->stock <= 0)
                                            <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">0 (Habis)</span>
                                        @elseif($item->type->value === 'bahan_habis_pakai' && $item->stock <= 5)
                                            <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">{{ $item->stock }} (Tipis)</span>
                                        @else
                                            <span class="text-neutral-800">{{ $item->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-neutral-600 bg-neutral-50 px-2 py-0.5 rounded border border-neutral-200">
                                            {{ $item->location ?? 'Rak Utama' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @php
                                            $badge = match($item->status->value) {
                                                'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'dipinjam' => 'bg-brand-50 text-brand-700 border-brand-100',
                                                'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                            };
                                            $nearestSched = $item->preventiveSchedules->where('status', 'scheduled')->first()
                                                ?? $item->preventiveSchedules->first();
                                        @endphp
                                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $badge }}">
                                            {{ $item->status->label() }}
                                        </span>
                                        <div class="mt-1 text-[10px] font-mono">
                                            @if($nearestSched && $nearestSched->status === 'scheduled')
                                                <span class="text-neutral-500">Maint: {{ optional($nearestSched->next_due_date)->format('d/m/Y') }}</span>
                                            @elseif($nearestSched)
                                                <span class="text-neutral-400">Maint: Dijeda</span>
                                            @else
                                                <span class="text-amber-700">Belum Dijadwalkan</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @if($item->type->value === 'bahan_habis_pakai')
                                                <button type="button"
                                                    class="open-stock-modal btn-icon-action text-amber-700 hover:text-amber-900 hover:bg-amber-50 hover:border-amber-200"
                                                    data-item-id="{{ $item->id }}"
                                                    data-item-name="{{ $item->name }}"
                                                    data-item-code="{{ $item->item_code }}"
                                                    data-item-stock="{{ $item->stock }}"
                                                    title="Catat Stok Masuk / Keluar"
                                                    aria-label="Mutasi Stok {{ $item->name }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank" class="btn-icon-action" title="Cetak Barcode" aria-label="Cetak Barcode {{ $item->item_code }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('items.show', $item->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Aset" aria-label="Detail {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('items.edit', array_filter(['item' => $item->id, 'return_group' => $activeGroup['slug'] ?? null])) }}" class="btn-icon-action hover:text-neutral-900" title="Edit Aset" aria-label="Edit {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Hapus barang ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon-action hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200" title="Hapus Aset" aria-label="Hapus {{ $item->name }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-neutral-500">
                                        @if($activeGroup && $search === '')
                                            <p class="font-semibold text-neutral-800 text-sm">Belum Ada Unit di Kelompok {{ $activeGroup['label'] }}</p>
                                            <p class="text-xs text-neutral-500 mt-1">Klik tombol Tambah Barang di kanan atas untuk menambahkan unit ke dalam kelompok ini.</p>
                                        @else
                                            <p class="font-semibold text-neutral-800 text-sm">Tidak Ada Barang Ditemukan</p>
                                            <p class="text-xs text-neutral-500 mt-1">Ubah kata kunci pencarian atau tambahkan unit baru ke Master Aset.</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Modal Tambah Kelompok Barang -->
        <template x-if="groupModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="group-modal-title">
                <div class="absolute inset-0 bg-neutral-950/60" @click="groupModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 overflow-hidden">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 id="group-modal-title" class="font-bold text-neutral-900 text-sm">Tambah Kelompok Barang</h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Buat kelompok kategori baru (misal: MIKROTIK, TPLINK, PC). Unit barang ditambahkan setelah membuka kelompok.</p>
                        </div>
                        <button type="button"
                                @click="groupModalOpen = false"
                                class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100"
                                aria-label="Tutup modal kelompok">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('items.groups.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="create_group">

                        <div>
                            <x-input-label for="new-group-name" value="Nama Kelompok Barang *" />
                            <x-text-input id="new-group-name"
                                          name="name"
                                          type="text"
                                          maxlength="80"
                                          required
                                          autofocus
                                          value="{{ old('_form') === 'create_group' ? old('name') : '' }}"
                                          placeholder="Contoh: MIKROTIK, TPLINK, PC"
                                          class="mt-1 w-full text-sm" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                            <button type="button"
                                    @click="groupModalOpen = false"
                                    class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                                Simpan Kelompok Barang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>

    <!-- Modal Catat Mutasi Stok Bahan Habis Pakai -->
    <div id="stock-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-neutral-950/60" id="stock-modal-backdrop"></div>
        <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 overflow-hidden">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="font-bold text-neutral-900 text-sm">Catat Mutasi Stok (BHP)</h3>
                    <p id="stock-modal-subtitle" class="text-[11px] font-mono text-neutral-500 mt-0.5"></p>
                </div>
                <button type="button" id="stock-modal-close" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100" aria-label="Tutup modal stok">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('stock-movements.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="item_id" id="stock-modal-item-id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="stock-modal-type" value="Jenis Mutasi *" />
                        <select id="stock-modal-type" name="type" class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary" required>
                            <option value="out">Keluar (Pakai)</option>
                            <option value="in">Masuk (Tambah)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="stock-modal-qty" value="Jumlah *" />
                        <x-text-input id="stock-modal-qty" name="quantity" type="number" min="1" value="1" required class="mt-1 w-full font-mono text-xs" />
                    </div>
                </div>

                <div>
                    <x-input-label for="stock-modal-recipient" value="Pengambil / Kelas / Vendor" />
                    <x-text-input id="stock-modal-recipient" name="recipient_name" type="text" placeholder="Contoh: XII SIJA 1 / Pembelian Lab" class="mt-1 w-full text-xs" />
                </div>

                <div>
                    <x-input-label for="stock-modal-desc" value="Keterangan *" />
                    <x-text-input id="stock-modal-desc" name="description" type="text" required placeholder="Contoh: Praktikum instalasi kabel LAN / Stok baru" class="mt-1 w-full text-xs" />
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" id="stock-modal-cancel" class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40">Simpan Mutasi</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-items');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const bulkBar = document.getElementById('bulk-actions-items');
            const countLabel = document.getElementById('selected-item-count');
            const btnPrint = document.getElementById('btn-print-selected');
            const bulkHiddenIds = document.getElementById('bulk-category-hidden-ids');

            const stockModal = document.getElementById('stock-modal');
            const stockItemId = document.getElementById('stock-modal-item-id');
            const stockSubtitle = document.getElementById('stock-modal-subtitle');
            const stockClose = document.getElementById('stock-modal-close');
            const stockCancel = document.getElementById('stock-modal-cancel');
            const stockBackdrop = document.getElementById('stock-modal-backdrop');

            function openStockModal(btn) {
                stockItemId.value = btn.dataset.itemId;
                stockSubtitle.textContent = `${btn.dataset.itemName} (${btn.dataset.itemCode}) · Sisa stok: ${btn.dataset.itemStock}`;
                stockModal.classList.remove('hidden');
                stockModal.classList.add('flex');
            }

            function closeStockModal() {
                stockModal.classList.add('hidden');
                stockModal.classList.remove('flex');
            }

            document.querySelectorAll('.open-stock-modal').forEach(btn => {
                btn.addEventListener('click', () => openStockModal(btn));
            });
            if (stockClose) stockClose.addEventListener('click', closeStockModal);
            if (stockCancel) stockCancel.addEventListener('click', closeStockModal);
            if (stockBackdrop) stockBackdrop.addEventListener('click', closeStockModal);

            function updateBulkBar() {
                const checked = Array.from(checkboxes).filter(cb => cb.checked);
                if (countLabel) countLabel.textContent = checked.length;

                if (bulkHiddenIds) {
                    bulkHiddenIds.innerHTML = '';
                    checked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'item_ids[]';
                        input.value = cb.value;
                        bulkHiddenIds.appendChild(input);
                    });
                }

                if (bulkBar) {
                    if (checked.length > 0) {
                        bulkBar.classList.remove('hidden');
                    } else {
                        bulkBar.classList.add('hidden');
                    }
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    updateBulkBar();
                    if (selectAll) {
                        selectAll.checked = Array.from(checkboxes).every(c => c.checked);
                    }
                });
            });

            if (btnPrint) {
                btnPrint.addEventListener('click', function () {
                    const ids = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                    if (ids.length > 0) {
                        window.open("{{ route('items.barcodes') }}?ids=" + ids.join(','), '_blank');
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
