<x-app-layout>
    <x-slot name="heading">Edit Jadwal Preventif #{{ $schedule->id }}</x-slot>
    <x-slot name="title">Edit Jadwal Preventif</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('maintenance.schedules.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Jadwal</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">
                Ubah Jadwal Perawatan <span class="font-mono">#{{ $schedule->id }}</span>
            </h2>

            <form method="POST" action="{{ route('maintenance.schedules.update', $schedule->id) }}" class="space-y-4" x-data="{
                intervalMonths: {{ (int) old('interval_months', $schedule->interval_months) }},
                lastServiced: '{{ old('last_serviced_at', optional($schedule->last_serviced_at)->format('Y-m-d')) }}',
                get nextDuePreview() {
                    if (!this.lastServiced || !this.intervalMonths) return '—';
                    const parts = this.lastServiced.split('-').map(Number);
                    const dt = new Date(parts[0], parts[1] - 1 + Number(this.intervalMonths), parts[2]);
                    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            }">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="item_id" value="Aset Laboratorium *" />
                    <select id="item_id" name="item_id" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id', $schedule->item_id) == $item->id)>
                                {{ $item->name }} ({{ $item->item_code }}) — {{ $item->location }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="title" value="Nama Kegiatan Perawatan *" />
                    <x-text-input id="title" name="title" class="mt-1 w-full text-sm" value="{{ old('title', $schedule->title) }}" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="interval_months" value="Interval Siklus (Bulan) *" />
                        <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="60" x-model="intervalMonths" class="mt-1 w-full font-mono text-sm" required />
                        <div class="flex items-center gap-1.5 mt-2">
                            @foreach([1, 3, 6, 12] as $preset)
                                <button type="button" @click="intervalMonths = {{ $preset }}" class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 transition-colors">
                                    {{ $preset }} bln
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('interval_months')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_serviced_at" value="Tanggal Servis Terakhir *" />
                        <x-text-input id="last_serviced_at" name="last_serviced_at" type="date" x-model="lastServiced" class="mt-1 w-full font-mono text-sm" required />
                        <p class="text-[11px] text-neutral-500 mt-1.5">
                            Jadwal berikutnya: <span class="font-mono font-semibold text-neutral-900" x-text="nextDuePreview"></span>
                        </p>
                        <x-input-error :messages="$errors->get('last_serviced_at')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="status" value="Status Jadwal" />
                    <select id="status" name="status" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">
                        <option value="scheduled" @selected(old('status', $schedule->status) === 'scheduled')>Aktif (Scheduled)</option>
                        <option value="paused" @selected(old('status', $schedule->status) === 'paused')>Dijeda (Paused)</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Checklist / Catatan Teknis" />
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">{{ old('notes', $schedule->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('maintenance.schedules.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Perbarui Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

