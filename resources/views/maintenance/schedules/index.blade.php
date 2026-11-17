<x-app-layout>
    <x-slot name="heading">1. Buat Jadwal Maintenance</x-slot>
    <x-slot name="title">Buat Jadwal Maintenance — M&amp;R</x-slot>

    @php
        $nameGroups = collect($itemGroups)->where('kind', 'name')->values();
        $brandGroups = collect($itemGroups)->where('kind', 'brand')->values();

        $initialGroupKey = $errors->any()
            ? (string) old('selected_group', $preselectedGroupKey ?? '')
            : (string) ($preselectedGroupKey ?? '');

        $groupsPayload = collect($itemGroups)->mapWithKeys(fn ($g) => [
            $g['key'] => [
                'key' => $g['key'],
                'slug' => $g['slug'],
                'kind' => $g['kind'],
                'label' => $g['label'],
                'item_count' => $g['item_count'],
                'total_units' => $g['total_units'],
                'master_url' => $g['kind'] === 'name'
                    ? route('items.index', ['group' => $g['slug']])
                    : route('items.index', ['view' => 'all']),
                'items' => collect($g['items'])->map(fn ($i) => [
                    'id' => (string) $i->id,
                    'name' => $i->name,
                    'item_code' => $i->item_code,
                    'stock' => max(1, (int) $i->stock),
                    'location' => $i->location ?? 'Ruang Alat',
                ])->values()->all(),
            ],
        ])->all();
    @endphp

    <script>
        window.silametPackageMaintenanceForm = function (config) {
            return {
                groupsMap: config.groupsMap || {},
                selectedGroup: String(config.initialGroup || ''),
                maintenanceDate: config.initialDate || '',
                intervalMonths: Number(config.initialInterval || 3),
                groupSearch: '',

                get activeGroup() {
                    if (this.selectedGroup && this.groupsMap[this.selectedGroup]) {
                        return this.groupsMap[this.selectedGroup];
                    }
                    return null;
                },

                get activeItems() {
                    return this.activeGroup ? (this.activeGroup.items || []) : [];
                },

                get activeGroupLabel() {
                    return this.activeGroup ? this.activeGroup.label : '';
                },

                get autoTitle() {
                    return this.activeGroupLabel ? ('Maintenance — ' + this.activeGroupLabel) : '';
                },

                get nextDuePreview() {
                    if (!this.maintenanceDate || !this.intervalMonths || this.intervalMonths < 1) return '—';
                    const parts = this.maintenanceDate.split('-').map(Number);
                    if (parts.length !== 3 || parts.some(Number.isNaN)) return '—';
                    const dt = new Date(parts[0], parts[1] - 1 + Number(this.intervalMonths), parts[2]);
                    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            };
        };
    </script>

    <div class="space-y-4" x-data="silametPackageMaintenanceForm({
        groupsMap: @js($groupsPayload),
        initialGroup: @js($initialGroupKey),
        initialDate: @js($errors->any() ? old('maintenance_date', today()->format('Y-m-d')) : today()->format('Y-m-d')),
        initialInterval: {{ (int) ($errors->any() ? old('interval_months', 3) : 3) }}
    })">

        @include('maintenance.schedules.partials._step_nav', [
            'currentStep' => 'buat',
            'workflowCounts' => $workflowCounts,
        ])

        <!-- Form Penjadwalan Paket Barang (List Menu ala Master Aset) -->
        <div class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-neutral-100">
                <div>
                    <h3 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Jadwalkan Maintenance Paket Barang</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Pilih paket kelompok barang praktikum dari Master Aset, lalu atur tanggal dan siklus berkala perawatannya.</p>
                </div>
                <span class="px-3 py-1 rounded-full font-mono text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200 self-start sm:self-auto shrink-0">
                    {{ $nameGroups->count() }} Paket &middot; {{ $masterItems->count() }} Unit
                </span>
            </div>

            @if($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Gagal menyimpan jadwal maintenance:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 pl-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($masterItems->isEmpty())
                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-6 text-xs text-neutral-600 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <span>Belum ada barang atau paket kelompok di Master Aset. Tambahkan terlebih dahulu melalui menu Master Aset.</span>
                    <a href="{{ route('items.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-neutral-900 text-white font-semibold rounded-full hover:bg-neutral-800 transition-colors whitespace-nowrap shadow-sm">
                        Tambah Barang Master Aset
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('maintenance.schedules.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_form_type" value="single">
                    <input type="hidden" name="go_to_progress" value="1">
                    <input type="hidden" name="status" value="scheduled">
                    <input type="hidden" name="group_label" :value="activeGroupLabel">
                    <input type="hidden" name="title" :value="autoTitle">

                    <!-- Unit dalam paket terpilih otomatis dikirimkan saat form disubmit -->
                    <template x-for="unit in activeItems" :key="unit.id">
                        <div>
                            <input type="hidden" name="item_ids[]" :value="unit.id">
                            <input type="hidden" :name="'quantities[' + unit.id + ']'" :value="unit.stock">
                        </div>
                    </template>

                    <!-- Hidden Select Sync untuk Form Submission & Test Compatibility -->
                    <select id="selected_group"
                            name="selected_group"
                            x-model="selectedGroup"
                            required
                            class="sr-only">
                        <option value="">— Pilih paket barang (misal: TPLINK, PC, MIKROTIK) —</option>
                        @if($nameGroups->isNotEmpty())
                            <optgroup label="Paket Kelompok Barang (Master Aset)">
                                @foreach($nameGroups as $grp)
                                    <option value="{{ $grp['key'] }}" @selected($initialGroupKey === $grp['key'])>
                                        {{ $grp['label'] }} — {{ $grp['item_count'] }} barang ({{ $grp['total_units'] }} unit)
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if($brandGroups->isNotEmpty())
                            <optgroup label="Semua Unit Berdasarkan Merek">
                                @foreach($brandGroups as $grp)
                                    <option value="{{ $grp['key'] }}" @selected($initialGroupKey === $grp['key'])>
                                        {{ $grp['label'] }} — {{ $grp['item_count'] }} barang ({{ $grp['total_units'] }} unit)
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>

                    <!-- 1. PILIH KELOMPOK BARANG (LIST MENU ALA MASTER ASET) -->
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">
                                    1. Pilih Kelompok Barang (Master Aset) *
                                </h4>
                                <p class="text-[11px] text-neutral-500 mt-0.5">
                                    Klik salah satu kelompok di bawah untuk memilih paket barang yang akan di-maintenance.
                                </p>
                            </div>
                            <!-- Pencarian Kelompok -->
                            <div class="relative w-full sm:w-64">
                                <input type="text"
                                       x-model="groupSearch"
                                       placeholder="Cari kelompok (misal: TPLINK, PC)..."
                                       class="w-full text-xs rounded-xl border border-neutral-200 pl-8 pr-3 py-1.5 focus:border-neutral-900 focus:ring-neutral-900 placeholder:text-neutral-400 font-sans" />
                                <svg class="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- LIST MENU CARDS KELOMPOK BARANG ALA MASTER ASET -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($itemGroups as $grp)
                                <div x-show="!groupSearch || '{{ strtolower(addslashes($grp['label'] . ' ' . $grp['kind'])) }}'.includes(groupSearch.toLowerCase())"
                                     @click="selectedGroup = '{{ $grp['key'] }}'"
                                     class="group relative rounded-xl p-4 border transition-all cursor-pointer flex flex-col justify-between"
                                     :class="selectedGroup === '{{ $grp['key'] }}' ? 'border-neutral-900 bg-neutral-50 ring-2 ring-neutral-900 shadow-sm' : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-50/50'">
                                     
                                    <div>
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-8 h-8 rounded-lg bg-neutral-100 text-neutral-700 flex items-center justify-center shrink-0 group-hover:bg-neutral-200 transition-colors font-mono font-bold text-xs">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                </span>
                                                <div class="min-w-0">
                                                    <h5 class="text-xs font-bold text-neutral-900 uppercase tracking-wide font-heading truncate">
                                                        {{ $grp['label'] }}
                                                    </h5>
                                                    <span class="text-[10px] font-mono text-neutral-500">
                                                        {{ $grp['kind'] === 'name' ? 'Kelompok Barang' : 'Kategori Merek' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Checkmark Bulat -->
                                            <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors shrink-0"
                                                 :class="selectedGroup === '{{ $grp['key'] }}' ? 'bg-neutral-900 border-neutral-900 text-white' : 'border-neutral-300 bg-white'">
                                                <svg x-show="selectedGroup === '{{ $grp['key'] }}'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between text-[11px] font-mono border-t border-neutral-100 pt-2.5">
                                            <span class="text-neutral-700 font-semibold">{{ $grp['item_count'] }} barang ({{ $grp['total_units'] }} unit)</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-neutral-100 text-neutral-700">
                                                {{ $grp['total_units'] }} fisik
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Preview Kode Unit Barang -->
                                    @if(!empty($grp['items']))
                                        <div class="mt-2.5 pt-2 border-t border-neutral-100 flex items-center gap-1 flex-wrap">
                                            @foreach(collect($grp['items'])->take(4) as $unit)
                                                <span class="px-1.5 py-0.2 rounded bg-neutral-100 font-mono text-[9px] text-neutral-600">
                                                    {{ $unit->item_code }}
                                                </span>
                                            @endforeach
                                            @if(count($grp['items']) > 4)
                                                <span class="text-[9px] font-mono text-neutral-400">+{{ count($grp['items']) - 4 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('selected_group')" class="mt-1.5" />

                        <!-- Detail Banner Paket Terpilih -->
                        <template x-if="activeGroup !== null">
                            <div class="rounded-xl border border-neutral-300 bg-neutral-100/70 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div class="space-y-1 min-w-0">
                                    <div class="text-xs font-semibold text-neutral-900">
                                        Paket Terpilih: <strong class="text-neutral-900 uppercase" x-text="activeGroup?.label"></strong>
                                        &middot; <span class="font-mono" x-text="(activeGroup?.item_count || 0) + ' barang (' + (activeGroup?.total_units || 0) + ' unit)'"></span>
                                        akan dimasukkan ke antrean maintenance.
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <template x-for="unit in activeItems.slice(0, 10)" :key="'badge-' + unit.id">
                                            <span class="px-2 py-0.5 rounded-md bg-white border border-neutral-200 font-mono text-[10px] font-semibold text-neutral-700"
                                                  x-text="unit.item_code"></span>
                                        </template>
                                        <template x-if="activeItems.length > 10">
                                            <span class="text-[11px] font-mono text-neutral-500"
                                                  x-text="'+' + (activeItems.length - 10) + ' unit lainnya'"></span>
                                        </template>
                                    </div>
                                </div>
                                <a x-show="activeGroup?.master_url"
                                   :href="activeGroup?.master_url"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-800 hover:text-neutral-900 hover:underline shrink-0">
                                    <span>Buka di Master Aset</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- 2. PENGATURAN TANGGAL & SIKLUS MAINTENANCE (DIBAWAHNYA) -->
                    <div class="border-t border-neutral-200 pt-6 space-y-5">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-heading">
                                2. Tanggal &amp; Siklus Pemeliharaan
                            </h4>
                            <p class="text-[11px] text-neutral-500 mt-0.5">
                                Tentukan tanggal mulai pelaksanaan serta interval siklus pemeriksaan berkala ke depan.
                            </p>
                        </div>

                        <!-- Tanggal Pelaksanaan Maintenance -->
                        <div class="bg-neutral-50/70 rounded-xl p-4 border border-neutral-200 space-y-2">
                            <x-input-label for="schedule_maintenance_date" value="Tanggal Jadwal Maintenance *" />
                            <div class="max-w-md">
                                <x-text-input id="schedule_maintenance_date"
                                              name="maintenance_date"
                                              type="date"
                                              x-model="maintenanceDate"
                                              class="w-full font-mono text-xs rounded-xl border-neutral-300 focus:border-neutral-900 focus:ring-neutral-900 bg-white"
                                              required />
                            </div>
                            <p class="text-[11px] text-neutral-500">Tanggal pelaksanaan pemeliharaan paket barang terpilih.</p>
                            <x-input-error :messages="$errors->get('maintenance_date')" class="mt-1" />
                        </div>

                        <!-- Siklus Maintenance (Posisi Khusus & Luas) -->
                        <div class="bg-neutral-50/70 rounded-xl p-4 border border-neutral-200 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <x-input-label for="schedule_interval_months" value="Siklus Pemeliharaan Rutin (Interval Berkala) *" />
                                    <p class="text-[11px] text-neutral-500 mt-0.5">
                                        Pilih seberapa sering paket barang ini harus diperiksa ulang secara berkala:
                                    </p>
                                </div>
                                <!-- Dynamic Next Due Date Badge -->
                                <div class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 font-mono text-xs self-start sm:self-auto">
                                    <span class="text-neutral-500 text-[10px] block">Jatuh Tempo Berikutnya</span>
                                    <span class="font-bold text-neutral-900" x-text="nextDuePreview"></span>
                                </div>
                            </div>

                            <!-- Preset Duration Cards / Pills -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                @php
                                    $presets = [
                                        ['val' => 1, 'title' => '1 Bulan', 'subtitle' => 'Pemeriksaan Bulanan'],
                                        ['val' => 3, 'title' => '3 Bulan', 'subtitle' => 'Triwulan (Standar)'],
                                        ['val' => 6, 'title' => '6 Bulan', 'subtitle' => 'Semesteran'],
                                        ['val' => 12, 'title' => '12 Bulan', 'subtitle' => 'Pemeriksaan Tahunan'],
                                    ];
                                @endphp
                                @foreach($presets as $p)
                                    <button type="button"
                                            @click="intervalMonths = {{ $p['val'] }}"
                                            class="p-3 rounded-xl border text-left transition-all"
                                            :class="Number(intervalMonths) === {{ $p['val'] }} ? 'border-neutral-900 bg-white ring-2 ring-neutral-900 shadow-sm' : 'border-neutral-200 bg-white hover:border-neutral-300 hover:bg-neutral-100/50'">
                                        <div class="font-bold text-xs font-heading"
                                             :class="Number(intervalMonths) === {{ $p['val'] }} ? 'text-neutral-900' : 'text-neutral-700'">
                                            {{ $p['title'] }}
                                        </div>
                                        <div class="text-[10px] text-neutral-500 mt-0.5 font-mono">
                                            {{ $p['subtitle'] }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <!-- Custom Input Interval -->
                            <div class="flex items-center gap-2 pt-1">
                                <span class="text-xs text-neutral-600 font-medium">Atau masukkan jumlah bulan:</span>
                                <x-text-input id="schedule_interval_months"
                                              name="interval_months"
                                              type="number"
                                              min="1"
                                              max="60"
                                              x-model.number="intervalMonths"
                                              class="w-24 font-mono text-xs text-center rounded-lg border-neutral-300 focus:border-neutral-900 focus:ring-neutral-900 bg-white"
                                              required />
                                <span class="text-xs font-semibold text-neutral-700 font-mono">Bulan sekali</span>
                            </div>
                            <x-input-error :messages="$errors->get('interval_months')" class="mt-1" />
                        </div>

                        <!-- 3. Catatan & Poin Checklist Pemeriksaan -->
                        <div class="space-y-1.5">
                            <x-input-label for="schedule_notes" value="Catatan / Poin Checklist Pemeriksaan" />
                            <p class="text-[11px] text-neutral-500">
                                Tuliskan panduan atau poin pemeriksaan teknis untuk paket <strong class="text-neutral-900" x-text="activeGroupLabel || 'barang terpilih'"></strong>.
                            </p>
                            <textarea id="schedule_notes"
                                      name="notes"
                                      rows="4"
                                      placeholder="Contoh:&#10;1. Bersihkan debu body, ventilasi pendingin, dan port koneksi&#10;2. Periksa kabel power, adaptor daya, dan indikator status LED&#10;3. Uji fungsi port LAN/WAN serta kelayakan pakai praktikum&#10;4. Cek pembaruan konfigurasi dan stabilitas perangkat"
                                      class="w-full rounded-xl border-neutral-300 text-xs focus:border-neutral-900 focus:ring-neutral-900">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Tombol Aksi Bawah yang Jelas & Tegas -->
                    <div class="pt-5 border-t border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-xs text-neutral-500">
                            <template x-if="activeGroup">
                                <span>Paket terpilih: <strong class="text-neutral-900" x-text="activeGroup.label"></strong> (<span class="font-mono" x-text="activeGroup.item_count"></span> barang, <span class="font-mono" x-text="activeGroup.total_units"></span> unit).</span>
                            </template>
                            <template x-if="!activeGroup">
                                <span class="text-amber-700 font-medium">Silakan klik salah satu kelompok barang di atas terlebih dahulu.</span>
                            </template>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    :disabled="!activeGroup"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-200 disabled:text-neutral-400 disabled:border-neutral-200 disabled:cursor-not-allowed text-white text-xs font-bold rounded-full transition-all shadow-sm">
                                <span>
                                    Jadwalkan &amp; Mulai Maintenance (<span class="font-mono" x-text="activeGroup ? activeGroup.item_count : 0"></span> Barang)
                                </span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

    </div>
</x-app-layout>
