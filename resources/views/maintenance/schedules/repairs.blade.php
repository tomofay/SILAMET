<x-app-layout>
    <x-slot name="heading">4. Perlu Diperbaiki (Temuan Maintenance)</x-slot>
    <x-slot name="title">Perlu Diperbaiki — M&amp;R</x-slot>

    @php
        $tabs = [
            'all' => ['label' => 'Semua Temuan Rusak', 'count' => ($workflowCounts['needs_repair'] ?? 0) + ($workflowCounts['ticket_created'] ?? 0)],
            'pending' => ['label' => 'Belum Diajukan Tiket', 'count' => $workflowCounts['needs_repair'] ?? 0],
            'submitted' => ['label' => 'Sudah Masuk Tiket Rusak', 'count' => $workflowCounts['ticket_created'] ?? 0],
        ];
    @endphp

    <div class="space-y-4" x-data="{
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
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-bold">Gagal mengajukan tiket barang rusak:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($tabs as $key => $tab)
                @php $isActive = $filter === $key; @endphp
                <a href="{{ route('maintenance.schedules.repairs', ['filter' => $key]) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold border transition-colors {{ $isActive ? 'bg-neutral-900 text-white border-neutral-900' : 'bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 border-neutral-200' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded font-mono text-[11px] {{ $isActive ? 'bg-neutral-800 text-neutral-200' : 'bg-neutral-100 text-neutral-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Tabel Barang Perlu Diperbaiki -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang (Master Aset)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Temuan Kerusakan</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah Rusak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tanggal Pengecekan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status Tiket</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
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
                            <tr class="table-row-clean">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $schedule->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                        {{ $schedule->item->item_code ?? '—' }} &middot; {{ $schedule->item->location ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-md">
                                    <p class="text-xs text-neutral-800">{{ $schedule->damage_notes ?: 'Perlu pengecekan & perbaikan lanjutan.' }}</p>
                                    @if($schedule->inspectedBy)
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Pelapor maintenance: {{ $schedule->inspectedBy->name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $damagedCount }} unit
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-700 whitespace-nowrap">
                                    {{ $maintDateStr ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($schedule->workflow_stage === 'ticket_created' && $schedule->maintenanceTicket)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-primary-50 text-primary-700 border border-indigo-200 font-mono">
                                            Tiket #{{ $schedule->maintenanceTicket->id }} &middot; {{ $schedule->maintenanceTicket->status->label() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Menunggu Pengajuan Tiket
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if($schedule->workflow_stage === 'needs_repair')
                                            <button type="button"
                                                    @click="openBrokenModal($el)"
                                                    data-action="{{ route('maintenance.schedules.mark-broken', $schedule->id) }}"
                                                    data-item-name="{{ $schedule->item->name ?? '-' }}"
                                                    data-item-code="{{ $schedule->item->item_code ?? '—' }}"
                                                    data-damaged-qty="{{ $damagedCount }}"
                                                    data-default-reason="{{ trim((string) $schedule->damage_notes) !== '' ? $schedule->damage_notes : 'Rusak permanen dan tidak layak pakai hasil pemeriksaan maintenance.' }}"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-semibold transition-colors"
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
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-700 text-white text-[11px] font-semibold border border-primary/40 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                </svg>
                                                <span>Masukkan ke Tiket Barang Rusak</span>
                                            </button>
                                        @elseif($schedule->maintenanceTicket)
                                            @if($schedule->maintenanceTicket->status->value === 'resolved')
                                                <a href="{{ route('maintenance.show', $schedule->maintenanceTicket->id) }}"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 text-[11px] font-semibold transition-colors">
                                                    Lihat Detail Tiket #{{ $schedule->maintenanceTicket->id }}
                                                </a>
                                            @else
                                                <a href="{{ route('maintenance.edit', $schedule->maintenanceTicket->id) }}"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-neutral-50 text-primary-700 border border-indigo-200 text-[11px] font-semibold transition-colors">
                                                    Buka Tiket #{{ $schedule->maintenanceTicket->id }}
                                                </a>
                                            @endif
                                        @endif

                                        <form method="POST" action="{{ route('maintenance.schedules.destroy', $schedule->id) }}" class="inline" onsubmit="return confirm('Hapus catatan barang perlu diperbaiki ini?')">
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
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Tidak Ada Barang Perlu Diperbaiki</p>
                                    <p class="text-xs text-neutral-500 mt-1">Barang yang mengalami kerusakan saat dipilah di tahap Sedang Di-Maintenance akan muncul di halaman ini.</p>
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

        <!-- Modal Ajukan Tiket Pengajuan Barang Rusak -->
        <template x-if="ticketModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-neutral-950/60" @click="ticketModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 z-10 space-y-4">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900">Ajukan Tiket Pengajuan Barang Rusak</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                <span class="font-semibold text-neutral-800" x-text="itemName"></span>
                                <span class="font-mono ml-1" x-text="'(' + itemCode + ')'"></span>
                                <span class="font-mono ml-1" x-text="'· ' + damagedQty + ' unit'"></span>
                            </p>
                        </div>
                        <button type="button" @click="ticketModalOpen = false" class="p-1 text-neutral-400 hover:text-neutral-700 rounded-lg" aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" :action="submitAction" class="space-y-3.5">
                        @csrf

                        <div>
                            <label for="ticket-issue-description" class="block text-xs font-semibold text-neutral-700 mb-1">Deskripsi Kendala Tiket Kerusakan *</label>
                            <textarea id="ticket-issue-description"
                                      name="issue_description"
                                      x-model="issueDescription"
                                      rows="4"
                                      required
                                      class="w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary"></textarea>
                            <p class="text-[11px] text-neutral-500 mt-1">Barang otomatis masuk daftar <strong>Tiket Kerusakan (Open)</strong> dan status alat dikunci sebagai <code>Under Maintenance</code> sampai perbaikan selesai.</p>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                            <button type="button" @click="ticketModalOpen = false" class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                                Buat Tiket Kerusakan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Pindahkan Langsung ke Barang Rusak Permanen -->
        <template x-if="brokenModalOpen">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-neutral-950/60" @click="brokenModalOpen = false"></div>
                <div class="relative bg-white border border-neutral-200 rounded-xl max-w-md w-full p-5 z-10 space-y-4">
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-neutral-100">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900">Catat Sebagai Barang Rusak Permanen</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                <span class="font-semibold text-neutral-800" x-text="itemName"></span>
                                <span class="font-mono ml-1" x-text="'(' + itemCode + ')'"></span>
                                <span class="font-mono ml-1 text-rose-700 font-semibold" x-text="'· ' + damagedQty + ' unit'"></span>
                            </p>
                        </div>
                        <button type="button" @click="brokenModalOpen = false" class="p-1 text-neutral-400 hover:text-neutral-700 rounded-lg" aria-label="Tutup modal barang rusak">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" :action="brokenAction" class="space-y-3.5">
                        @csrf

                        <div>
                            <label for="broken-damage-reason" class="block text-xs font-semibold text-neutral-700 mb-1">Alasan Kerusakan Permanen</label>
                            <textarea id="broken-damage-reason"
                                      name="damage_reason"
                                      x-model="brokenReason"
                                      rows="3"
                                      required
                                      class="w-full rounded-lg border-neutral-300 text-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                            <p class="text-[11px] text-rose-800 bg-rose-50 border border-rose-200 rounded-lg px-2.5 py-2 mt-2 leading-snug">
                                Barang akan langsung dipindahkan ke bar <strong>Barang Rusak</strong> dan stok pada <strong>Master Aset</strong> berkurang otomatis sebesar <span class="font-mono font-bold" x-text="damagedQty + ' unit'"></span>.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                            <button type="button" @click="brokenModalOpen = false" class="px-3.5 py-2 text-xs font-semibold text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold rounded-lg transition-colors">
                                Konfirmasi Masuk Barang Rusak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>

