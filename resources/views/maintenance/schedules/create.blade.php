<x-app-layout>
    <x-slot name="heading">Tambah Jadwal Perawatan Preventif</x-slot>
    <x-slot name="title">Tambah Jadwal Preventif</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Halaman 1: Buat Jadwal</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">Formulir Pembuatan Jadwal Maintenance</h2>

            <form method="POST" action="{{ route('maintenance.schedules.store') }}" class="space-y-4" x-data="{
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

                <div>
                    <x-input-label for="item_id" value="Nama Alat / Barang (Master Aset) *" />
                    <select id="item_id" name="item_id" x-model="selectedItemId" @change="applySuggestedTitle()" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
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
                        <x-text-input id="maintenance_date" name="maintenance_date" type="date" x-model="maintenanceDate" class="mt-1 w-full font-mono text-sm" required />
                        <x-input-error :messages="$errors->get('maintenance_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="quantity" value="Jumlah Barang Di-Maintenance *" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" max="10000" x-model="quantity" class="mt-1 w-full font-mono text-sm" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="title" value="Nama Kegiatan Perawatan" />
                    <x-text-input id="title" name="title" x-model="title" @input="titleEdited = true" class="mt-1 w-full text-sm" placeholder="Otomatis terisi saat memilih barang" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="interval_months" value="Interval Siklus Preventif Berikutnya (Bulan)" />
                    <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="60" x-model="intervalMonths" class="mt-1 w-full font-mono text-sm" />
                    <div class="flex items-center justify-between gap-2 mt-2 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            @foreach([1, 3, 6, 12] as $preset)
                                <button type="button" @click="intervalMonths = {{ $preset }}" class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 transition-colors">
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
                    <x-input-label for="notes" value="Checklist / Catatan Teknis (opsional)" />
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary"
                              placeholder="Contoh: Bersihkan debu heatsink, cek port SFP/Ethernet, update firmware stabil...">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('maintenance.schedules.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan &amp; Mulai Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
