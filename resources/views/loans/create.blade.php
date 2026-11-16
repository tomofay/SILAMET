<x-app-layout>
    <x-slot name="heading">Catat Peminjaman Baru</x-slot>
    <x-slot name="title">Catat Peminjaman Manual</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Catat Peminjaman Manual</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Entri peminjaman alat praktikum untuk siswa secara langsung oleh administrator / laboran.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="create-loan-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Peminjaman</span>
                </button>
            </div>
        </div>

        <form id="create-loan-form" method="POST" action="{{ route('loans.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Data Siswa & Durasi Pemakaian</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Tentukan nama siswa peminjam dan batas waktu peminjaman alat.</p>
                        </div>

                        <div>
                            <x-input-label for="user_id" value="Siswa / Peminjam *" />
                            <select id="user_id" name="user_id" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                <option value="">— Pilih siswa peminjam —</option>
                                @foreach(\App\Models\User::where('role', 'siswa')->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                        {{ $user->name }} ({{ $user->nis_nip }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="usage_duration" value="Durasi Pemakaian *" />
                            <select id="usage_duration" name="usage_duration" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                @foreach(\App\Enums\LoanDuration::cases() as $duration)
                                    <option value="{{ $duration->value }}" @selected(old('usage_duration') === $duration->value)>
                                        {{ $duration->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('usage_duration')" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4"
                         x-data="{
                             search: '',
                             selectedCategory: 'all',
                             openGroups: {},
                             toggleGroup(name) {
                                 this.openGroups[name] = !this.openGroups[name];
                             },
                             isGroupOpen(name) {
                                 if (this.search.trim().length > 0) return true;
                                 if (this.selectedCategory !== 'all' && this.selectedCategory === name) return true;
                                 return !!this.openGroups[name];
                             }
                         }">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-100 pb-3">
                            <div>
                                <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Pilih Alat Praktikum *</h2>
                                <p class="text-xs text-neutral-500 mt-0.5">Filter berdasarkan kelompok (RJ45, Router, dll) dan centang unit yang akan dipinjam.</p>
                            </div>
                        </div>

                        @php
                            $groupedItems = $items->groupBy(fn ($it) => filled($it->category) ? strtoupper(trim($it->category)) : 'LAINNYA');
                            $oldItemIds = is_array(old('item_ids')) ? array_map('intval', old('item_ids')) : [];
                        @endphp

                        <!-- Search & Category Filter Header -->
                        <div class="space-y-3">
                            <div class="relative">
                                <input type="text"
                                       x-model="search"
                                       placeholder="Cari nama alat, pack, kode aset, atau kelompok..."
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

                            <!-- Filter Pills -->
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
                                                class="px-3 py-1 rounded-lg shrink-0 transition-colors uppercase">
                                            {{ $catName }} ({{ $catItems->count() }})
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Grouped List with Dropdowns / Accordions -->
                        <div class="border border-neutral-200 rounded-xl p-3 max-h-96 overflow-y-auto space-y-3 bg-neutral-50/50">
                            @forelse($groupedItems as $catName => $catItems)
                                <div x-show="(selectedCategory === 'all' || selectedCategory === '{{ addslashes($catName) }}') && (search === '' || '{{ strtolower(addslashes($catName . ' ' . $catItems->pluck('name')->implode(' ') . ' ' . $catItems->pluck('item_code')->implode(' '))) }}'.includes(search.toLowerCase()))"
                                     class="rounded-xl border border-neutral-200/90 bg-white shadow-2xs overflow-hidden">
                                    
                                    <!-- Accordion Group Header (Click to dropdown) -->
                                    <button type="button"
                                            @click="toggleGroup('{{ addslashes($catName) }}')"
                                            class="w-full px-4 py-3 bg-neutral-50 hover:bg-neutral-100/80 transition-colors flex items-center justify-between gap-3 text-left">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-7 h-7 rounded-lg bg-neutral-200/70 text-neutral-700 flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-bold text-xs text-neutral-900 uppercase tracking-wide truncate block">
                                                    {{ $catName }}
                                                </span>
                                                <span class="text-[11px] text-neutral-500 font-mono">
                                                    {{ $catItems->count() }} unit / pack tersedia
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[11px] font-semibold text-neutral-500 hover:text-neutral-900 hidden sm:inline">
                                                Klik untuk buka/tutup
                                            </span>
                                            <svg class="w-4 h-4 text-neutral-500 transition-transform duration-200"
                                                 :class="isGroupOpen('{{ addslashes($catName) }}') ? 'rotate-180' : ''"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Group Items List (Dropdown Content) -->
                                    <div x-show="isGroupOpen('{{ addslashes($catName) }}')"
                                         x-transition
                                         class="p-3 space-y-2 border-t border-neutral-100 bg-white">
                                        @foreach($catItems as $item)
                                            <div x-data="{ checked: {{ in_array($item->id, $oldItemIds) ? 'true' : 'false' }} }"
                                                 x-show="search === '' || '{{ strtolower(addslashes($item->name . ' ' . $item->item_code . ' ' . $item->location)) }}'.includes(search.toLowerCase())"
                                                 class="p-3 bg-neutral-50/50 rounded-xl border border-neutral-200 transition-colors space-y-2.5"
                                                 :class="checked ? 'border-neutral-900 ring-1 ring-neutral-900 bg-neutral-50' : 'hover:border-neutral-300'">
                                                <label class="flex items-center gap-3 cursor-pointer text-xs">
                                                    <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" 
                                                           x-model="checked"
                                                           class="rounded-md border-neutral-300 text-neutral-900 focus:ring-neutral-900 h-4 w-4">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-bold text-neutral-900 truncate">{{ $item->name }}</span>
                                                            @if($item->type->value === 'bahan_habis_pakai')
                                                                <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                                    BHP / Sekali Pakai (Stok: {{ $item->stock }} pcs)
                                                                </span>
                                                            @else
                                                                <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                                    Unit Praktikum
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-[11px] text-neutral-500 font-mono">{{ $item->item_code }}</span>
                                                            @if($item->epc_tag)
                                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">UHF RFID</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="font-mono text-[11px] text-neutral-500 shrink-0 px-2 py-0.5 rounded bg-white border border-neutral-200">
                                                        {{ $item->location ?: 'Ruang Alat' }}
                                                    </span>
                                                </label>

                                                @if($item->type->value === 'bahan_habis_pakai')
                                                    <!-- Kolom Wajib Diisi Jumlah Pemakaian untuk Bahan Sekali Pakai / Konektor -->
                                                    <div x-show="checked"
                                                         x-transition
                                                         class="pt-2 border-t border-neutral-200 flex items-center justify-between gap-3 text-xs pl-7">
                                                        <div>
                                                            <span class="font-bold text-neutral-900">Jumlah yang diambil *</span>
                                                            <p class="text-[11px] text-neutral-500">Maksimal {{ $item->stock }} pcs sesuai sisa stok pack ini.</p>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 shrink-0">
                                                            <input type="number"
                                                                   name="quantities[{{ $item->id }}]"
                                                                   min="1"
                                                                   max="{{ max(1, (int) $item->stock) }}"
                                                                   value="{{ old('quantities.'.$item->id, 1) }}"
                                                                   :required="checked"
                                                                   class="w-20 px-2.5 py-1 text-center font-mono font-bold text-xs rounded-lg border border-neutral-300 focus:border-neutral-900 focus:ring-neutral-900" />
                                                            <span class="font-mono text-xs font-semibold text-neutral-600">pcs</span>
                                                        </div>
                                                    </div>
                                                @endif
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
                        <x-input-error :messages="$errors->get('item_ids')" class="mt-2" />
                    </div>
                </div>

                <!-- Guidance Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Integrasi Gate UHF RFID</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Begitu formulir ini disimpan, status alat dalam database berubah menjadi <span class="font-semibold text-neutral-900">dipinjam</span>.
                        </p>
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1">
                            <span class="font-semibold block">Akses Gate Diizinkan</span>
                            <p class="text-emerald-700/90 text-[11px]">Siswa yang membawa alat ini melewati gerbang lab tidak akan memicu sirine alarm gate.</p>
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
                            Alat yang belum dikembalikan melewati batas waktu durasi akan otomatis bertanda <span class="font-semibold text-rose-700">Overdue</span> di dasbor dan mengirim notifikasi pengingat ke siswa.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
