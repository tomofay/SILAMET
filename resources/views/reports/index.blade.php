<x-app-layout>
    <div class="space-y-4">
        <p class="text-neutral-500 text-sm">Unduh laporan inventaris, peminjaman, log pintu, dan biaya perbaikan.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $reports = [
                    ['type' => 'inventaris', 'title' => 'Laporan Inventaris', 'desc' => 'Rekapitulasi seluruh aset & bahan praktikum', 'icon' => '📦'],
                    ['type' => 'loans', 'title' => 'Riwayat Peminjaman', 'desc' => 'Data peminjaman & pengembalian alat', 'icon' => '🔁'],
                    ['type' => 'gate_logs', 'title' => 'Log Keamanan Pintu', 'desc' => 'Riwayat pembacaan RFID di gate', 'icon' => '🚪'],
                    ['type' => 'maintenance', 'title' => 'Biaya Perbaikan (M&R)', 'desc' => 'Tindakan servis, vendor, dan biaya suku cadang', 'icon' => '🔧'],
                ];
            @endphp

            @foreach($reports as $report)
                <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-5 flex flex-col">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-xl shrink-0">{{ $report['icon'] }}</div>
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
