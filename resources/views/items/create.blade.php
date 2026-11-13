<x-app-layout>
    @php
        $locked = (bool) ($isLockedGroup ?? false);
        $groupLabel = $preselectedCategory ?? null;
        $backUrl = filled($returnGroup ?? null)
            ? route('items.index', ['group' => $returnGroup])
            : route('items.index');
        $initialType = old('type', $preselectedType ?? 'aset');
    @endphp

    <x-slot name="heading">{{ $locked ? 'Tambah Barang — ' . $groupLabel : 'Tambah Barang Baru' }}</x-slot>
    <x-slot name="title">Tambah Barang Baru — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
             category: @js(old('category', $groupLabel ?? '')),
             itemType: @js($initialType)
         }">

        <!-- Top Action Bar (Discard & Add Buttons ala Dribbble Template) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl }}" class="p-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm inline-flex items-center gap-1.5" title="{{ $locked ? 'Kembali ke Kelompok ' . $groupLabel : 'Kembali ke Daftar Aset' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="text-xs font-semibold pr-1">{{ $locked ? 'Kembali ke Kelompok ' . $groupLabel : 'Kembali ke Daftar Aset' }}</span>
                </a>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                        {{ $locked ? 'Formulir Tambah Barang — Kelompok ' . $groupLabel : 'Formulir Registrasi Barang' }}
                    </h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Daftarkan unit alat praktikum atau bahan habis pakai baru ke dalam katalog lab.</p>
                </div>
            </div>

            <!-- Top Right Action Buttons (Discard & Add ala Dribbble) -->
            <div class="flex items-center gap-2.5">
                <a href="{{ $backUrl }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm">
                    Batal
                </a>
                <button type="submit" form="create-item-form" class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Simpan Barang</span>
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs space-y-1">
                <div class="font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Terdapat data yang belum sesuai validasi:</span>
                </div>
                <ul class="list-disc pl-6 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="create-item-form" method="POST" action="{{ route('items.store') }}">
            @csrf
            <!-- Default status barang baru adalah selalu tersedia -->
            <input type="hidden" name="status" value="tersedia">

            @if(filled($returnGroup ?? null))
                <input type="hidden" name="return_group" value="{{ $returnGroup }}">
            @endif

            <!-- 2-Column Layout (Split 65% / 35% ala Dribbble E-commerce Flow) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column (Product Details, Media & RFID, Tipe Barang) -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- Card 1: Detail Barang (Product Information) -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 font-heading">Detail Barang</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Informasi nama perangkat, merek, dan spesifikasi unit praktikum.</p>
                        </div>

                        <div>
                            <x-input-label for="name" value="Nama Barang *" />
                            <x-text-input id="name" name="name" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" value="{{ old('name') }}" required placeholder="{{ $locked ? 'Contoh: ' . $groupLabel . ' Unit 01' : 'Contoh: Router MikroTik RB750Gr3' }}" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" value="Deskripsi / Catatan Tambahan (Opsional)" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-primary focus:ring-primary" placeholder="Tuliskan spesifikasi teknis, kelengkapan adaptor, atau nomor seri pabrik...">{{ old('notes') }}</textarea>
                            <p class="text-[11px] text-neutral-400 mt-1">Catatan ini membantu teknisi dan siswa saat pemeriksaan alat di Kiosk.</p>
                        </div>
                    </div>

                    <!-- Card 2: Identifikasi & Tag RFID (Media / Identification Area ala Dribbble) -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-neutral-100 pb-3 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 font-heading">Identifikasi &amp; Keamanan RFID</h2>
                                <p class="text-xs text-neutral-500 mt-0.5">Pindai tag UHF RFID agar barang terlindungi sensor Gate Security otomatis.</p>
                            </div>
                            <span class="px-2 py-0.5 rounded font-mono text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                HW-VX6330
                            </span>
                        </div>

                        <!-- Dashed Box ala Dribbble Media Upload -->
                        <div class="border-2 border-dashed border-neutral-200 rounded-xl p-5 text-center bg-neutral-50/50">
                            <div class="w-10 h-10 rounded-full bg-primary-50 text-primary mx-auto flex items-center justify-center mb-2.5">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-neutral-800">Pemetaan Stiker EPC UHF RFID Gen2</p>
                            <p class="text-[11px] text-neutral-500 mt-0.5 max-w-md mx-auto">Dekatkan stiker RFID ke pembaca USB reader atau ketikkan kode hexa EPC unik alat.</p>
                            
                            <div class="mt-4 max-w-md mx-auto">
                                <x-text-input id="epc_tag" name="epc_tag" class="w-full font-mono text-center text-xs rounded-xl border-neutral-300 focus:border-primary focus:ring-primary tracking-wider" value="{{ old('epc_tag') }}" placeholder="Contoh EPC: E280689400004001" />
                                <x-input-error :messages="$errors->get('epc_tag')" class="mt-1.5" />
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                <span class="font-medium text-neutral-700">QR Code / Barcode Token</span>
                            </div>
                            <span class="font-mono text-[11px] font-bold text-neutral-600 bg-white px-2 py-0.5 rounded border border-neutral-200">
                                Digenerate Otomatis
                            </span>
                        </div>
                    </div>

                    <!-- Card 3: Tipe Penggunaan Barang (Barang Praktikum vs Bahan Habis Pakai) -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 font-heading">Tipe Barang &amp; Penggunaan</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Pilih apakah barang ini digunakan berulang kali atau bahan sekali pakai.</p>
                        </div>

                        <div>
                            <x-input-label for="type" value="Pilihan Tipe Barang *" />
                            <select id="type" name="type" x-model="itemType" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm font-semibold focus:border-primary focus:ring-primary" required>
                                <option value="aset">Barang Praktikum (Aset / Dipakai Berulang)</option>
                                <option value="bahan_habis_pakai">Bahan Habis Pakai (Sekali Pakai / BHP)</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <!-- Kondisi jika Barang Praktikum (Aset): Kuantitas stok otomatis 1 unit -->
                        <div x-show="itemType === 'aset'" x-transition class="p-3.5 rounded-xl bg-neutral-50 border border-neutral-200 flex items-center gap-2.5 text-xs text-neutral-600">
                            <input type="hidden" name="stock" value="1">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Barang praktikum dicatat sebagai <strong>1 unit individual</strong> berseri unik dan siap dipantau sirkulasinya.</span>
                        </div>

                        <!-- Kondisi jika Bahan Habis Pakai (BHP): Kuantitas stok dapat diisi -->
                        <div x-show="itemType === 'bahan_habis_pakai'" x-transition class="space-y-2">
                            <x-input-label for="stock" value="Jumlah Kuantitas Stok Awal (Pcs / Roll / Pack) *" />
                            <x-text-input id="stock" name="stock" type="number" min="1" class="w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" value="{{ old('stock', 1) }}" />
                            <p class="text-[11px] text-neutral-400">Setiap pemakaian bahan habis pakai akan otomatis tercatat di Buku Stok Bahan.</p>
                            <x-input-error :messages="$errors->get('stock')" class="mt-1.5" />
                        </div>
                    </div>

                </div>

                <!-- Right Column (Sidebar Cards: Organisasi, Lokasi Penempatan) -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- Card 1: Pengelompokan & Organisasi (Organization ala Dribbble) -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 shadow-sm space-y-3.5">
                        <div class="border-b border-neutral-100 pb-2.5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">Identitas &amp; Kelompok</h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Penetapan kode unik dan kategori kelompok master.</p>
                        </div>

                        <div>
                            <x-input-label for="item_code" value="Kode Barang Unik *" />
                            <x-text-input id="item_code" name="item_code" class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary uppercase" value="{{ old('item_code') }}" required placeholder="Contoh: TL840_18" />
                            <x-input-error :messages="$errors->get('item_code')" class="mt-1.5" />
                        </div>

                        <div>
                            @if($locked)
                                <x-input-label value="Kelompok Barang" />
                                <input type="hidden" name="category" value="{{ $groupLabel }}">
                                <div class="mt-1.5 flex items-center justify-between gap-2 rounded-xl border border-indigo-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700">
                                    <span>{{ $groupLabel }}</span>
                                    <svg class="w-4 h-4 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            @else
                                <x-input-label for="category" value="Kelompok / Kategori Barang" />
                                <x-text-input id="category" name="category" x-model="category" list="item-category-suggestions" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" placeholder="Pilih atau ketik kelompok (misal: TPLINK N ROUTER)" />
                                <datalist id="item-category-suggestions">
                                    @foreach(($categorySuggestions ?? []) as $cat)
                                        <option value="{{ $cat }}"></option>
                                    @endforeach
                                </datalist>
                                @if(!empty($categorySuggestions))
                                    <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                        @foreach(array_slice($categorySuggestions, 0, 4) as $cat)
                                            <button type="button" @click="category = @js($cat)" class="px-2 py-0.5 rounded-full border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-[10px] font-semibold text-neutral-700 transition-colors">
                                                {{ $cat }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            <x-input-error :messages="$errors->get('category')" class="mt-1.5" />
                        </div>
                    </div>

                    <!-- Card 2: Lokasi Penempatan (Dropdown Ruang Alat / Lab 1 - Lab 5) -->
                    <div class="bg-white rounded-2xl border border-neutral-200 p-5 shadow-sm space-y-3.5">
                        <div class="border-b border-neutral-100 pb-2.5">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">Lokasi Penempatan</h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Pilih ruangan atau laboratorium penyimpanan.</p>
                        </div>

                        <div>
                            <x-input-label for="location" value="Ruang / Laboratorium *" />
                            <select id="location" name="location" class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs font-semibold focus:border-primary focus:ring-primary" required>
                                <option value="Ruang Alat" @selected(old('location', 'Ruang Alat') === 'Ruang Alat')>Ruang Alat</option>
                                <option value="Lab 1" @selected(old('location') === 'Lab 1')>Lab 1</option>
                                <option value="Lab 2" @selected(old('location') === 'Lab 2')>Lab 2</option>
                                <option value="Lab 3" @selected(old('location') === 'Lab 3')>Lab 3</option>
                                <option value="Lab 4" @selected(old('location') === 'Lab 4')>Lab 4</option>
                                <option value="Lab 5" @selected(old('location') === 'Lab 5')>Lab 5</option>
                            </select>
                            <x-input-error :messages="$errors->get('location')" class="mt-1.5" />
                        </div>
                    </div>

                </div>

            </div>
        </form>

    </div>
</x-app-layout>
