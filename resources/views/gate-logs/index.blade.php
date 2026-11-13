<x-app-layout>
    <x-slot name="heading">Monitoring Gate Security RFID</x-slot>
    <x-slot name="title">Gate Logs RFID — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Monitoring Gate Security RFID
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Pemantauan antena UHF RFID HW-VX6330, snapshot kamera pelanggar, dan status relay sirine 12V.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-neutral-200 font-mono text-[11px] shadow-sm text-neutral-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    HW-VX6330 [COM2]
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-neutral-200 font-mono text-[11px] shadow-sm text-neutral-700">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Relay [COM4]
                </span>
            </div>
        </div>

        <!-- Header Overview 4 Metric Cards (Dribbble Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Total Deteksi Pintu</span>
                    <div id="stat-total" class="text-2xl font-extrabold text-neutral-900 mt-1 font-heading tabular-nums">
                        {{ number_format($stats['total'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Log pemindaian</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-700 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Akses Sah (Allowed)</span>
                    <div id="stat-allowed" class="text-2xl font-extrabold text-emerald-700 mt-1 font-heading tabular-nums">
                        {{ number_format($stats['allowed'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-emerald-600 mt-0.5 block">Status dipinjam resmi</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 text-emerald-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Pelanggaran (Denied)</span>
                    <div id="stat-denied" class="text-2xl font-extrabold text-rose-700 mt-1 font-heading tabular-nums">
                        {{ number_format($stats['denied'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-rose-600 mt-0.5 block">Sirine terpicu</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center shrink-0 text-rose-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <div id="stat-today-card" class="bg-white rounded-2xl p-5 border {{ ($stats['today_denied'] ?? 0) > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-neutral-200' }} shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Sirine Nyala Hari Ini</span>
                    <div class="text-2xl font-extrabold text-rose-700 mt-1 font-heading tabular-nums flex items-center gap-2">
                        <span id="stat-today-denied">{{ number_format($stats['today_denied'] ?? 0) }}</span>
                        <span id="stat-today-badge" class="{{ ($stats['today_denied'] ?? 0) > 0 ? '' : 'hidden' }} text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200 uppercase">AKTIF</span>
                    </div>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Deteksi 24 jam terakhir</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center shrink-0 text-rose-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>

        </div>

        <!-- Filter Bar & Live Table Container (Rounded-2xl ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">

            <!-- Controls / Filter Tabs -->
            <div class="p-5 border-b border-neutral-200 flex flex-col xl:flex-row xl:items-center justify-between gap-3 bg-neutral-50/70">
                <div class="flex items-center gap-2 flex-wrap">
                    @php $currentStatus = request('status'); @endphp
                    <a href="{{ route('gate-logs.index') }}"
                        class="px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 {{ !$currentStatus ? 'bg-neutral-900 text-white border-neutral-900 shadow-sm' : 'bg-white text-neutral-600 hover:bg-neutral-50 border-neutral-200' }}">
                        Semua Aktivitas
                    </a>
                    <a href="{{ route('gate-logs.index', ['status' => 'denied']) }}"
                        class="px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 inline-flex items-center gap-1.5 {{ $currentStatus === 'denied' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white text-rose-700 hover:bg-rose-50 border-rose-200' }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Hanya Pelanggaran (Denied)</span>
                    </a>
                    <a href="{{ route('gate-logs.index', ['status' => 'allowed']) }}"
                        class="px-4 py-2 rounded-full text-xs font-semibold border transition-all duration-150 inline-flex items-center gap-1.5 {{ $currentStatus === 'allowed' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-emerald-700 hover:bg-emerald-50 border-emerald-200' }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Akses Sah (Allowed)</span>
                    </a>
                </div>

                <div class="flex items-center gap-2 text-xs text-neutral-600 flex-wrap">
                    <button type="button" id="toggle-live-poll"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono text-[11px] font-semibold transition-colors shadow-sm"
                        title="Aktifkan/jeda pembaruan otomatis tiap 3 detik">
                        <span id="live-poll-dot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span id="live-poll-label">LIVE 3s</span>
                    </button>

                    <button type="button" id="toggle-sound-alert"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white text-neutral-600 hover:bg-neutral-50 border border-neutral-200 font-mono text-[11px] font-semibold transition-colors shadow-sm"
                        title="Aktifkan/nonaktifkan bunyi notifikasi saat insiden denied baru masuk">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                        <span id="sound-alert-label">Suara: OFF</span>
                    </button>

                    <span id="live-last-sync" class="font-mono text-[11px] text-neutral-400 px-1">
                        Sync: {{ now()->format('H:i:s') }}
                    </span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Waktu Pemindaian</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">EPC Tag Hardware</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Identifikasi Barang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Status Gerbang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Snapshot Kamera</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="gate-logs-tbody" class="divide-y divide-neutral-100">
                        @include('gate-logs.partials._rows', ['logs' => $logs])
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Live Violation Toast Alert (Muncul otomatis saat polling mendeteksi insiden denied baru) -->
    <div id="gate-alert-toast" class="fixed top-4 right-4 z-50 hidden max-w-sm w-full bg-white border border-rose-300 rounded-2xl p-4 shadow-xl">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-rose-700 uppercase tracking-wider font-heading">Pelanggaran Gate Baru</div>
                <div id="gate-toast-item" class="text-xs font-semibold text-neutral-900 mt-1 truncate"></div>
                <div class="text-[11px] font-mono text-neutral-500 mt-0.5">
                    EPC: <span id="gate-toast-epc"></span> · <span id="gate-toast-time"></span>
                </div>
                <div class="mt-2.5 flex items-center gap-2">
                    <button type="button" id="gate-toast-view-photo" class="hidden px-3 py-1 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-semibold transition-colors shadow-sm">
                        Lihat Foto
                    </button>
                    <button type="button" id="gate-toast-close" class="px-3 py-1 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-[11px] font-semibold transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Foto Pelanggar Solid Border (Rounded-2xl Card) -->
    <div id="photo-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-neutral-950/75" id="photo-modal-backdrop"></div>
        <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl max-w-lg w-full p-6 text-white overflow-hidden shadow-2xl">

            <div class="flex items-center justify-between pb-3.5 border-b border-neutral-800">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-rose-500/15 text-rose-400 border border-rose-500/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-sm font-heading">Foto Snapshot Gerbang</h3>
                        <p class="text-[11px] text-neutral-400">Tangkapan otomatis saat sirine 12V terpicu</p>
                    </div>
                </div>
                <button id="photo-modal-close" class="p-1.5 text-neutral-400 hover:text-white rounded-lg hover:bg-neutral-800 transition-colors" aria-label="Tutup modal">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Meta Info Badge -->
            <div class="my-4 p-3 rounded-xl bg-neutral-950 border border-neutral-800 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                <div class="min-w-0">
                    <div class="text-[10px] text-neutral-500 uppercase tracking-wider font-semibold">Barang</div>
                    <div id="photo-modal-item" class="font-semibold text-white mt-0.5 truncate">—</div>
                </div>
                <div>
                    <div class="text-[10px] text-neutral-500 uppercase tracking-wider font-semibold">EPC Tag</div>
                    <div id="photo-modal-epoch" class="font-mono font-bold text-sky-400 mt-0.5"></div>
                </div>
                <div class="sm:text-right">
                    <div class="text-[10px] text-neutral-500 uppercase tracking-wider font-semibold">Waktu Kejadian</div>
                    <div id="photo-modal-time" class="font-mono text-neutral-200 font-semibold mt-0.5"></div>
                </div>
            </div>

            <!-- Image View -->
            <div class="relative rounded-xl overflow-hidden border border-neutral-800 bg-neutral-950 aspect-[4/3] flex items-center justify-center">
                <img id="photo-modal-img" src="" alt="Foto Pelanggar" class="w-full h-full object-contain">
                <div id="photo-modal-error" class="hidden px-6 text-center text-xs text-neutral-400 font-mono">
                    File foto snapshot tidak ditemukan di penyimpanan server.
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button id="photo-modal-dismiss" type="button" class="px-5 py-2 bg-neutral-800 hover:bg-neutral-700 text-white font-semibold text-xs rounded-full border border-neutral-700 transition-colors shadow-sm">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.getElementById('gate-logs-tbody');
            const modal = document.getElementById('photo-modal');
            const img = document.getElementById('photo-modal-img');
            const errorBox = document.getElementById('photo-modal-error');
            const itemLabel = document.getElementById('photo-modal-item');
            const epochLabel = document.getElementById('photo-modal-epoch');
            const timeLabel = document.getElementById('photo-modal-time');
            const closeBtn = document.getElementById('photo-modal-close');
            const dismissBtn = document.getElementById('photo-modal-dismiss');
            const backdrop = document.getElementById('photo-modal-backdrop');

            const feedUrl = @json(route('gate-logs.feed'));
            const activeStatus = @json(request('status'));
            const currentPage = @json((int) $logs->currentPage());

            let latestLogId = @json((int) ($latestLogId ?? 0));
            let latestDeniedId = @json((int) ($latestDeniedId ?? 0));
            let liveEnabled = currentPage <= 1;
            let soundEnabled = false;
            let pollTimer = null;
            let toastTimer = null;
            let audioCtx = null;
            let isFetching = false;

            const statTotal = document.getElementById('stat-total');
            const statAllowed = document.getElementById('stat-allowed');
            const statDenied = document.getElementById('stat-denied');
            const statTodayDenied = document.getElementById('stat-today-denied');
            const statTodayCard = document.getElementById('stat-today-card');
            const statTodayBadge = document.getElementById('stat-today-badge');
            const toggleLiveBtn = document.getElementById('toggle-live-poll');
            const liveDot = document.getElementById('live-poll-dot');
            const liveLabel = document.getElementById('live-poll-label');
            const toggleSoundBtn = document.getElementById('toggle-sound-alert');
            const soundLabel = document.getElementById('sound-alert-label');
            const syncLabel = document.getElementById('live-last-sync');

            const toast = document.getElementById('gate-alert-toast');
            const toastItem = document.getElementById('gate-toast-item');
            const toastEpc = document.getElementById('gate-toast-epc');
            const toastTime = document.getElementById('gate-toast-time');
            const toastPhotoBtn = document.getElementById('gate-toast-view-photo');
            const toastCloseBtn = document.getElementById('gate-toast-close');

            if (img) {
                img.addEventListener('error', function () {
                    if (!img.getAttribute('src')) return;
                    img.classList.add('hidden');
                    if (errorBox) errorBox.classList.remove('hidden');
                });
            }

            function openModal(src, epoch, item, time) {
                if (errorBox) errorBox.classList.add('hidden');
                img.classList.remove('hidden');
                img.src = src;
                if (itemLabel) itemLabel.textContent = item || 'Tag Asing / Tidak Terdaftar';
                epochLabel.textContent = epoch || '-';
                timeLabel.textContent = time || '-';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                img.removeAttribute('src');
                if (errorBox) errorBox.classList.add('hidden');
                img.classList.remove('hidden');
            }

            // Delegasi klik supaya baris baru hasil auto-refresh langsung mendukung pratinjau foto.
            if (tbody) {
                tbody.addEventListener('click', function (event) {
                    const btn = event.target.closest('.open-photo-modal');
                    if (!btn) return;
                    openModal(btn.dataset.photo, btn.dataset.epoch, btn.dataset.item, btn.dataset.time);
                });
            }

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (dismissBtn) dismissBtn.addEventListener('click', closeModal);
            if (backdrop) backdrop.addEventListener('click', closeModal);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            function playAlertBeep() {
                if (!soundEnabled) return;
                try {
                    const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextCtor) return;
                    if (!audioCtx) audioCtx = new AudioContextCtor();
                    if (audioCtx.state === 'suspended') audioCtx.resume();

                    const now = audioCtx.currentTime;
                    [880, 660, 880].forEach(function (freq, idx) {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'square';
                        osc.frequency.setValueAtTime(freq, now + idx * 0.14);
                        gain.gain.setValueAtTime(0.05, now + idx * 0.14);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.14 + 0.11);
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start(now + idx * 0.14);
                        osc.stop(now + idx * 0.14 + 0.12);
                    });
                } catch (e) {
                    // Abaikan jika browser memblokir autoplay audio
                }
            }

            function showViolationToast(alertData) {
                if (!toast || !alertData) return;
                toastItem.textContent = alertData.item_name || 'Tag Asing / Tidak Terdaftar';
                toastEpc.textContent = alertData.epc || '-';
                toastTime.textContent = alertData.time || '-';

                if (alertData.photo_url && toastPhotoBtn) {
                    toastPhotoBtn.classList.remove('hidden');
                    toastPhotoBtn.onclick = function () {
                        openModal(alertData.photo_url, alertData.epc, alertData.item_name, alertData.time);
                        toast.classList.add('hidden');
                    };
                } else if (toastPhotoBtn) {
                    toastPhotoBtn.classList.add('hidden');
                }

                toast.classList.remove('hidden');
                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    toast.classList.add('hidden');
                }, 8000);
            }

            if (toastCloseBtn) {
                toastCloseBtn.addEventListener('click', function () {
                    toast.classList.add('hidden');
                });
            }

            function updateLiveUi() {
                if (!toggleLiveBtn) return;
                if (liveEnabled) {
                    toggleLiveBtn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono text-[11px] font-semibold transition-colors';
                    liveDot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
                    liveLabel.textContent = 'LIVE 3s';
                } else {
                    toggleLiveBtn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-neutral-100 text-neutral-600 border border-neutral-200 font-mono text-[11px] font-semibold transition-colors';
                    liveDot.className = 'w-2 h-2 rounded-full bg-neutral-400';
                    liveLabel.textContent = currentPage > 1 ? 'JEDA (Hal >1)' : 'LIVE JEDA';
                }
            }

            function formatNum(num) {
                return Number(num || 0).toLocaleString('en-US');
            }

            async function pollFeed() {
                if (!liveEnabled || isFetching || document.visibilityState !== 'visible') {
                    return;
                }

                isFetching = true;
                try {
                    const params = new URLSearchParams({
                        after_id: String(latestLogId),
                        after_denied_id: String(latestDeniedId),
                    });
                    if (activeStatus) params.set('status', activeStatus);

                    const res = await fetch(`${feedUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data.success) return;

                    if (data.stats) {
                        if (statTotal) statTotal.textContent = formatNum(data.stats.total);
                        if (statAllowed) statAllowed.textContent = formatNum(data.stats.allowed);
                        if (statDenied) statDenied.textContent = formatNum(data.stats.denied);
                        if (statTodayDenied) statTodayDenied.textContent = formatNum(data.stats.today_denied);
                        if (statTodayCard && statTodayBadge) {
                            if (Number(data.stats.today_denied || 0) > 0) {
                                statTodayBadge.classList.remove('hidden');
                                statTodayCard.classList.remove('border-neutral-200');
                                statTodayCard.classList.add('border-rose-300', 'bg-rose-50/20');
                            } else {
                                statTodayBadge.classList.add('hidden');
                                statTodayCard.classList.remove('border-rose-300', 'bg-rose-50/20');
                                statTodayCard.classList.add('border-neutral-200');
                            }
                        }
                    }

                    if (data.changed && data.tbody_html && tbody) {
                        tbody.innerHTML = data.tbody_html;
                    }

                    if (data.latest_denied_id > latestDeniedId && data.latest_denied_alert) {
                        showViolationToast(data.latest_denied_alert);
                        playAlertBeep();
                    }

                    if (typeof data.latest_id === 'number') latestLogId = data.latest_id;
                    if (typeof data.latest_denied_id === 'number') latestDeniedId = data.latest_denied_id;

                    if (syncLabel) {
                        const now = new Date();
                        syncLabel.textContent = 'Sync: ' + now.toTimeString().slice(0, 8);
                    }
                } catch (e) {
                    // Lewati kegagalan jaringan sementara; siklus berikutnya akan mencoba lagi
                } finally {
                    isFetching = false;
                }
            }

            if (toggleLiveBtn) {
                toggleLiveBtn.addEventListener('click', function () {
                    liveEnabled = !liveEnabled;
                    updateLiveUi();
                    if (liveEnabled) pollFeed();
                });
            }

            if (toggleSoundBtn) {
                toggleSoundBtn.addEventListener('click', function () {
                    soundEnabled = !soundEnabled;
                    if (soundEnabled) {
                        toggleSoundBtn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200 font-mono text-[11px] font-semibold transition-colors';
                        soundLabel.textContent = 'Suara: ON';
                        try {
                            const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
                            if (AudioContextCtor && !audioCtx) audioCtx = new AudioContextCtor();
                            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
                        } catch (e) {}
                    } else {
                        toggleSoundBtn.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white text-neutral-600 hover:bg-neutral-100 border border-neutral-200 font-mono text-[11px] font-semibold transition-colors';
                        soundLabel.textContent = 'Suara: OFF';
                    }
                });
            }

            updateLiveUi();
            pollTimer = setInterval(pollFeed, 3000);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible' && liveEnabled) {
                    pollFeed();
                }
            });
            window.addEventListener('beforeunload', function () {
                if (pollTimer) clearInterval(pollTimer);
            });
        });
    </script>
    @endpush
</x-app-layout>
