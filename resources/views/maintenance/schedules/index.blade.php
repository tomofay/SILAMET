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

        <!-- Form Sederhana Penjadwalan Paket Barang (Synced Master Aset) -->
        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 mb-5 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900 tracking-tight">Jadwalkan Maintenance Paket Barang</h3>
                    <p class="text-[11px] text-neutral-500 mt-0.5">Pilih paket nama barang dari Master Aset (misal TPLINK, PC, MIKROTIK), tentukan tanggal, siklus bulanan ke depan, serta daftar hal yang harus diperiksa.</p>
                </div>
                <span class="px-2.5 py-1 rounded-md font-mono text-[11px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200 self-start sm:self-auto shrink-0">
                    {{ $nameGroups->count() }} Paket · {{ $masterItems->count() }} Unit
                </span>
            </div>

            @if($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 px-3.5 py-2.5 rounded-lg text-xs space-y-1">
                    <div class="font-bold">Gagal menyimpan jadwal maintenance:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($masterItems->isEmpty())
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 text-xs text-neutral-600 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <span>Belum ada barang atau paket kelompok di Master Aset. Tambahkan terlebih dahulu melalui menu Master Aset.</span>
                    <a href="{{ route('items.create') }}" class="inline-flex items-center justify-center px-3 py-1.5 bg-primary text-white font-semibold rounded-lg whitespace-nowrap">
                        Tambah Barang Master Aset
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('maintenance.schedules.store') }}" class="space-y-5">
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

                    <!-- 1. Dropdown Paket Nama Barang -->
                    <div class="space-y-2.5">
                        <div>
                            <x-input-label for="selected_group" value="Paket Nama Barang (Master Aset) *" />
                            <select id="selected_group"
                                    name="selected_group"
                                    x-model="selectedGroup"
                                    required
                                    class="mt-1.5 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">
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
                            <x-input-error :messages="$errors->get('selected_group')" class="mt-1.5" />
                        </div>

                        <!-- Info Paket Terpilih (ringkas tanpa daftar centang yang penuh) -->
                        <template x-if="activeGroup !== null">
                            <div class="rounded-lg border border-indigo-200 bg-primary-50/50 px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                <div class="space-y-1 min-w-0">
                                    <div class="text-xs font-semibold text-neutral-900">
                                        Paket <span class="text-primary-700" x-text="activeGroup?.label"></span>:
                                        <span class="font-mono font-bold" x-text="(activeGroup?.item_count || 0) + ' barang (' + (activeGroup?.total_units || 0) + ' unit)'"></span>
                                        akan dijadwalkan bersama.
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <template x-for="unit in activeItems.slice(0, 8)" :key="'badge-' + unit.id">
                                            <span class="px-1.5 py-0.5 rounded bg-white border border-indigo-200/80 font-mono text-[10px] font-semibold text-neutral-700"
                                                  x-text="unit.item_code"></span>
                                        </template>
                                        <template x-if="activeItems.length > 8">
                                            <span class="text-[11px] font-mono text-neutral-500"
                                                  x-text="'+' + (activeItems.length - 8) + ' unit lainnya'"></span>
                                        </template>
                                    </div>
                                </div>

                                <a x-show="activeGroup?.master_url"
                                   :href="activeGroup?.master_url"
                                   class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 hover:underline whitespace-nowrap shrink-0">
                                    <span>Lihat di Master Aset</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </template>
                    </div>

                    <!-- 2. Jadwal Maintenance & Siklus Ke Depan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="schedule_maintenance_date" value="Tanggal Jadwal Maintenance *" />
                            <x-text-input id="schedule_maintenance_date"
                                          name="maintenance_date"
                                          type="date"
                                          x-model="maintenanceDate"
                                          class="mt-1.5 w-full font-mono text-sm"
                                          required />
                            <p class="text-[11px] text-neutral-500 mt-1.5">Tanggal pelaksanaan maintenance paket barang ini.</p>
                            <x-input-error :messages="$errors->get('maintenance_date')" class="mt-1.5" />
                        </div>

                        <div>
                            <x-input-label for="schedule_interval_months" value="Siklus Maintenance Ke Depan (Per Berapa Bulan) *" />
                            <div class="mt-1.5 flex items-center gap-2">
                                <x-text-input id="schedule_interval_months"
                                              name="interval_months"
                                              type="number"
                                              min="1"
                                              max="60"
                                              x-model="intervalMonths"
                                              class="w-28 font-mono text-sm text-center"
                                              required />
                                <span class="text-xs font-semibold text-neutral-600">Bulan sekali</span>
                                <div class="flex items-center gap-1 flex-wrap ml-auto">
                                    @foreach([1, 3, 6, 12] as $preset)
                                        <button type="button"
                                                @click="intervalMonths = {{ $preset }}"
                                                class="px-2.5 py-1.5 rounded-lg font-mono text-[11px] font-semibold border transition-colors"
                                                :class="Number(intervalMonths) === {{ $preset }} ? 'bg-neutral-900 text-white border-neutral-900' : 'border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-neutral-700'">
                                            {{ $preset }} bln
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-1.5">
                                Maintenance berikutnya otomatis jatuh tempo pada: <strong class="font-mono text-neutral-900" x-text="nextDuePreview"></strong>
                            </p>
                            <x-input-error :messages="$errors->get('interval_months')" class="mt-1.5" />
                        </div>
                    </div>

                    <!-- 3. Catatan Checklist Apa Saja yang Harus Di-Maintenance -->
                    <div>
                        <x-input-label for="schedule_notes" value="Catatan Hal yang Harus Di-Maintenance pada Paket Barang" />
                        <p class="text-[11px] text-neutral-500 mt-0.5 mb-1.5">
                            Tuliskan poin pengecekan atau perawatan yang harus dikerjakan untuk paket <strong x-text="activeGroupLabel || 'barang terpilih'"></strong>.
                        </p>
                        <textarea id="schedule_notes"
                                  name="notes"
                                  rows="5"
                                  placeholder="Contoh:&#10;1. Bersihkan debu body, kipas pendingin, dan ventilasi&#10;2. Periksa kabel power, adaptor daya, dan indikator LED&#10;3. Uji fungsi port LAN/WAN serta kelayakan pakai praktikum&#10;4. Cek pembaruan firmware dan catatan suhu perangkat"
                                  class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">{{ old('notes') }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1.5" />
                    </div>

                    <!-- Aksi Submit -->
                    <div class="pt-4 border-t border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-[11px] text-neutral-500">
                            Setelah disimpan, seluruh unit dalam paket terpilih masuk ke halaman <strong>Sedang Di-Maintenance</strong> untuk pengecekan dan pemilahan kondisi.
                        </span>
                        <button type="submit"
                                :disabled="!activeGroup"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-700 disabled:bg-neutral-200 disabled:text-neutral-500 disabled:border-neutral-200 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors whitespace-nowrap shrink-0">
                            <span>
                                Jadwalkan &amp; Mulai Maintenance (<span class="font-mono" x-text="activeGroup ? activeGroup.item_count : 0"></span> Barang)
                            </span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>
</x-app-layout>
