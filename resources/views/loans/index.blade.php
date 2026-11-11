<x-app-layout>
    <x-slot name="heading">Daftar Peminjaman Alat</x-slot>
    <x-slot name="title">Peminjaman</x-slot>

    <div class="space-y-4">
        
        <!-- Header Overview -->
        <div class="bg-white rounded-xl p-4 sm:p-5 border border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Sirkulasi & Riwayat Peminjaman Siswa</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Pemantauan barang yang dipinjam mandiri via Kiosk maupun dicatat oleh teknisi.</p>
            </div>
            <div>
                <a href="{{ route('loans.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Catat Peminjaman Baru</span>
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Siswa Peminjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang Dipinjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Waktu Pinjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Batas Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Kondisi Balik</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($loans as $loan)
                            @php
                                $isOverdue = $loan->status->value === 'aktif' && $loan->due_at?->isPast();
                            @endphp
                            <tr class="transition-colors {{ $isOverdue ? 'bg-rose-50/40 hover:bg-rose-50/70' : 'hover:bg-neutral-50' }}">
                                <td class="px-4 py-3">
                                    <div class="text-neutral-900 font-semibold text-xs">{{ $loan->user->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">NIS: {{ $loan->user->nis_nip ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-neutral-900 font-semibold text-xs">{{ $loan->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $loan->item->item_code ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-neutral-700 font-medium whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-neutral-100 border border-neutral-200 text-neutral-700">
                                        {{ $loan->usage_duration?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ optional($loan->borrowed_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($isOverdue)
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[11px] font-semibold text-rose-700 px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200">
                                            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>TERLAMBAT ({{ optional($loan->due_at)->format('d/m H:i') }})</span>
                                        </span>
                                    @else
                                        <span class="font-mono text-xs text-neutral-600">{{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($loan->condition_in)
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $loan->condition_in === 'bagus' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                            {{ $loan->condition_in === 'bagus' ? 'Kondisi Bagus' : 'Rusak/Kendala' }}
                                        </span>
                                    @else
                                        <span class="text-neutral-400 text-xs font-mono">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $lbadge = match($loan->status->value) {
                                            'aktif' => 'bg-brand-50 text-brand-700 border-brand-100',
                                            'menunggu_approval_kembali' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $lbadge }}">
                                        {{ $loan->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        @if($loan->capture_image_path)
                                            <a href="{{ route('loans.show', $loan->id) }}" class="btn-icon-action text-primary-600 hover:text-primary-700 hover:bg-primary-50" title="Lihat Detail & Foto Verifikasi Kiosk" aria-label="Foto Peminjam {{ $loan->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('loans.show', $loan->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Peminjaman" aria-label="Detail Peminjaman {{ $loan->id }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-neutral-500">
                                    <p class="font-semibold text-neutral-800 text-sm">Belum Ada Catatan Peminjaman</p>
                                    <p class="text-xs text-neutral-500 mt-1">Siswa dapat meminjam langsung di Kiosk Layanan Mandiri.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
