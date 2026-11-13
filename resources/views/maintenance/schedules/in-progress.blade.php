<x-app-layout>
    <x-slot name="heading">2. Sedang Di-Maintenance</x-slot>
    <x-slot name="title">Sedang Di-Maintenance — M&amp;R</x-slot>

    @php
        $allVisibleIds = $schedules->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
    @endphp

    <div class="space-y-4" x-data="{
        triageModalOpen: false,
        triageAction: '',
        itemName: '',
        itemCode: '',
        totalQty: 1,
        damagedQty: 1,
        damageDisposition: 'needs_repair',
        damageNotes: '',
        allVisibleIds: @js($allVisibleIds),
        selectedRows: [],
        get allRowsSelected() {
            return this.allVisibleIds.length > 0 && this.selectedRows.length === this.allVisibleIds.length;
        },
        toggleAllRows() {
            if (this.allRowsSelected) {
                this.selectedRows = [];
            } else {
                this.selectedRows = [...this.allVisibleIds];
            }
        },
        openTriageModal(btn) {
            this.triageAction = btn.dataset.action;
            this.itemName = btn.dataset.itemName;
            this.itemCode = btn.dataset.itemCode;
            this.totalQty = Math.max(1, Number(btn.dataset.quantity || 1));
            this.damagedQty = 1;
            this.damageDisposition = 'needs_repair';
            this.damageNotes = '';
            this.triageModalOpen = true;
            this.$nextTick(() => document.getElementById('triage-damage-notes')?.focus());
        },
        get safeQtyPreview() {
            const d = Math.min(this.totalQty, Math.max(0, Number(this.damagedQty || 0)));
            return Math.max(0, this.totalQty - d);
        }
    }">

        @include('maintenance.schedules.partials._step_nav', [
            'currentStep' => 'progres',
            'workflowCounts' => $workflowCounts,
        ])

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-bold">Gagal menyimpan pemilahan hasil maintenance:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header & Pencarian Tahap 2 -->
        <div class="bg-white rounded-xl border border-neutral-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-neutral-900 tracking-tight">Daftar Barang Sedang Di-Maintenance</h3>
                <p class="text-[11px] text-neutral-500 mt-0.5">Pilah setiap unit setelah pengecekan: <strong>Aman</strong> masuk ke halaman Barang Aman, <strong>Rusak</strong> masuk ke halaman Perlu Diperbaiki.</p>
            </div>
            <form method="GET" action="{{ route('maintenance.schedules.in-progress') }}" class="flex items-center gap-2">
                <input type="search"
                       name="q"
                       value="{{ $search }}"
                       placeholder="Cari nama / kode barang..."
                       class="w-full sm:w-56 rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary" />
                <button type="submit" class="px-3 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-semibold rounded-lg border border-neutral-200 transition-colors">
                    Cari
                </button>
            </form>
        </div>

        <!-- Bar Aksi Cepat Multi-Pilih (hanya tampil jika ada baris dicentang) -->
        <template x-if="selectedRows.length > 0">
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
                <div class="text-xs text-emerald-900 font-semibold">
                    <span class="font-mono font-bold" x-text="selectedRows.length"></span> barang dipilih untuk ditandai Aman sekaligus.
                </div>
                <form method="POST" action="{{ route('maintenance.schedules.bulk-triage-safe') }}" class="flex items-center gap-2">
                    @csrf
                    <template x-for="sid in selectedRows" :key="sid">
                        <input type="hidden" name="schedule_ids[]" :value="sid" />
                    </template>
                    <button type="button" @click="selectedRows = []" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-600 bg-white border border-neutral-200 hover:bg-neutral-50">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Tandai <span x-text="selectedRows.length"></span> Barang Terpilih Aman</span>
                    </button>
                </form>
            </div>
        </template>

        <!-- Tabel Progres Maintenance -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            @if($schedules->isNotEmpty())
                                <th class="w-10 px-4 py-3 text-left">
                                    <input type="checkbox"
                                           :checked="allRowsSelected"
                                           @change="toggleAllRows()"
                                           title="Pilih semua baris"
                                           aria-label="Pilih semua baris"
                                           class="rounded border-neutral-300 text-primary focus:ring-primary" />
                                </th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang (Master Aset)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kegiatan &amp; Catatan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal Maintenance</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pilah Hasil Maintenance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($schedules as $schedule)
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           value="{{ $schedule->id }}"
                                           x-model="selectedRows"
                                           aria-label="Pilih {{ $schedule->item->name ?? 'Barang' }}"
                                           class="rounded border-neutral-300 text-primary focus:ring-primary" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-semibold text-neutral-900 text-xs">{{ $schedule->item->name ?? '-' }}</span>
                                        @if($schedule->item)
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">
                                                {{ $schedule->item->type->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-sm">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-semibold text-neutral-900">{{ $schedule->title }}</span>
                                        @if(filled($schedule->item?->category))
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200">
                                                {{ $schedule->item->category }}
                                            </span>
                                        @endif
                                    </div>
                                    @if(filled($schedule->notes))
                                        <p class="text-[11px] text-neutral-600 mt-1 whitespace-pre-line">{{ $schedule->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ optional($schedule->maintenance_date ?? $schedule->last_serviced_at)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        {{ $schedule->quantity }} unit
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
                                        <form method="POST" action="{{ route('maintenance.schedules.triage', $schedule->id) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="mode" value="all_safe">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition-colors"
                                                    title="Tandai semua unit aman &amp; layak pakai">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>{{ $schedule->quantity > 1 ? 'Semua Aman' : 'Aman' }}</span>
                                            </button>
                                        </form>

                                        <button type="button"
                                                @click="openTriageModal($el)"
                                                data-action="{{ route('maintenance.schedules.triage', $schedule->id) }}"
                                                data-item-name="{{ $schedule->item->name ?? '-' }}"
                                                data-item-code="{{ $schedule->item->item_code ?? '—' }}"
                                                data-quantity="{{ $schedule->quantity }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-semibold transition-colors"
                                                title="Catat barang rusak atau pilah sebagian rusak">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>{{ $schedule->quantity > 1 ? 'Pilah / Ada Rusak' : 'Rusak' }}</span>
                                        </button>

                                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $schedule->id) }}" class="inline" onsubmit="return confirm('Hapus item ini dari antrean maintenance?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon-action hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200" title="Hapus" aria-label="Hapus #{{ $schedule->id }}">
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
                                <td colspan="5" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Tidak Ada Barang yang Sedang Di-Maintenance</p>
                                    <p class="text-xs text-neutral-500 mt-1">Buat jadwal maintenance baru pada langkah 1 agar barang masuk ke daftar pengecekan ini.</p>
                                    <a href="{{ route('maintenance.schedules.index') }}" class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                        Ke Halaman 1: Buat Jadwal
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Pilah Kerusakan Barang -->
        <template x-if="triageModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-neutral-950/60" @click="triageModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 z-10 space-y-4">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900">Laporkan Kerusakan / Pilah Barang</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                <span class="font-semibold text-neutral-800" x-text="itemName"></span>
                                <span class="font-mono ml-1" x-text="'(' + itemCode + ')'"></span>
                            </p>
                        </div>
                        <button type="button" @click="triageModalOpen = false" class="p-1 text-neutral-400 hover:text-neutral-700 rounded-lg" aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" :action="triageAction" class="space-y-3.5">
                        @csrf
                        <input type="hidden" name="mode" value="split">

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-600">Total Di-Maintenance</span>
                                <span class="font-mono font-bold text-neutral-900" x-text="totalQty + ' unit'"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div>
                                    <label for="triage-damaged-qty" class="block text-[11px] font-semibold text-rose-700 mb-1">Jumlah Rusak *</label>
                                    <input id="triage-damaged-qty"
                                           name="damaged_quantity"
                                           type="number"
                                           min="1"
                                           :max="totalQty"
                                           x-model="damagedQty"
                                           :readonly="totalQty === 1"
                                           class="w-full rounded-lg border-neutral-300 font-mono text-sm focus:border-primary focus:ring-primary"
                                           required />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-emerald-700 mb-1">Otomatis Aman</label>
                                    <div class="px-3 py-2 rounded-lg border border-neutral-200 bg-white font-mono text-sm font-bold text-emerald-700"
                                         x-text="safeQtyPreview + ' unit'"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Tindak Lanjut Barang Rusak *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="damage_disposition" value="needs_repair" x-model="damageDisposition" class="peer sr-only" />
                                    <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-amber-500 peer-checked:bg-amber-50/70 transition-colors h-full">
                                        <div class="text-xs font-bold text-neutral-900">Perlu Diperbaiki</div>
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Masuk daftar perbaikan dulu atau ajukan ke tiket servis.</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="damage_disposition" value="broken" x-model="damageDisposition" class="peer sr-only" />
                                    <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-rose-600 peer-checked:bg-rose-50/70 transition-colors h-full">
                                        <div class="text-xs font-bold text-rose-900">Rusak Permanen</div>
                                        <p class="text-[11px] text-rose-700 mt-0.5">Langsung masuk Barang Rusak &amp; kurangi Master Aset.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="triage-damage-notes" class="block text-xs font-semibold text-neutral-700 mb-1">Temuan Kerusakan / Kondisi Alat *</label>
                            <textarea id="triage-damage-notes"
                                      name="damage_notes"
                                      x-model="damageNotes"
                                      rows="3"
                                      required
                                      placeholder="Jelaskan kerusakan yang ditemukan (misal: port tidak menyala, adaptor mati, pin bengkok)..."
                                      class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                            <button type="button" @click="triageModalOpen = false" class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50">
                                Batal
                            </button>
                            <button type="submit"
                                    :class="damageDisposition === 'broken' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-rose-600 hover:bg-rose-700'"
                                    class="px-4 py-2 text-white text-xs font-semibold rounded-lg transition-colors"
                                    x-text="damageDisposition === 'broken' ? 'Masuk Barang Rusak & Kurangi Aset' : 'Pindahkan ke Perlu Diperbaiki'">
                                Pindahkan ke Perlu Diperbaiki
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>
