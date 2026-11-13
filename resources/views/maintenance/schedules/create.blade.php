<x-app-layout>
    <x-slot name="heading">Tambah Jadwal Perawatan Preventif</x-slot>
    <x-slot name="title">Tambah Jadwal Preventif</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Tambah Jadwal Maintenance Baru</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Langkah 1 dari alur siklus pemeliharaan berkala alat praktikum laboratorium.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('maintenance.schedules.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="create-schedule-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan &amp; Mulai Maintenance</span>
                </button>
            </div>
        </div>

        <form id="create-schedule-form" method="POST" action="{{ route('maintenance.schedules.store') }}" x-data="{
            selectedItemId: '{{ old('item_id', $preselectedItemId ?? '') }}',
            itemNames: @js($items->mapWithKeys(fn ($i) => [(string) $i->id => $i->name])->all()),
            title: @js(old('title', '')),
            titleEdited: @js(filled(old('title'))),
            quantity: {{ (int) old('quantity', 1) }},
            intervalMonths: {{ (int) old('interval_months', 3) }},
            maintenanceDate: '{{ old('maintenance_date', today()->format('Y-m-d')) }}',
            init() {
                if (this.selectedItemId && !this.title) {
                    this.applySuggestedTitle();
                }
            },
            applySuggestedTitle() {
                if (this.titleEdited) return;
                const itemName = this.itemNames[String(this.selectedItemId)];
                this.title = itemName ? ('Maintenance — ' + itemName) : '';
            },
            get nextDuePreview() {
                if (!this.maintenanceDate || !this.intervalMonths) return '—';
                const parts = this.maintenanceDate.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return '—';
                const dt = new Date(parts[0], parts[1] - 1 + Number(this.intervalMonths), parts[2]);
                return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }">
            @csrf
            <input type="hidden" name="go_to_progress" value="1">
            <input type="hidden" name="status" value="scheduled">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Parameter Kegiatan Perawatan</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Pilih alat praktikum, tanggal pelaksanaan, dan interval siklus preventif.</p>
                        </div>

                        <div>
                            <x-input-label for="item_id" value="Nama Alat / Barang (Master Aset) *" />
                            <select id="item_id" name="item_id" x-model="selectedItemId" @change="applySuggestedTitle()" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                <option value="">— Pilih nama alat / barang dari Master Aset —</option>
                                @foreach($items as $item)
                                    @php
                                        $activeCount = $item->preventiveSchedules->where('status', 'scheduled')->count();
                                        $scheduleTag = $item->preventiveSchedules->isEmpty()
                                            ? 'Belum Dijadwalkan'
                                            : ($activeCount > 0 ? "Terjadwal ({$activeCount})" : 'Jadwal Dijeda');
                                    @endphp
                                    <option value="{{ $item->id }}" @selected(old('item_id', $preselectedItemId ?? null) == $item->id)>
                                        {{ $item->name }} ({{ $item->item_code }}) · {{ $item->type->label() }} · Stok {{ $item->stock }} · {{ $item->location }} — [{{ $scheduleTag }}]
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="maintenance_date" value="Tanggal Maintenance *" />
                                <x-text-input id="maintenance_date" name="maintenance_date" type="date" x-model="maintenanceDate" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" required />
                                <x-input-error :messages="$errors->get('maintenance_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="quantity" value="Jumlah Barang Di-Maintenance *" />
                                <x-text-input id="quantity" name="quantity" type="number" min="1" max="10000" x-model="quantity" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" required />
                                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="title" value="Nama Kegiatan Perawatan" />
                            <x-text-input id="title" name="title" x-model="title" @input="titleEdited = true" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" placeholder="Otomatis terisi saat memilih barang" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="interval_months" value="Interval Siklus Preventif Berikutnya (Bulan)" />
                            <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="60" x-model="intervalMonths" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" />
                            <div class="flex items-center justify-between gap-2 mt-2 flex-wrap">
                                <div class="flex items-center gap-1.5">
                                    @foreach([1, 3, 6, 12] as $preset)
                                        <button type="button" @click="intervalMonths = {{ $preset }}" class="px-2.5 py-1 rounded-full font-mono text-[11px] font-semibold border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 transition-colors">
                                            {{ $preset }} bln
                                        </button>
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-neutral-500">
                                    Estimasi jadwal berikutnya: <span class="font-mono font-semibold text-neutral-900" x-text="nextDuePreview"></span>
                                </p>
                            </div>
                            <x-input-error :messages="$errors->get('interval_months')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notes" value="Checklist / Catatan Teknis (Opsional)" />
                            <textarea id="notes" name="notes" rows="3"
                                      class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                      placeholder="Contoh: Bersihkan debu heatsink, cek konektor port SFP/Ethernet, update firmware stabil...">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Info Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Alur 4-Tahap Maintenance</h3>
                        <div class="space-y-3 text-xs text-neutral-600 leading-relaxed">
                            <div class="p-3 rounded-xl bg-neutral-900 text-white space-y-1">
                                <span class="font-semibold block text-xs">Tahap 1: Buat Jadwal</span>
                                <p class="text-neutral-400 text-[11px]">Membuat paket batch maintenance berkala.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200 space-y-1">
                                <span class="font-semibold text-neutral-900 block text-xs">Tahap 2: Sedang Di-Maintenance</span>
                                <p class="text-neutral-500 text-[11px]">Teknisi memeriksa kondisi fisik alat di meja servis.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200 space-y-1">
                                <span class="font-semibold text-neutral-900 block text-xs">Tahap 3 &amp; 4: Hasil Uji</span>
                                <p class="text-neutral-500 text-[11px]">Barang dinyatakan aman atau dibuatkan tiket perbaikan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
