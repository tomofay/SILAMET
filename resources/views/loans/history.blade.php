<x-app-layout>
    <x-slot name="heading">Histori Peminjaman Alat</x-slot>
    <x-slot name="title">Histori Peminjaman — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        
        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                        Histori Peminjaman Alat
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $completedTotal }} Riwayat Selesai
                    </span>
                </div>
                <p class="text-xs text-neutral-500 mt-1">
                    Arsip lengkap riwayat pengembalian dan sirkulasi alat praktikum laboratorium SIJA SMKN 1 Cibinong.
                </p>
            </div>

            <!-- Top Right Action Buttons -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('loans.index') }}"
                   class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-2xs inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>Peminjaman Aktif</span>
                </a>
                <a href="{{ route('reports.dashboard', ['type' => 'loans']) }}"
                   class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-2xs inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Ekspor Laporan</span>
                </a>
            </div>
        </div>

        <!-- 3 Executive Metric Cards (Orbix Studio Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-neutral-200 p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Total Pengembalian</div>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-xl font-extrabold text-neutral-900 font-heading">{{ $completedTotal }}</span>
                        <span class="text-xs text-neutral-400">peminjaman tuntas</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-neutral-200 p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kondisi Bagus</div>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-xl font-extrabold text-emerald-600 font-heading">{{ $goodConditionTotal }}</span>
                        <span class="text-xs text-neutral-400">tanpa kendala</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-neutral-200 p-4 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Rusak / Kendala</div>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-xl font-extrabold text-rose-600 font-heading">{{ $damagedConditionTotal }}</span>
                        <span class="text-xs text-neutral-400">perlu tindakan teknisi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Capsule Bar -->
        <div class="bg-white rounded-2xl border border-neutral-200 p-4 shadow-sm">
            <form method="GET" action="{{ route('loans.history') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <!-- Condition Filter Pills -->
                <div class="inline-flex items-center p-1 rounded-full bg-neutral-100 border border-neutral-200/80 gap-1 flex-wrap">
                    <a href="{{ route('loans.history', array_filter(['q' => $search, 'start_date' => $startDate, 'end_date' => $endDate])) }}"
                       class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ $conditionFilter === '' ? 'bg-neutral-900 text-white shadow-2xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                        Semua Kondisi
                    </a>
                    <a href="{{ route('loans.history', array_filter(['condition' => 'bagus', 'q' => $search, 'start_date' => $startDate, 'end_date' => $endDate])) }}"
                       class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ $conditionFilter === 'bagus' ? 'bg-neutral-900 text-white shadow-2xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                        Kondisi Bagus
                    </a>
                    <a href="{{ route('loans.history', array_filter(['condition' => 'rusak', 'q' => $search, 'start_date' => $startDate, 'end_date' => $endDate])) }}"
                       class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ $conditionFilter === 'rusak' ? 'bg-neutral-900 text-white shadow-2xs' : 'text-neutral-600 hover:text-neutral-900' }}">
                        Rusak / Kendala
                    </a>
                </div>

                <!-- Search & Date Filter Inputs -->
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    @if(filled($conditionFilter))
                        <input type="hidden" name="condition" value="{{ $conditionFilter }}">
                    @endif
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="q" value="{{ $search }}"
                               placeholder="Cari siswa, NIS, atau barang..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary bg-neutral-50/50 focus:bg-white transition-colors" />
                    </div>

                    <div class="flex items-center gap-1.5 text-xs text-neutral-500">
                        <input type="date" name="start_date" value="{{ $startDate }}" title="Tanggal Mulai" class="py-1.5 px-2.5 text-xs font-mono rounded-xl border-neutral-200 focus:border-primary focus:ring-primary bg-neutral-50/50" />
                        <span class="text-neutral-400">—</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" title="Tanggal Akhir" class="py-1.5 px-2.5 text-xs font-mono rounded-xl border-neutral-200 focus:border-primary focus:ring-primary bg-neutral-50/50" />
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-2xs">
                            Filter
                        </button>
                    </div>

                    @if(filled($search) || filled($conditionFilter) || filled($startDate) || filled($endDate))
                        <a href="{{ route('loans.history') }}" class="px-3 py-1.5 rounded-xl border border-neutral-200 text-xs font-semibold text-neutral-500 hover:text-neutral-800 bg-white transition-colors whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
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
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Waktu Kembali</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kondisi Balik</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($loans as $loan)
                            <tr class="transition-colors hover:bg-neutral-50/70">
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
                                <td class="px-5 py-4 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '—' }}
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
                                    <span class="text-[11px] font-semibold px-3 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
                                        {{ $loan->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if($loan->capture_image_path)
                                            <a href="{{ route('loans.show', $loan->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-sky-600 hover:text-sky-700 transition-colors shadow-2xs" title="Lihat Foto Verifikasi Kiosk" aria-label="Foto Peminjam {{ $loan->id }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                        @endif
                                        <a href="{{ route('loans.show', $loan->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-2xs" title="Detail Peminjaman" aria-label="Detail Peminjaman {{ $loan->id }}">
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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Riwayat Peminjaman Selesai</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Data peminjaman yang telah dikembalikan oleh siswa akan otomatis diarsipkan di sini.
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
