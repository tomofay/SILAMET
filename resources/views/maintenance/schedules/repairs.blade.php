<x-app-layout>
    <x-slot name="heading">4. Perlu Diperbaiki (Temuan Maintenance)</x-slot>
    <x-slot name="title">Perlu Diperbaiki — M&amp;R — SILAMET</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Temuan Rusak', 'count' => ($workflowCounts['needs_repair'] ?? 0) + ($workflowCounts['ticket_created'] ?? 0)],
            'pending' => ['label' => 'Belum Diajukan Tiket', 'count' => $workflowCounts['needs_repair'] ?? 0],
            'submitted' => ['label' => 'Sudah Masuk Tiket Rusak', 'count' => $workflowCounts['ticket_created'] ?? 0],
        ];
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto" x-data="{
        ticketModalOpen: false,
        submitAction: '',
        brokenModalOpen: false,
        brokenAction: '',
        brokenReason: '',
        itemName: '',
        itemCode: '',
        damagedQty: 1,
        issueDescription: '',
        openTicketModal(btn) {
            this.submitAction = btn.dataset.action;
            this.itemName = btn.dataset.itemName;
            this.itemCode = btn.dataset.itemCode;
            this.damagedQty = Number(btn.dataset.damagedQty || 1);
            this.issueDescription = btn.dataset.defaultIssue || '';
            this.ticketModalOpen = true;
            this.$nextTick(() => document.getElementById('ticket-issue-description')?.focus());
        },
        openBrokenModal(btn) {
            this.brokenAction = btn.dataset.action;
            this.itemName = btn.dataset.itemName;
            this.itemCode = btn.dataset.itemCode;
            this.damagedQty = Number(btn.dataset.damagedQty || 1);
            this.brokenReason = btn.dataset.defaultReason || '';
            this.brokenModalOpen = true;
            this.$nextTick(() => document.getElementById('broken-damage-reason')?.focus());
        }
    }">

        @include('maintenance.schedules.partials._step_nav', [
            'currentStep' => 'rusak',
            'workflowCounts' => $workflowCounts,
        ])

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs space-y-1 shadow-sm">
                <div class="font-bold">Gagal mengajukan tiket barang rusak:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Tabs (Capsule Pills) -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $filter === $key; @endphp
                <a href="{{ route('maintenance.schedules.repairs', ['filter' => $key]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900 shadow-sm' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-2 py-0.5 rounded-full font-mono text-[10px] font-bold {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Tabel Barang Perlu Diperbaiki (Rounded-2xl Table ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Barang (Master Aset)</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Temuan Kerusakan</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jumlah Rusak</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Tanggal Pengecekan</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status Tiket</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($schedules as $schedule)
                            @php
                                $damagedCount = max(1, (int) ($schedule->damaged_quantity ?: $schedule->quantity));
                                $maintDateStr = optional($schedule->maintenance_date ?? $schedule->inspected_at)->format('d/m/Y');
                                $prefilledIssue = trim((string) $schedule->damage_notes);
                                if ($prefilledIssue === '') {
                                    $prefilledIssue = 'Ditemukan kerusakan saat maintenance tanggal ' . $maintDateStr . ' (' . $damagedCount . ' unit).';
                                } elseif ($damagedCount > 1) {
                                    $prefilledIssue = "[{$damagedCount} unit] " . $prefilledIssue;
                                }
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-neutral-900 text-xs font-heading">{{ $schedule->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 max-w-md">
                                    <p class="text-xs text-neutral-800">{{ $schedule->damage_notes ?: 'Perlu pengecekan & perbaikan lanjutan.' }}</p>
                                    @if($schedule->inspectedBy)
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Pelapor maintenance: {{ $schedule->inspectedBy->name }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $damagedCount }} unit
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ $maintDateStr ?? '—' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($schedule->workflow_stage === 'ticket_created' && $schedule->maintenanceTicket)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200 font-mono">
                                            Tiket #{{ $schedule->maintenanceTicket->id }} &middot; {{ $schedule->maintenanceTicket->status->label() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Menunggu Pengajuan Tiket
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
                                        @if($schedule->workflow_stage === 'needs_repair')
                                            <button type="button"
                                                    @click="openBrokenModal($el)"
                                                    data-action="{{ route('maintenance.schedules.mark-broken', $schedule->id) }}"
                                                    data-item-name="{{ $schedule->item->name ?? '-' }}"
                                                    data-item-code="{{ $schedule->item->item_code ?? '—' }}"
                                                    data-damaged-qty="{{ $damagedCount }}"
                                                    data-default-reason="{{ trim((string) $schedule->damage_notes) !== '' ? $schedule->damage_notes : 'Rusak permanen dan tidak layak pakai hasil pemeriksaan maintenance.' }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition-colors shadow-sm"
                                                    title="Tandai rusak permanen, masuk bar Barang Rusak &amp; kurangi Master Aset">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                                </svg>
                                                <span>Masuk Barang Rusak</span>
                                            </button>

                                            <button type="button"
                                                    @click="openTicketModal($el)"
                                                    data-action="{{ route('maintenance.schedules.submit-ticket', $schedule->id) }}"
                                                    data-item-name="{{ $schedule->item->name ?? '-' }}"
                                                    data-item-code="{{ $schedule->item->item_code ?? '—' }}"
                                                    data-damaged-qty="{{ $damagedCount }}"
                                                    data-default-issue="{{ $prefilledIssue }}"
                                                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                </svg>
                                                <span>Masukkan ke Tiket Barang Rusak</span>
                                            </button>
                                        @elseif($schedule->maintenanceTicket)
                                            @if($schedule->maintenanceTicket->status->value === 'resolved')
                                                <a href="{{ route('maintenance.show', $schedule->maintenanceTicket->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 text-xs font-semibold transition-colors shadow-sm">
                                                    Lihat Detail Tiket #{{ $schedule->maintenanceTicket->id }} &rarr;
                                                </a>
                                            @else
                                                <a href="{{ route('maintenance.edit', $schedule->maintenanceTicket->id) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-white hover:bg-neutral-50 text-primary-700 border border-indigo-200 text-xs font-semibold transition-colors shadow-sm">
                                                    Buka Tiket #{{ $schedule->maintenanceTicket->id }} &rarr;
                                                </a>
                                            @endif
                                        @endif

                                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $schedule->id) }}" class="inline" onsubmit="return confirm('Hapus catatan barang perlu diperbaiki ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-rose-50 text-neutral-600 hover:text-rose-600 hover:border-rose-200 transition-colors shadow-sm" title="Hapus" aria-label="Hapus #{{ $schedule->id }}">
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
                                <td colspan="6" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Tidak Ada Barang Perlu Diperbaiki</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Barang yang mengalami kerusakan saat dipilah di tahap Sedang Di-Maintenance akan muncul di halaman ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Ajukan Tiket Pengajuan Barang Rusak (Rounded-2xl Modal) -->
        <template x-if="ticketModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-neutral-950/60" @click="ticketModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 z-10 space-y-4 shadow-xl">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 font-heading">Ajukan Tiket Pengajuan Barang Rusak</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                <span class="font-semibold text-neutral-800" x-text="itemName"></span>
                                <span class="font-mono ml-1" x-text="'(' + itemCode + ')'"></span>
                                <span class="font-mono ml-1" x-text="'· ' + damagedQty + ' unit'"></span>
                            </p>
                        </div>
                        <button type="button" @click="ticketModalOpen = false" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition-colors" aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" :action="submitAction" class="space-y-4">
                        @csrf

                        <div>
                            <label for="ticket-issue-description" class="block text-xs font-semibold text-neutral-700 mb-1.5">Deskripsi Kendala Tiket Kerusakan *</label>
                            <textarea id="ticket-issue-description"
                                      name="issue_description"
                                      x-model="issueDescription"
                                      rows="4"
                                      required
                                      class="w-full rounded-xl border-neutral-200 text-xs focus:border-primary focus:ring-primary"></textarea>
                            <p class="text-[11px] text-neutral-500 mt-1.5">Barang otomatis masuk daftar <strong>Tiket Kerusakan (Open)</strong> dan status alat dikunci sebagai <code>Under Maintenance</code> sampai perbaikan selesai.</p>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <button type="button" @click="ticketModalOpen = false" class="px-4 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-full hover:bg-neutral-50">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full shadow-sm transition-colors">
                                Buat Tiket Kerusakan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Pindahkan Langsung ke Barang Rusak Permanen (Rounded-2xl Modal) -->
        <template x-if="brokenModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-neutral-950/60" @click="brokenModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 z-10 space-y-4 shadow-xl">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 font-heading">Catat Sebagai Barang Rusak Permanen</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                <span class="font-semibold text-neutral-800" x-text="itemName"></span>
                                <span class="font-mono ml-1" x-text="'(' + itemCode + ')'"></span>
                                <span class="font-mono ml-1 text-rose-700 font-semibold" x-text="'· ' + damagedQty + ' unit'"></span>
                            </p>
                        </div>
                        <button type="button" @click="brokenModalOpen = false" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg hover:bg-neutral-100 transition-colors" aria-label="Tutup modal barang rusak">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" :action="brokenAction" class="space-y-4">
                        @csrf

                        <div>
                            <label for="broken-damage-reason" class="block text-xs font-semibold text-neutral-700 mb-1.5">Alasan Kerusakan Permanen</label>
                            <textarea id="broken-damage-reason"
                                      name="damage_reason"
                                      x-model="brokenReason"
                                      rows="3"
                                      required
                                      class="w-full rounded-xl border-neutral-200 text-xs focus:border-rose-500 focus:ring-rose-500"></textarea>
                            <p class="text-[11px] text-rose-800 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2 mt-2 leading-snug">
                                Barang akan langsung dipindahkan ke bar <strong>Barang Rusak</strong> dan stok pada <strong>Master Aset</strong> berkurang otomatis sebesar <span class="font-mono font-bold" x-text="damagedQty + ' unit'"></span>.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2.5">
                            <button type="button" @click="brokenModalOpen = false" class="px-4 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-full hover:bg-neutral-50">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-full shadow-sm transition-colors">
                                Konfirmasi Masuk Barang Rusak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>