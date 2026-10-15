<x-app-layout>
    <div class="space-y-4">
        <p class="text-neutral-500 text-sm">Monitoring pembacaan UHF RFID HW-VX6330 di pintu laboratorium</p>

        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">EPC Tag</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Barang Terdeteksi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status Akses</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($logs as $log)
                            <tr class="{{ $log->action_status->value === 'denied' ? 'bg-danger/5' : '' }} hover:bg-neutral-50">
                                <td class="px-5 py-3 text-neutral-600 whitespace-nowrap">{{ optional($log->created_at)->translatedFormat('d M Y, H:i:s') }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-700">{{ $log->epc_tag_scanned }}</td>
                                <td class="px-5 py-3 text-neutral-800">{{ $log->item->name ?? 'Tidak dikenal' }}</td>
                                <td class="px-5 py-3">
                                    @if($log->action_status->value === 'allowed')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-success/10 text-success-dark">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success inline-block"></span>
                                            ALLOWED
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-danger/10 text-danger">
                                            <span class="w-1.5 h-1.5 rounded-full bg-danger inline-block"></span>
                                            DENIED
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if($log->action_status->value === 'denied' && $log->capture_image_path)
                                        <button type="button"
                                            data-photo="{{ asset('storage/' . $log->capture_image_path) }}"
                                            data-epoch="{{ $log->epc_tag_scanned }}"
                                            class="open-photo-modal text-xs text-primary hover:underline font-medium">
                                            Lihat Foto →
                                        </button>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-neutral-400">Belum ada aktivitas di pintu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Foto Pelanggar -->
    <div id="photo-modal" class="fixed inset-0 z-40 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" id="photo-modal-backdrop"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-neutral-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Foto Pelanggar</span>
                </h3>
                <button id="photo-modal-close" class="text-neutral-500 hover:text-neutral-800 text-xl leading-none">&times;</button>
            </div>
            <p id="photo-modal-epoch" class="text-xs text-neutral-500 font-mono mb-3"></p>
            <img id="photo-modal-img" src="" alt="Foto pelanggar" class="w-full rounded-md border border-neutral-200">
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('photo-modal');
            const img = document.getElementById('photo-modal-img');
            const epochLabel = document.getElementById('photo-modal-epoch');
            const closeBtn = document.getElementById('photo-modal-close');
            const backdrop = document.getElementById('photo-modal-backdrop');

            function openModal(src, epoch) {
                img.src = src;
                epochLabel.textContent = 'EPC: ' + epoch;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                img.src = '';
            }

            document.querySelectorAll('.open-photo-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModal(btn.dataset.photo, btn.dataset.epoch);
                });
            });

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (backdrop) backdrop.addEventListener('click', closeModal);
        });
    </script>
    @endpush
</x-app-layout>
