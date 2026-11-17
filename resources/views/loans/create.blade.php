<x-app-layout>
    <x-slot name="heading">Catat Peminjaman Baru</x-slot>
    <x-slot name="title">Catat Peminjaman Manual</x-slot>

    @php
        $groupedItems = $items->groupBy(fn ($it) => filled($it->category) ? strtoupper(trim($it->category)) : 'LAINNYA');
        $oldItemIds = is_array(old('item_ids')) ? array_map('intval', old('item_ids')) : [];
        $oldQuantities = is_array(old('quantities')) ? old('quantities') : [];

        $itemsPayload = $items->map(fn ($it) => [
            'id' => (int) $it->id,
            'name' => $it->name,
            'item_code' => $it->item_code,
            'epc_tag' => $it->epc_tag,
            'type' => $it->type->value,
            'stock' => (int) $it->stock,
            'category' => filled($it->category) ? strtoupper(trim($it->category)) : 'LAINNYA',
            'location' => $it->location ?: 'Ruang Alat',
        ])->values()->all();

        $studentsPayload = $students->map(fn ($s) => [
            'id' => (int) $s->id,
            'name' => $s->name,
            'nis_nip' => $s->nis_nip,
            'phone' => $s->phone,
        ])->values()->all();
    @endphp

    <script>
        window.silametManualLoanApp = function (config) {
            return {
                students: config.students || [],
                items: config.items || [],
                selectedUserId: config.initialUserId || '',
                selectedItemIds: config.initialItemIds || [],
                quantities: config.initialQuantities || {},
                usageDuration: config.initialDuration || '',
                search: '',
                selectedCategory: 'all',
                barcodeScanInput: '',
                scanFeedback: '',
                openGroups: {},

                init() {
                    // Inisialisasi kuantitas default jika belum ada
                    this.selectedItemIds.forEach(id => {
                        const item = this.items.find(i => i.id === id);
                        if (item && item.type === 'bahan_habis_pakai' && !this.quantities[id]) {
                            this.quantities[id] = 1;
                        }
                    });
                },

                get selectedStudent() {
                    if (!this.selectedUserId) return null;
                    return this.students.find(s => String(s.id) === String(this.selectedUserId)) || null;
                },

                get selectedItems() {
                    return this.items.filter(item => this.selectedItemIds.includes(item.id));
                },

                get hasAssetItem() {
                    return this.selectedItems.some(item => item.type === 'aset');
                },

                get onlyBhpItems() {
                    return this.selectedItems.length > 0 && !this.hasAssetItem;
                },

                isItemSelected(id) {
                    return this.selectedItemIds.includes(Number(id));
                },

                toggleItem(item) {
                    const id = Number(item.id);
                    const idx = this.selectedItemIds.indexOf(id);
                    if (idx > -1) {
                        this.selectedItemIds.splice(idx, 1);
                    } else {
                        this.selectedItemIds.push(id);
                        if (item.type === 'bahan_habis_pakai' && !this.quantities[id]) {
                            this.quantities[id] = 1;
                        }
                    }
                },

                removeItem(id) {
                    const itemId = Number(id);
                    const idx = this.selectedItemIds.indexOf(itemId);
                    if (idx > -1) {
                        this.selectedItemIds.splice(idx, 1);
                    }
                },

                clearAllItems() {
                    this.selectedItemIds = [];
                },

                changeQty(id, delta, maxStock) {
                    const itemId = Number(id);
                    let current = Number(this.quantities[itemId] || 1) + delta;
                    if (current < 1) current = 1;
                    if (maxStock && current > maxStock) current = maxStock;
                    this.quantities[itemId] = current;
                },

                handleQuickScan() {
                    const query = (this.barcodeScanInput || '').trim().toUpperCase();
                    if (!query) return;

                    const found = this.items.find(i =>
                        (i.item_code && i.item_code.toUpperCase() === query) ||
                        (i.epc_tag && i.epc_tag.toUpperCase() === query)
                    );

                    if (found) {
                        if (!this.selectedItemIds.includes(found.id)) {
                            this.toggleItem(found);
                            this.scanFeedback = 'Barang "' + found.name + '" berhasil ditambahkan.';
                        } else {
                            this.scanFeedback = 'Barang "' + found.name + '" sudah ada di daftar.';
                        }
                        this.barcodeScanInput = '';
                        setTimeout(() => { this.scanFeedback = ''; }, 3000);
                    } else {
                        this.scanFeedback = 'Barang dengan kode / tag "' + query + '" tidak ditemukan atau tidak tersedia.';
                        setTimeout(() => { this.scanFeedback = ''; }, 3500);
                    }
                },

                toggleGroup(name) {
                    this.openGroups[name] = !this.openGroups[name];
                },

                isGroupOpen(name) {
                    if (this.search.trim().length > 0) return true;
                    if (this.selectedCategory !== 'all' && this.selectedCategory === name) return true;
                    return !!this.openGroups[name];
                }
            };
        };
    </script>

    <div class="space-y-6" x-data="silametManualLoanApp({
        students: @js($studentsPayload),
        items: @js($itemsPayload),
        initialUserId: @js(old('user_id', '')),
        initialItemIds: @js($oldItemIds),
        initialQuantities: @js($oldQuantities),
        initialDuration: @js(old('usage_duration', ''))
    })">

        <!-- Action Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div class="flex items-center gap-3">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Catat Peminjaman Alat</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Entri peminjaman alat praktikum atau pengambilan bahan sekali pakai oleh administrator.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit"
                        form="create-loan-form"
                        :disabled="!selectedUserId || selectedItems.length === 0 || (hasAssetItem && !usageDuration)"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-200 disabled:text-neutral-400 disabled:cursor-not-allowed text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Peminjaman</span>
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Terjadi kesalahan pengisian formulir:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="create-loan-form"
              method="POST"
              action="{{ route('loans.store') }}"
              @keydown.enter="if ($event.target.tagName !== 'TEXTAREA' && $event.target.type !== 'submit') { $event.preventDefault(); }">
            @csrf

            <!-- Hidden Inputs to sync Selected Items & Quantities to Backend -->
            <template x-for="item in selectedItems" :key="'post-item-' + item.id">
                <div>
                    <input type="hidden" name="item_ids[]" :value="item.id">
                    <template x-if="item.type === 'bahan_habis_pakai'">
                        <input type="hidden" :name="'quantities[' + item.id + ']'" :value="quantities[item.id] || 1">
                    </template>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- KOLOM KIRI (8 COLS): TAHAP 1 (SISWA) & TAHAP 2 (PILIH / SCAN ALAT) -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- TAHAP 1: IDENTITAS SISWA PEMINJAM -->
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-neutral-100 pb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-xl font-mono text-xs font-bold bg-neutral-900 text-white flex items-center justify-center shrink-0">
                                    01
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-neutral-900 tracking-tight font-heading">Data Siswa Peminjam</h2>
                                    <p class="text-xs text-neutral-500 mt-0.5">Pilih nama siswa yang meminjam alat atau mengambil bahan praktikum.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="user_id" value="Nama Siswa / Nomor Induk Siswa (NIS) *" />
                            <select id="user_id"
                                    name="user_id"
                                    x-model="selectedUserId"
                                    class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs sm:text-sm focus:border-neutral-900 focus:ring-neutral-900 font-sans"
                                    required>
                                <option value="">— Pilih siswa peminjam —</option>
                                @foreach($students as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                        {{ $user->name }} &middot; NIS: {{ $user->nis_nip }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <!-- Kartu Profil Siswa Terpilih (Sama seperti Kiosk Mandiri) -->
                        <template x-if="selectedStudent">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-neutral-200/80 text-neutral-700 font-bold font-mono text-sm flex items-center justify-center shrink-0">
                                        <span x-text="selectedStudent.name ? selectedStudent.name.charAt(0).toUpperCase() : 'S'"></span>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-neutral-900" x-text="selectedStudent.name"></div>
                                        <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                            NIS: <span x-text="selectedStudent.nis_nip"></span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <template x-if="selectedStudent.phone">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span x-text="selectedStudent.phone"></span>
                                        </span>
                                    </template>
                                    <template x-if="!selectedStudent.phone">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-mono font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            No. WhatsApp Belum Ada
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- TAHAP 2: PILIH / SCAN ALAT PRAKTIKUM (ALUR KIOSK MANDIRI) -->
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-xl font-mono text-xs font-bold bg-neutral-900 text-white flex items-center justify-center shrink-0">
                                    02
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-neutral-900 tracking-tight font-heading">Pilih Alat Praktikum *</h2>
                                    <p class="text-xs text-neutral-500 mt-0.5">Scan barcode alat langsung dengan scanner USB atau pilih dari daftar kelompok di bawah.</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-md font-mono text-xs font-bold bg-neutral-100 text-neutral-700 border border-neutral-200 self-start sm:self-auto">
                                <span x-text="selectedItems.length">0</span> Alat Terpilih
                            </span>
                        </div>

                        <!-- Scanner Barcode / EPC Siaga Card (Sama seperti Kiosk Mandiri) -->
                        <div class="p-4 rounded-xl bg-neutral-900 text-white border border-neutral-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                                    <span class="font-mono text-[11px] font-bold text-emerald-400 tracking-wider">SCANNER SIAGA</span>
                                </div>
                                <span class="text-[11px] text-neutral-400 font-mono">USB Barcode / RFID Reader</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input type="text"
                                           x-model="barcodeScanInput"
                                           @keydown.enter.prevent="handleQuickScan()"
                                           placeholder="Arahkan scanner ke barcode / ketik kode barang lalu tekan Enter..."
                                           class="w-full text-xs font-mono font-semibold rounded-lg bg-neutral-800 border-neutral-700 text-white placeholder:text-neutral-500 focus:border-primary focus:ring-primary pl-9 pr-3 py-2" />
                                    <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <button type="button"
                                        @click="handleQuickScan()"
                                        class="px-3.5 py-2 rounded-lg bg-neutral-800 hover:bg-neutral-700 border border-neutral-700 text-xs font-semibold text-neutral-200 transition-colors shrink-0">
                                    Tambah
                                </button>
                            </div>
                            <div x-show="scanFeedback"
                                 x-transition
                                 class="text-xs font-mono text-amber-300 pt-1"
                                 x-text="scanFeedback"></div>
                        </div>

                        <!-- PANEL DAFTAR ALAT YANG DIPILIH (KERANJANG PEMINJAMAN ALA KIOSK) -->
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-heading">Daftar Alat yang Dipilih</h3>
                                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-neutral-200 text-neutral-800" x-text="selectedItems.length">0</span>
                                </div>
                                <button type="button"
                                        x-show="selectedItems.length > 0"
                                        @click="clearAllItems()"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700 transition-colors">
                                    Hapus Semua
                                </button>
                            </div>

                            <!-- List Item Terpilih -->
                            <div class="space-y-2.5">
                                <template x-for="(item, index) in selectedItems" :key="'selected-' + item.id">
                                    <div class="p-3 bg-white rounded-xl border border-neutral-200 shadow-2xs space-y-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-start gap-2.5 min-w-0">
                                                <span class="w-6 h-6 rounded-lg bg-neutral-900 text-white font-mono text-[11px] font-bold flex items-center justify-center shrink-0"
                                                      x-text="index + 1"></span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-xs text-neutral-900 truncate" x-text="item.name"></span>
                                                        <template x-if="item.type === 'bahan_habis_pakai'">
                                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                                BHP / Sekali Pakai
                                                            </span>
                                                        </template>
                                                        <template x-if="item.type === 'aset'">
                                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                                Unit Praktikum
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1 text-[11px] text-neutral-500 font-mono flex-wrap">
                                                        <span x-text="item.item_code"></span>
                                                        <!-- ANGKA SERIAL TAG FISIK (BUKAN TEKS UHF RFID) -->
                                                        <template x-if="item.epc_tag">
                                                            <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-700 border border-neutral-200 font-semibold"
                                                                  x-text="'TAG: ' + item.epc_tag"
                                                                  title="Serial Tag RFID"></span>
                                                        </template>
                                                        <span class="text-neutral-400">&middot;</span>
                                                        <span x-text="'Rak: ' + item.location"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    @click="removeItem(item.id)"
                                                    class="p-1 rounded-lg text-neutral-400 hover:text-rose-600 hover:bg-neutral-100 transition-colors"
                                                    title="Hapus alat dari daftar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Kontrol Kuantitas jika Bahan Sekali Pakai (BHP) -->
                                        <template x-if="item.type === 'bahan_habis_pakai'">
                                            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between gap-3 text-xs pl-8">
                                                <div>
                                                    <span class="font-semibold text-neutral-800">Jumlah diambil:</span>
                                                    <span class="text-[11px] text-neutral-400 block" x-text="'Maksimal ' + item.stock + ' pcs sesuai sisa stok'"></span>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <button type="button"
                                                            @click="changeQty(item.id, -1, item.stock)"
                                                            class="w-7 h-7 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold flex items-center justify-center transition-colors">
                                                        -
                                                    </button>
                                                    <input type="number"
                                                           min="1"
                                                           :max="item.stock"
                                                           x-model.number="quantities[item.id]"
                                                           class="w-16 px-2 py-1 text-center font-mono font-bold text-xs rounded-lg border border-neutral-300 focus:border-neutral-900 focus:ring-neutral-900" />
                                                    <button type="button"
                                                            @click="changeQty(item.id, 1, item.stock)"
                                                            class="w-7 h-7 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold flex items-center justify-center transition-colors">
                                                        +
                                                    </button>
                                                    <span class="font-mono text-xs font-semibold text-neutral-600">pcs</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="selectedItems.length === 0">
                                    <div class="py-6 text-center text-neutral-400">
                                        <p class="text-xs font-semibold text-neutral-600">Belum Ada Alat yang Dipilih</p>
                                        <p class="text-[11px] text-neutral-400 mt-0.5">Scan barcode alat di atas atau centang alat dari katalog di bawah.</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- KATALOG ALAT PRAKTIKUM DENGAN KATEGORI & SEARCH -->
                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-heading">Pilih Alat Praktikum *</h3>
                                <span class="text-[11px] text-neutral-500 font-mono">{{ $items->count() }} unit di lab</span>
                            </div>

                            <!-- Search Input -->
                            <div class="relative">
                                <input type="text"
                                       x-model="search"
                                       placeholder="Cari nama alat, kelompok (misal: ROUTER, KONEKTOR), kode aset, atau tag..."
                                       class="w-full text-xs rounded-xl border border-neutral-200 pl-9 pr-8 py-2.5 focus:border-neutral-900 focus:ring-neutral-900 placeholder:text-neutral-400" />
                                <div class="absolute left-3 top-3 text-neutral-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <button type="button"
                                        x-show="search.length > 0"
                                        @click="search = ''"
                                        class="absolute right-2.5 top-2.5 text-neutral-400 hover:text-neutral-600 p-0.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Category Filter Pills -->
                            @if($groupedItems->count() > 1)
                                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                                    <button type="button"
                                            @click="selectedCategory = 'all'"
                                            :class="selectedCategory === 'all' ? 'bg-neutral-900 text-white font-bold' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 font-medium'"
                                            class="px-3 py-1 rounded-lg shrink-0 transition-colors">
                                        Semua ({{ $items->count() }})
                                    </button>
                                    @foreach($groupedItems as $catName => $catItems)
                                        <button type="button"
                                                @click="selectedCategory = '{{ addslashes($catName) }}'"
                                                :class="selectedCategory === '{{ addslashes($catName) }}' ? 'bg-neutral-900 text-white font-bold' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 font-medium'"
                                                class="px-3 py-1 rounded-lg shrink-0 transition-colors uppercase font-mono">
                                            {{ $catName }} ({{ $catItems->count() }})
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Accordion List Kelompok Barang -->
                            <div class="border border-neutral-200 rounded-xl p-3 max-h-96 overflow-y-auto space-y-2.5 bg-neutral-50/50">
                                @forelse($groupedItems as $catName => $catItems)
                                    <div x-show="(selectedCategory === 'all' || selectedCategory === '{{ addslashes($catName) }}') && (search === '' || '{{ strtolower(addslashes($catName . ' ' . $catItems->pluck('name')->implode(' ') . ' ' . $catItems->pluck('item_code')->implode(' ') . ' ' . $catItems->pluck('epc_tag')->implode(' '))) }}'.includes(search.toLowerCase()))"
                                         class="rounded-xl border border-neutral-200/90 bg-white shadow-2xs overflow-hidden">
                                        
                                        <!-- Header Kelompok -->
                                        <button type="button"
                                                @click="toggleGroup('{{ addslashes($catName) }}')"
                                                class="w-full px-4 py-2.5 bg-neutral-50 hover:bg-neutral-100/80 transition-colors flex items-center justify-between gap-3 text-left">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-6 h-6 rounded-lg bg-neutral-200 text-neutral-700 flex items-center justify-center shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="font-bold text-xs text-neutral-900 uppercase tracking-wide truncate block font-heading">
                                                        {{ $catName }}
                                                    </span>
                                                    <span class="text-[10px] text-neutral-500 font-mono">
                                                        {{ $catItems->count() }} unit tersedia
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 text-neutral-400">
                                                <span class="text-[10px] font-medium hidden sm:inline">Buka/Tutup</span>
                                                <svg class="w-4 h-4 transition-transform duration-200"
                                                     :class="isGroupOpen('{{ addslashes($catName) }}') ? 'rotate-180' : ''"
                                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </button>

                                        <!-- Items List -->
                                        <div x-show="isGroupOpen('{{ addslashes($catName) }}')"
                                             x-transition
                                             class="p-2.5 space-y-2 border-t border-neutral-100 bg-white">
                                            @foreach($catItems as $item)
                                                <div class="p-2.5 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-3"
                                                     :class="isItemSelected({{ $item->id }}) ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50/50'"
                                                     @click="toggleItem({ id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', type: '{{ $item->type->value }}', stock: {{ (int) $item->stock }} })">
                                                    
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <input type="checkbox"
                                                               :checked="isItemSelected({{ $item->id }})"
                                                               @click.stop="toggleItem({ id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', type: '{{ $item->type->value }}', stock: {{ (int) $item->stock }} })"
                                                               class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900 h-4 w-4">
                                                        <div class="min-w-0">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-bold text-xs text-neutral-900 truncate">{{ $item->name }}</span>
                                                                @if($item->type->value === 'bahan_habis_pakai')
                                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                                        BHP (Stok: {{ $item->stock }})
                                                                    </span>
                                                                @else
                                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                                        Unit Praktikum
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2 mt-0.5">
                                                                <span class="text-[11px] text-neutral-500 font-mono">{{ $item->item_code }}</span>
                                                                <!-- MENAMPILKAN ANGKA SERIAL TAG FISIK (BUKAN TEKS UHF RFID) -->
                                                                @if($item->epc_tag)
                                                                    <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-700 border border-neutral-200" title="Serial Tag UHF RFID">
                                                                        TAG: {{ $item->epc_tag }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <span class="font-mono text-[10px] text-neutral-500 shrink-0 px-2 py-0.5 rounded bg-neutral-50 border border-neutral-200">
                                                        {{ $item->location ?: 'Ruang Alat' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-8 text-center text-xs text-neutral-500">
                                        Tidak ada alat praktikum yang berstatus tersedia saat ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                    </div>

                </div>

                <!-- KOLOM KANAN (4 COLS): TAHAP 3 (DURASI & KONFIRMASI) + PANDUAN -->
                <div class="lg:col-span-4 space-y-5">

                    <!-- TAHAP 3: DURASI PEMAKAIAN (ALUR KIOSK MANDIRI) -->
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4">
                        <div class="border-b border-neutral-100 pb-3 flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-xl font-mono text-xs font-bold bg-neutral-900 text-white flex items-center justify-center shrink-0">
                                03
                            </span>
                            <div>
                                <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider font-heading">Durasi Pemakaian</h3>
                                <p class="text-[11px] text-neutral-500 mt-0.5">Batas waktu pengembalian alat praktikum.</p>
                            </div>
                        </div>

                        <!-- JIKA HANYA MEMINJAM BAHAN SEKALI PAKAI (BHP) -->
                        <div x-show="onlyBhpItems" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1">
                            <div class="flex items-center gap-2 font-bold text-xs">
                                <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Bahan Sekali Pakai (BHP)</span>
                            </div>
                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                Bahan praktikum tidak perlu dikembalikan ke lab. Durasi pemakaian tidak diperlukan.
                            </p>
                        </div>

                        <!-- JIKA ADA ALAT PRAKTIKUM (ASET BERULANG) -->
                        <div x-show="!onlyBhpItems" class="space-y-3">
                            <x-input-label for="usage_duration" value="Pilih Durasi Pemakaian *" />
                            
                            <!-- Kartu Pilihan Durasi Cepat ala Kiosk -->
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(\App\Enums\LoanDuration::cases() as $duration)
                                    <button type="button"
                                            @click="usageDuration = '{{ $duration->value }}'"
                                            class="p-2.5 rounded-xl border text-left transition-all"
                                            :class="usageDuration === '{{ $duration->value }}' ? 'border-neutral-900 bg-neutral-50 ring-1 ring-neutral-900 font-bold text-neutral-900' : 'border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700'">
                                        <div class="text-xs font-bold leading-tight">{{ $duration->label() }}</div>
                                        <div class="text-[10px] text-neutral-400 font-mono mt-0.5">
                                            @if($duration->value === 'pulang_sekolah')
                                                Hari ini 16:00
                                            @elseif($duration->value === '1_hari')
                                                1x24 Jam
                                            @elseif($duration->value === '2_hari')
                                                2x24 Jam
                                            @elseif($duration->value === '1_minggu')
                                                7 Hari
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <!-- Hidden Select untuk fallback native POST -->
                            <select id="usage_duration"
                                    name="usage_duration"
                                    x-model="usageDuration"
                                    class="hidden"
                                    :required="hasAssetItem">
                                <option value="">— Pilih durasi pemakaian —</option>
                                @foreach(\App\Enums\LoanDuration::cases() as $duration)
                                    <option value="{{ $duration->value }}">{{ $duration->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('usage_duration')" class="mt-1" />
                        </div>

                        <!-- Ringkasan Singkat & Tombol Simpan -->
                        <div class="pt-3 border-t border-neutral-100 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-neutral-500">Peminjam:</span>
                                <span class="font-bold text-neutral-900 truncate max-w-[140px]" x-text="selectedStudent ? selectedStudent.name : '—'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-neutral-500">Total Alat:</span>
                                <span class="font-mono font-bold text-neutral-900" x-text="selectedItems.length + ' item'"></span>
                            </div>

                            <button type="submit"
                                    :disabled="!selectedUserId || selectedItems.length === 0 || (hasAssetItem && !usageDuration)"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-200 disabled:text-neutral-400 disabled:cursor-not-allowed text-xs font-bold text-white shadow-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Simpan Peminjaman</span>
                            </button>
                        </div>
                    </div>

                    <!-- Guidance Sidebar -->
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Integrasi Gate UHF RFID</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Begitu formulir ini disimpan, status alat dalam database berubah menjadi <span class="font-semibold text-neutral-900">dipinjam</span>.
                        </p>
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1">
                            <span class="font-semibold block">Akses Gate Diizinkan</span>
                            <p class="text-emerald-700/90 text-[11px]">Siswa yang membawa alat ini melewati gerbang lab tidak akan memicu alarm sirine gate.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center gap-2 text-neutral-900 font-semibold text-xs">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Aturan Keterlambatan</span>
                        </div>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Alat yang belum dikembalikan melewati batas waktu durasi akan otomatis bertanda <span class="font-semibold text-rose-700">Overdue</span> di dasbor dan memicu pesan WhatsApp pengingat.
                        </p>
                    </div>

                </div>

            </div>
        </form>
    </div>
</x-app-layout>
