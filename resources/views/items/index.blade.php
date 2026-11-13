<x-app-layout>
    <x-slot name="heading"></x-slot>
    <x-slot name="title">Master Aset — SILAMET</x-slot>

    @php
        $canManageMaintenance = in_array(auth()->user()?->role?->value, ['teknisi', 'kabeng'], true);
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
             groupModalOpen: @js($errors->has('name') && old('_form') === 'create_group'),
             editGroupModalOpen: false,
             editGroupSlug: '',
             editGroupName: '',
             editGroupType: 'aset',
             groupTypeFilter: 'all'
         }"
         @keydown.escape.window="groupModalOpen = false; editGroupModalOpen = false">

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs space-y-1">
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

        <!-- Top Action Bar: High-Visibility Segmented Switcher & Actions (No Title Fluff) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-neutral-200">
            <!-- Left Side: Prominent High-Visibility Switcher & Back Button -->
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($activeGroup)
                    <a href="{{ route('items.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold shadow-sm transition-all"
                       title="Kembali ke Semua Kelompok">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Semua Kelompok</span>
                    </a>
                @else
                    <a href="{{ route('items.index') }}"
                       class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full text-xs font-bold transition-all duration-150 {{ $viewMode === 'groups' ? 'bg-neutral-900 text-white shadow-md ring-2 ring-neutral-900' : 'bg-white text-neutral-800 hover:bg-neutral-100 border border-neutral-300 shadow-sm' }}">
                        <svg class="w-4 h-4 {{ $viewMode === 'groups' ? 'text-white' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span>Daftar Kelompok Barang</span>
                        <span class="px-2.5 py-0.5 rounded-full font-mono text-[11px] font-extrabold {{ $viewMode === 'groups' ? 'bg-neutral-800 text-white border border-neutral-700' : 'bg-neutral-100 text-neutral-900 border border-neutral-200' }}">
                            {{ count($groups) }}
                        </span>
                    </a>

                    <a href="{{ route('items.index', ['view' => 'all']) }}"
                       class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-full text-xs font-bold transition-all duration-150 {{ $viewMode === 'all' && $search === '' ? 'bg-neutral-900 text-white shadow-md ring-2 ring-neutral-900' : 'bg-white text-neutral-800 hover:bg-neutral-100 border border-neutral-300 shadow-sm' }}">
                        <svg class="w-4 h-4 {{ ($viewMode === 'all' && $search === '') ? 'text-white' : 'text-neutral-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span>Semua Unit</span>
                        <span class="px-2.5 py-0.5 rounded-full font-mono text-[11px] font-extrabold {{ ($viewMode === 'all' && $search === '') ? 'bg-neutral-800 text-white border border-neutral-700' : 'bg-neutral-100 text-neutral-900 border border-neutral-200' }}">
                            {{ $totalItemCount }}
                        </span>
                    </a>
                @endif
            </div>

            <!-- Right Side: Search & Primary Action Button -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Search Pill Form -->
                <form method="GET" action="{{ route('items.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                    @if($activeGroup)
                        <input type="hidden" name="group" value="{{ $activeGroup['slug'] }}">
                    @endif
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="search"
                               name="q"
                               value="{{ $search }}"
                               placeholder="Cari kelompok, nama, kode, EPC..."
                               class="w-full pl-9 pr-4 py-2 rounded-full border border-neutral-300 bg-white text-xs font-medium text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-sm transition-all" />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-full bg-white hover:bg-neutral-50 text-neutral-800 text-xs font-bold border border-neutral-300 shadow-sm transition-colors shrink-0">
                        Cari
                    </button>
                    @if($search !== '')
                        <a href="{{ $activeGroup ? route('items.index', ['group' => $activeGroup['slug']]) : route('items.index', $viewMode === 'all' ? ['view' => 'all'] : []) }}"
                           class="px-2 py-2 text-xs font-semibold text-neutral-500 hover:text-neutral-900 shrink-0">
                            Reset
                        </a>
                    @endif
                </form>

                @if($viewMode === 'groups')
                    <button type="button"
                            @click="groupModalOpen = true"
                            class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-600 text-white text-xs font-bold transition-colors shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Kelompok Barang</span>
                    </button>
                @else
                    <a href="{{ route('items.create', $activeGroup ? ['category' => $activeGroup['label'], 'return_group' => $activeGroup['slug']] : []) }}"
                       class="px-5 py-2.5 rounded-full bg-primary hover:bg-primary-600 text-white text-xs font-bold transition-colors shadow-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Barang</span>
                    </a>
                @endif
            </div>
        </div>

        @if($viewMode === 'groups')
            @php
                $asetGroups = collect($groups)->where('type', 'aset')->values();
                $bhpGroups = collect($groups)->where('type', 'bahan_habis_pakai')->values();
            @endphp

            <!-- Sub-filter Kategori Kelompok: Semua / Barang Praktikum / Bahan Sekali Pakai -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button"
                        @click="groupTypeFilter = 'all'"
                        :class="groupTypeFilter === 'all' ? 'bg-neutral-900 text-white shadow-sm ring-1 ring-neutral-900' : 'bg-white text-neutral-600 hover:text-neutral-900 border border-neutral-200 hover:bg-neutral-50'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all">
                    <span>Semua Kelompok</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-extrabold"
                          :class="groupTypeFilter === 'all' ? 'bg-neutral-800 text-white' : 'bg-neutral-100 text-neutral-700'">
                        {{ count($groups) }}
                    </span>
                </button>

                <button type="button"
                        @click="groupTypeFilter = 'aset'"
                        :class="groupTypeFilter === 'aset' ? 'bg-primary text-white shadow-sm ring-1 ring-primary' : 'bg-white text-neutral-600 hover:text-neutral-900 border border-neutral-200 hover:bg-neutral-50'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span>Barang Praktikum (Aset)</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-extrabold"
                          :class="groupTypeFilter === 'aset' ? 'bg-primary-700 text-white' : 'bg-neutral-100 text-neutral-700'">
                        {{ $asetGroups->count() }}
                    </span>
                </button>

                <button type="button"
                        @click="groupTypeFilter = 'bahan_habis_pakai'"
                        :class="groupTypeFilter === 'bahan_habis_pakai' ? 'bg-primary text-white shadow-sm ring-1 ring-primary' : 'bg-white text-neutral-600 hover:text-neutral-900 border border-neutral-200 hover:bg-neutral-50'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Bahan Sekali Pakai (BHP)</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-extrabold"
                          :class="groupTypeFilter === 'bahan_habis_pakai' ? 'bg-primary-700 text-white' : 'bg-neutral-100 text-neutral-700'">
                        {{ $bhpGroups->count() }}
                    </span>
                </button>
            </div>

            <div class="space-y-6">
                <!-- SEKSI 1: BARANG PRAKTIKUM (ASET / DIPAKAI BERULANG) -->
                <div x-show="groupTypeFilter === 'all' || groupTypeFilter === 'aset'" x-transition class="space-y-3">
                    <div class="flex items-center justify-between gap-3 px-1">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-primary-50 text-primary border border-indigo-200 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">Barang Praktikum (Aset / Dipakai Berulang)</h2>
                                <p class="text-[11px] text-neutral-500">Perangkat jaringan, komputer, dan peralatan praktikum berseri dengan pengamanan RFID &amp; pemeliharaan berkala.</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-neutral-100 text-neutral-700 border border-neutral-200">
                            {{ $asetGroups->count() }} kelompok
                        </span>
                    </div>

                    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                                    <tr>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kelompok / Nama Barang</th>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tipe &amp; Lokasi</th>
                                        <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jumlah Unit</th>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Ringkasan Status</th>
                                        <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100">
                                    @forelse($asetGroups as $grp)
                                        <tr class="hover:bg-neutral-50/70 transition-colors group">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="w-10 h-10 rounded-xl bg-primary-50 border border-indigo-100 flex items-center justify-center shrink-0 text-primary">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                                               class="text-sm font-bold text-neutral-900 group-hover:text-primary transition-colors font-heading uppercase tracking-wide">
                                                                {{ $grp['label'] }}
                                                            </a>
                                                            @if($grp['is_explicit_category'])
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                                                    Aset
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
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-primary-50 text-primary-700 border-indigo-200">
                                                    Aset Praktikum
                                                </span>
                                                <div class="text-[11px] font-mono text-neutral-500 mt-1">
                                                    {{ !empty($grp['locations']) ? implode(', ', array_slice($grp['locations'], 0, 2)) : '—' }}
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full font-mono text-xs font-bold bg-neutral-100 text-neutral-900 border border-neutral-200">
                                                    {{ $grp['item_count'] }} item
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @if($grp['item_count'] === 0)
                                                    <span class="text-[11px] font-medium text-neutral-400">Belum ada unit</span>
                                                @else
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        @if($grp['available_count'] > 0)
                                                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 font-mono">
                                                                {{ $grp['available_count'] }} Tersedia
                                                            </span>
                                                        @endif
                                                        @if($grp['borrowed_count'] > 0)
                                                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-sky-50 text-sky-700 border-sky-200 font-mono">
                                                                {{ $grp['borrowed_count'] }} Dipinjam
                                                            </span>
                                                        @endif
                                                        @if($grp['maintenance_count'] > 0)
                                                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200 font-mono">
                                                                {{ $grp['maintenance_count'] }} Maint
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
                                                    <button type="button"
                                                            @click="editGroupModalOpen = true; editGroupSlug = '{{ $grp['slug'] }}'; editGroupName = '{{ addslashes(strtoupper($grp['label'])) }}'; editGroupType = '{{ $grp['type'] ?? 'aset' }}'"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-300 bg-white hover:bg-neutral-100 text-neutral-700 text-xs font-semibold shadow-sm transition-colors"
                                                            title="Ubah Nama Kelompok"
                                                            aria-label="Edit Kelompok {{ $grp['label'] }}">
                                                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Edit</span>
                                                    </button>

                                                    <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                                       class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold shadow-sm transition-colors">
                                                        <span>Buka Daftar</span>
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>

                                                    @if(!empty($grp['item_ids']))
                                                        <a href="{{ route('items.barcodes', ['ids' => implode(',', $grp['item_ids'])]) }}"
                                                           target="_blank"
                                                           class="p-1.5 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm"
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
                                            <td colspan="5" class="py-8 px-4 text-center">
                                                <p class="text-xs text-neutral-400">Belum ada kelompok barang praktikum (aset).</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SEKSI 2: BAHAN HABIS PAKAI (SEKALI PAKAI / BHP) -->
                <div x-show="groupTypeFilter === 'all' || groupTypeFilter === 'bahan_habis_pakai'" x-transition class="space-y-3 pt-2">
                    <div class="flex items-center justify-between gap-3 px-1">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">Bahan Sekali Pakai (BHP)</h2>
                                <p class="text-[11px] text-neutral-500">Konektor RJ-45, Fast Connector, kabel UTP, dan bahan praktikum sekali pakai yang dipindai via barcode kemasan.</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            {{ $bhpGroups->count() }} kelompok
                        </span>
                    </div>

                    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200 text-sm">
                                <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                                    <tr>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kelompok / Nama Bahan</th>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tipe &amp; Lokasi</th>
                                        <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Total Stok Kuantitas</th>
                                        <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Ketersediaan</th>
                                        <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-100">
                                    @forelse($bhpGroups as $grp)
                                        <tr class="hover:bg-neutral-50/70 transition-colors group">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0 text-amber-700">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                                               class="text-sm font-bold text-neutral-900 group-hover:text-primary transition-colors font-heading uppercase tracking-wide">
                                                                {{ $grp['label'] }}
                                                            </a>
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                                Sekali Pakai
                                                            </span>
                                                        </div>
                                                        @if(!empty($grp['preview_codes']))
                                                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                                                Barcode: {{ implode(', ', $grp['preview_codes']) }}
                                                            </div>
                                                        @elseif($grp['item_count'] === 0)
                                                            <div class="text-[11px] text-neutral-400 mt-0.5">
                                                                Kelompok kosong — klik Buka Daftar untuk menambah item bahan
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200">
                                                    Bahan Sekali Pakai (BHP)
                                                </span>
                                                <div class="text-[11px] font-mono text-neutral-500 mt-1">
                                                    {{ !empty($grp['locations']) ? implode(', ', array_slice($grp['locations'], 0, 2)) : '—' }}
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full font-mono text-xs font-bold bg-neutral-100 text-neutral-900 border border-neutral-200">
                                                    {{ $grp['total_units'] }} unit ({{ $grp['item_count'] }} item)
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @if($grp['total_units'] > 10)
                                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 font-mono">
                                                        Stok Siap ({{ $grp['total_units'] }})
                                                    </span>
                                                @elseif($grp['total_units'] > 0)
                                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200 font-mono">
                                                        Stok Menipis ({{ $grp['total_units'] }})
                                                    </span>
                                                @else
                                                    <span class="text-[11px] font-medium text-neutral-400">Habis</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
                                                    <button type="button"
                                                            @click="editGroupModalOpen = true; editGroupSlug = '{{ $grp['slug'] }}'; editGroupName = '{{ addslashes(strtoupper($grp['label'])) }}'; editGroupType = '{{ $grp['type'] ?? 'bahan_habis_pakai' }}'"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-300 bg-white hover:bg-neutral-100 text-neutral-700 text-xs font-semibold shadow-sm transition-colors"
                                                            title="Ubah Nama Kelompok"
                                                            aria-label="Edit Kelompok {{ $grp['label'] }}">
                                                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Edit</span>
                                                    </button>

                                                    <a href="{{ route('items.index', ['group' => $grp['slug']]) }}"
                                                       class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold shadow-sm transition-colors">
                                                        <span>Buka Daftar</span>
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 px-4 text-center">
                                                <p class="text-xs text-neutral-400">Belum ada kelompok bahan sekali pakai (BHP).</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Kelompok Aktif (ketika membuka salah satu kelompok misal TPLINK) -->
            @if($activeGroup)
                <div class="bg-white rounded-2xl border border-indigo-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-primary shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-neutral-900 font-heading">Kelompok: {{ $activeGroup['label'] }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-primary-50 text-primary-700 border border-indigo-200">
                                    {{ $activeGroup['item_count'] }} barang ({{ $activeGroup['total_units'] }} unit)
                                </span>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Menampilkan seluruh unit {{ $activeGroup['label'] }} beserta kode aset, tag RFID, barcode, dan status maintenance.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bulk Action Bar (Cetak Barcode Terpilih + Atur Kelompok Barang) -->
            <div id="bulk-actions-items" class="hidden bg-primary-50 border border-indigo-200 rounded-2xl p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3 shadow-sm">
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
                               class="w-44 rounded-full border border-neutral-300 bg-white px-3.5 py-1.5 text-xs focus:ring-1 focus:ring-primary focus:border-primary shadow-sm" />
                        <datalist id="bulk-category-suggestions">
                            @foreach($categorySuggestions as $catSuggestion)
                                <option value="{{ $catSuggestion }}"></option>
                            @endforeach
                        </datalist>
                        <button type="submit" class="px-4 py-1.5 bg-white hover:bg-neutral-50 text-neutral-800 border border-neutral-300 font-semibold text-xs rounded-full transition-colors shadow-sm">
                            Tetapkan Kelompok
                        </button>
                    </form>

                    <button id="btn-print-selected" type="button" class="px-4 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white font-semibold text-xs rounded-full transition-colors flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Barcode Terpilih</span>
                    </button>
                </div>
            </div>

            {{-- Table Container (Isi Penuh Unit Kelompok atau Semua Unit) --}}
            <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider w-10">
                                    <input type="checkbox" id="select-all-items" aria-label="Pilih semua barang" class="rounded border-neutral-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-4 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kode Aset</th>
                                <th class="px-4 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Nama Barang &amp; Tag RFID</th>
                                <th class="px-4 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Stok</th>
                                <th class="px-4 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Lokasi Rak</th>
                                <th class="px-4 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($items as $item)
                                <tr class="hover:bg-neutral-50/70 transition-colors table-row-clean">
                                    <td class="px-4 py-3.5">
                                        <input type="checkbox" aria-label="Pilih {{ $item->name }}" class="item-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $item->id }}">
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-neutral-100 text-neutral-800 font-mono text-xs font-semibold border border-neutral-200">
                                            {{ $item->item_code }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <!-- Product Icon Avatar ala Dribbble -->
                                            <div class="w-8 h-8 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="font-bold text-neutral-900 text-xs font-heading">{{ $item->name }}</span>
                                                    @if(filled($item->category) && !$activeGroup)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                            {{ $item->category }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($item->epc_tag)
                                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">EPC: {{ $item->epc_tag }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $item->type->value === 'aset' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                            {{ $item->type->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-mono font-semibold text-xs">
                                        @if($item->type->value === 'bahan_habis_pakai' && $item->stock <= 0)
                                            <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">0 (Habis)</span>
                                        @elseif($item->type->value === 'bahan_habis_pakai' && $item->stock <= 5)
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">{{ $item->stock }} (Tipis)</span>
                                        @else
                                            <span class="text-neutral-800">{{ $item->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="font-mono text-xs text-neutral-600 bg-neutral-50 px-2.5 py-0.5 rounded-md border border-neutral-200">
                                            {{ $item->location ?? 'Rak Utama' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        @php
                                            $badge = match($item->status->value) {
                                                'tersedia' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'dipinjam' => 'bg-sky-50 text-sky-700 border-sky-200',
                                                'under_maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                            };
                                            $nearestSched = $item->preventiveSchedules->where('status', 'scheduled')->first()
                                                ?? $item->preventiveSchedules->first();
                                        @endphp
                                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $badge }}">
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
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            @if($item->type->value === 'bahan_habis_pakai')
                                                <button type="button"
                                                    class="open-stock-modal p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-amber-700 hover:text-amber-900 transition-colors shadow-sm"
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
                                            <a href="{{ route('items.barcodes') }}?ids={{ $item->id }}" target="_blank" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Cetak Barcode" aria-label="Cetak Barcode {{ $item->item_code }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('items.show', $item->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Detail Aset" aria-label="Detail {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('items.edit', array_filter(['item' => $item->id, 'return_group' => $activeGroup['slug'] ?? null])) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Edit Aset" aria-label="Edit {{ $item->name }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Hapus barang ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-rose-50 text-neutral-600 hover:text-rose-600 hover:border-rose-200 transition-colors shadow-sm" title="Hapus Aset" aria-label="Hapus {{ $item->name }}">
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
                                    <td colspan="8" class="py-16 px-4 text-center">
                                        <!-- Dribbble Empty State (dribbble_4.png) -->
                                        <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                            <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            @if($activeGroup && $search === '')
                                                <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Unit di Kelompok {{ $activeGroup['label'] }}</h3>
                                                <p class="text-xs text-neutral-500 mt-1 text-center">
                                                    Klik tombol Tambah Unit di kanan atas untuk menambahkan unit ke dalam kelompok ini.
                                                </p>
                                                <a href="{{ route('items.create', ['category' => $activeGroup['label'], 'return_group' => $activeGroup['slug']]) }}"
                                                   class="mt-4 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold shadow-sm inline-flex items-center gap-1.5 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    <span>Tambah Unit ke {{ $activeGroup['label'] }}</span>
                                                </a>
                                            @else
                                                <h3 class="font-heading font-bold text-neutral-900 text-sm">Tidak Ada Barang Ditemukan</h3>
                                                <p class="text-xs text-neutral-500 mt-1 text-center">
                                                    Ubah kata kunci pencarian atau tambahkan unit baru ke Master Aset.
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Modal Tambah Kelompok Barang -->
        <template x-if="groupModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="group-modal-title">
                <div class="absolute inset-0 bg-neutral-950/60" @click="groupModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 overflow-hidden shadow-xl">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 id="group-modal-title" class="font-bold text-neutral-900 text-sm font-heading">Tambah Kelompok Barang</h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Buat kelompok kategori baru (misal: MIKROTIK, TPLINK, PC). Unit barang ditambahkan setelah membuka kelompok.</p>
                        </div>
                        <button type="button"
                                @click="groupModalOpen = false"
                                class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition-colors"
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
                                          @input="$event.target.value = $event.target.value.toUpperCase()"
                                          value="{{ old('_form') === 'create_group' ? old('name') : '' }}"
                                          placeholder="CONTOH: MIKROTIK, TPLINK, KONEKTOR"
                                          class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary uppercase font-bold" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="new-group-type" value="Tipe Kelompok *" />
                            <select id="new-group-type"
                                    name="type"
                                    class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary">
                                <option value="aset" {{ old('type') === 'aset' ? 'selected' : '' }}>Barang Praktikum (Aset / Dipakai Berulang)</option>
                                <option value="bahan_habis_pakai" {{ old('type') === 'bahan_habis_pakai' ? 'selected' : '' }}>Bahan Habis Pakai (Sekali Pakai / BHP)</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <button type="button"
                                    @click="groupModalOpen = false"
                                    class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm">
                                Simpan Kelompok Barang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Ubah Nama Kelompok Barang -->
        <template x-if="editGroupModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="edit-group-modal-title">
                <div class="absolute inset-0 bg-neutral-950/60" @click="editGroupModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 overflow-hidden shadow-xl">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 id="edit-group-modal-title" class="font-bold text-neutral-900 text-sm font-heading">Ubah Nama Kelompok Barang</h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Perbarui nama atau tipe kelompok. Seluruh unit di dalam kelompok ini akan ikut terupdate otomatis.</p>
                        </div>
                        <button type="button"
                                @click="editGroupModalOpen = false"
                                class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition-colors"
                                aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('items.groups.update') }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="old_slug" :value="editGroupSlug">

                        <div>
                            <x-input-label for="edit-group-name" value="Nama Kelompok Barang *" />
                            <x-text-input id="edit-group-name"
                                          name="name"
                                          type="text"
                                          maxlength="80"
                                          required
                                          autofocus
                                          x-model="editGroupName"
                                          @input="editGroupName = $event.target.value.toUpperCase()"
                                          placeholder="CONTOH: MIKROTIK, TPLINK, KONEKTOR"
                                          class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary uppercase font-bold" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="edit-group-type" value="Tipe Kelompok *" />
                            <select id="edit-group-type"
                                    name="type"
                                    x-model="editGroupType"
                                    class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary">
                                <option value="aset">Barang Praktikum (Aset / Dipakai Berulang)</option>
                                <option value="bahan_habis_pakai">Bahan Habis Pakai (Sekali Pakai / BHP)</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <button type="button"
                                    @click="editGroupModalOpen = false"
                                    class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm">
                                Simpan Perubahan
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
        <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 overflow-hidden shadow-xl">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="font-bold text-neutral-900 text-sm font-heading">Catat Mutasi Stok (BHP)</h3>
                    <p id="stock-modal-subtitle" class="text-[11px] font-mono text-neutral-500 mt-0.5"></p>
                </div>
                <button type="button" id="stock-modal-close" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition-colors" aria-label="Tutup modal stok">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('stock-movements.store') }}" class="mt-4 space-y-3.5">
                @csrf
                <input type="hidden" name="item_id" id="stock-modal-item-id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="stock-modal-type" value="Jenis Mutasi *" />
                        <select id="stock-modal-type" name="type" class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs focus:border-primary focus:ring-primary" required>
                            <option value="out">Keluar (Pakai)</option>
                            <option value="in">Masuk (Tambah)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="stock-modal-qty" value="Jumlah *" />
                        <x-text-input id="stock-modal-qty" name="quantity" type="number" min="1" value="1" required class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                    </div>
                </div>

                <div>
                    <x-input-label for="stock-modal-recipient" value="Pengambil / Kelas / Vendor" />
                    <x-text-input id="stock-modal-recipient" name="recipient_name" type="text" placeholder="Contoh: XII SIJA 1 / Pembelian Lab" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                </div>

                <div>
                    <x-input-label for="stock-modal-desc" value="Keterangan *" />
                    <x-text-input id="stock-modal-desc" name="description" type="text" required placeholder="Contoh: Praktikum instalasi kabel LAN / Stok baru" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                    <button type="button" id="stock-modal-cancel" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm">Simpan Mutasi</button>
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