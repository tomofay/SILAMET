<x-app-layout>
    <x-slot name="heading">Daftar Peminjaman Alat</x-slot>
    <x-slot name="title">Peminjaman Alat — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        
        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Sirkulasi Peminjaman Alat
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Pemantauan sirkulasi perangkat praktikum siswa via Kiosk mandiri dan pencatatan staf teknisi.
                </p>
            </div>

            <!-- Top Right Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('loans.create') }}"
                   class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Catat Peminjaman Baru</span>
                </a>
            </div>
        </div>

        <!-- Table Card (Rounded-2xl Container ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Siswa Peminjam</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Barang Dipinjam</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Waktu Pinjam</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Batas Waktu</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kondisi Balik</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($loans as $loan)
                            @php
                                $isOverdue = $loan->status->value === 'aktif' && $loan->due_at?->isPast();
                            @endphp
                            <tr class="transition-colors hover:bg-neutral-50/70 {{ $isOverdue ? 'bg-rose-50/30' : '' }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 font-bold text-neutral-700 text-xs font-heading">
                                            {{ strtoupper(substr($loan->user->name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-neutral-900 font-bold text-xs font-heading">{{ $loan->user->name ?? '-' }}</div>
                                            <div class="text-[11px] text-neutral-500 font-mono mt-0.5">NIS: {{ $loan->user->nis_nip ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-neutral-900 font-bold text-xs font-heading">{{ $loan->item->name ?? '-' }}</div>
                                    <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $loan->item->item_code ?? '—' }}</div>
                                </td>
                                <td class="px-5 py-4 text-xs text-neutral-700 font-medium whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-neutral-100 border border-neutral-200 text-neutral-700 text-[11px] font-semibold">
                                        {{ $loan->usage_duration?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ optional($loan->borrowed_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($isOverdue)
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[11px] font-semibold text-rose-700 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200">
                                            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>TERLAMBAT ({{ optional($loan->due_at)->format('d/m H:i') }})</span>
                                        </span>
                                    @else
                                        <span class="font-mono text-xs text-neutral-600">{{ optional($loan->due_at)->format('d/m/Y H:i') ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($loan->condition_in)
                                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $loan->condition_in === 'bagus' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                            {{ $loan->condition_in === 'bagus' ? 'Kondisi Bagus' : 'Rusak/Kendala' }}
                                        </span>
                                    @else
                                        <span class="text-neutral-400 text-xs font-mono">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $lbadge = match($loan->status->value) {
                                            'aktif' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'menunggu_approval_kembali' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'selesai' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-3 py-0.5 rounded-full border {{ $lbadge }}">
                                        {{ $loan->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if($loan->capture_image_path)
                                            <a href="{{ route('loans.show', $loan->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-sky-600 hover:text-sky-700 transition-colors shadow-sm" title="Lihat Foto Verifikasi Kiosk" aria-label="Foto Peminjam {{ $loan->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('loans.show', $loan->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Detail Peminjaman" aria-label="Detail Peminjaman {{ $loan->id }}">
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
                                <td colspan="8" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Catatan Peminjaman</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Siswa dapat meminjam langsung di Kiosk Layanan Mandiri atau dicatat manual.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>