<x-app-layout>
    <div class="space-y-4">
        <p class="text-neutral-500 text-sm">Unduh laporan inventaris, peminjaman, log pintu, dan biaya perbaikan.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $reports = [
                    ['type' => 'inventaris', 'title' => 'Laporan Inventaris', 'desc' => 'Rekapitulasi seluruh aset & bahan praktikum', 'icon' => 'box'],
                    ['type' => 'loans', 'title' => 'Riwayat Peminjaman', 'desc' => 'Data peminjaman & pengembalian alat', 'icon' => 'refresh'],
                    ['type' => 'gate_logs', 'title' => 'Log Keamanan Pintu', 'desc' => 'Riwayat pembacaan RFID di gate', 'icon' => 'shield'],
                    ['type' => 'maintenance', 'title' => 'Biaya Perbaikan (M&R)', 'desc' => 'Tindakan servis, vendor, dan biaya suku cadang', 'icon' => 'tool'],
                ];
            @endphp

            @foreach($reports as $report)
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5 flex flex-col">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            @if($report['icon'] === 'box')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            @elseif($report['icon'] === 'refresh')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            @elseif($report['icon'] === 'shield')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-semibold text-neutral-800">{{ $report['title'] }}</h3>
                            <p class="text-sm text-neutral-500 mt-0.5">{{ $report['desc'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 pt-3 border-t border-neutral-100">
                        <a href="{{ route('reports.pdf', $report['type']) }}"
                           class="inline-flex items-center px-3 py-1.5 bg-danger text-white text-xs font-medium rounded-md hover:opacity-90">
                            ⬇ PDF
                        </a>
                        <a href="{{ route('reports.excel', $report['type']) }}"
                           class="inline-flex items-center px-3 py-1.5 bg-success text-white text-xs font-medium rounded-md hover:opacity-90">
                            ⬇ Excel
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
