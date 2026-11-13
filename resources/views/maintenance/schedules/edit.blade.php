<x-app-layout>
    <x-slot name="heading">Edit Jadwal Maintenance #{{ $schedule->id }}</x-slot>
    <x-slot name="title">Edit Jadwal Maintenance</x-slot>

    @php
        $backRoute = match ($schedule->workflow_stage) {
            'in_maintenance' => route('maintenance.schedules.in-progress'),
            'needs_repair', 'ticket_created' => route('maintenance.schedules.repairs'),
            'safe', 'scheduled' => route('maintenance.schedules.safe'),
            default => route('maintenance.schedules.index'),
        };
        $backLabel = match ($schedule->workflow_stage) {
            'in_maintenance' => 'Kembali ke Sedang Di-Maintenance',
            'needs_repair', 'ticket_created' => 'Kembali ke Perlu Diperbaiki',
            'safe', 'scheduled' => 'Kembali ke Barang Aman',
            default => 'Kembali ke Buat Jadwal',
        };
        $stageBadge = match ($schedule->workflow_stage) {
            'in_maintenance' => ['label' => 'Sedang Di-Maintenance', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'needs_repair' => ['label' => 'Perlu Diperbaiki', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
            'ticket_created' => ['label' => 'Sudah Masuk Tiket Rusak', 'class' => 'bg-primary-50 text-primary-700 border-indigo-200'],
            default => ['label' => 'Barang Aman / Terjadwal', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        };
    @endphp

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ $backRoute }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">
                            Ubah Data Maintenance <span class="font-mono">#{{ $schedule->id }}</span>
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $stageBadge['class'] }}">
                            {{ $stageBadge['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ $backLabel }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $backRoute }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="edit-schedule-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Perbarui Jadwal</span>
                </button>
            </div>
        </div>

        <form id="edit-schedule-form" method="POST" action="{{ route('maintenance.schedules.update', $schedule->id) }}" x-data="{
            intervalMonths: {{ (int) old('interval_months', $schedule->interval_months) }},
            lastServiced: '{{ old('last_serviced_at', optional($schedule->last_serviced_at)->format('Y-m-d')) }}',
            get nextDuePreview() {
                if (!this.lastServiced || !this.intervalMonths) return '—';
                const parts = this.lastServiced.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return '—';
                const dt = new Date(parts[0], parts[1] - 1 + Number(this.intervalMonths), parts[2]);
                return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Detail Perawatan Preventif</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Parameter siklus servis dan tanggal pelaksanaan.</p>
                        </div>

                        <div>
                            <x-input-label for="item_id" value="Nama Alat / Barang (Master Aset) *" />
                            <select id="item_id" name="item_id" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" @selected(old('item_id', $schedule->item_id) == $item->id)>
                                        {{ $item->name }} ({{ $item->item_code }}) · {{ $item->type->label() }} — {{ $item->location }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="title" value="Nama Kegiatan Maintenance *" />
                            <x-text-input id="title" name="title" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('title', $schedule->title) }}" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="maintenance_date" value="Tanggal Pelaksanaan Maintenance *" />
                                <x-text-input id="maintenance_date"
                                              name="maintenance_date"
                                              type="date"
                                              value="{{ old('maintenance_date', optional($schedule->maintenance_date ?? $schedule->last_serviced_at)->format('Y-m-d')) }}"
                                              class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900"
                                              required />
                                <x-input-error :messages="$errors->get('maintenance_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="quantity" value="Jumlah Barang (Unit) *" />
                                <x-text-input id="quantity"
                                              name="quantity"
                                              type="number"
                                              min="1"
                                              max="10000"
                                              value="{{ old('quantity', $schedule->quantity ?? 1) }}"
                                              class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900"
                                              required />
                                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="interval_months" value="Interval Siklus Preventif (Bulan) *" />
                                <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="60" x-model="intervalMonths" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" required />
                                <div class="flex items-center gap-1.5 mt-2">
                                    @foreach([1, 3, 6, 12] as $preset)
                                        <button type="button" @click="intervalMonths = {{ $preset }}" class="px-2.5 py-1 rounded-full font-mono text-[11px] font-semibold border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-neutral-700 transition-colors">
                                            {{ $preset }} bln
                                        </button>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('interval_months')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_serviced_at" value="Tanggal Dasar / Servis Terakhir *" />
                                <x-text-input id="last_serviced_at" name="last_serviced_at" type="date" x-model="lastServiced" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" required />
                                <p class="text-[11px] text-neutral-500 mt-1.5">
                                    Jadwal berikutnya: <span class="font-mono font-semibold text-neutral-900" x-text="nextDuePreview"></span>
                                </p>
                                <x-input-error :messages="$errors->get('last_serviced_at')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="status" value="Status Pengingat Preventif" />
                            <select id="status" name="status" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900">
                                <option value="scheduled" @selected(old('status', $schedule->status) === 'scheduled')>Aktif (Scheduled)</option>
                                <option value="paused" @selected(old('status', $schedule->status) === 'paused')>Dijeda (Paused)</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        @if(filled($schedule->damage_notes))
                            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-900">
                                <div class="font-semibold">Catatan Temuan Kerusakan:</div>
                                <p class="mt-1 text-rose-800">{{ $schedule->damage_notes }}</p>
                            </div>
                        @endif

                        <div>
                            <x-input-label for="notes" value="Checklist / Catatan Teknis" />
                            <textarea id="notes" name="notes" rows="3"
                                      class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900">{{ old('notes', $schedule->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Info Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Tahap Alur Saat Ini</h3>
                        <div class="p-3.5 rounded-xl border text-xs {{ $stageBadge['class'] }}">
                            <span class="font-bold block">{{ $stageBadge['label'] }}</span>
                            <span class="text-[11px] mt-0.5 block opacity-90">ID Jadwal: #{{ $schedule->id }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
