@php
    $returnTarget = $returnTo ?? 'index';
    $oldModalMode = old('_modal_mode');
    $oldModalTicketId = old('_modal_ticket_id');
    $serverMaxUploadBytes = min(4 * 1024 * 1024, max(1024, \Illuminate\Http\UploadedFile::getMaxFilesize()));
    $serverMaxMbLabel = number_format($serverMaxUploadBytes / 1048576, 1, ',', '.');
@endphp

<!-- Modal 1: Mulai Perbaikan (Internal Sekolah / Vendor Luar) -->
<div id="start-repair-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="start-modal-title">
    <div id="start-modal-backdrop" class="absolute inset-0 bg-neutral-950/60"></div>

    <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white border border-neutral-200 rounded-xl">
        <div class="sticky top-0 z-10 bg-white px-5 py-4 border-b border-neutral-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 id="start-modal-title" class="text-sm font-bold text-neutral-900 tracking-tight font-heading">Mulai Perbaikan Aset</h3>
                    <p class="text-[11px] text-neutral-500">Pilih lokasi perbaikan di sekolah atau kirim ke vendor luar.</p>
                </div>
            </div>
            <button type="button" id="start-modal-close" class="p-1.5 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 rounded-lg transition-colors" aria-label="Tutup modal mulai perbaikan">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="start-repair-form" method="POST" action="{{ $oldModalMode === 'start' && $oldModalTicketId ? route('maintenance.start', $oldModalTicketId) : '' }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="return_to" value="{{ $returnTarget }}">
            <input type="hidden" name="_modal_mode" value="start">
            <input type="hidden" name="_modal_ticket_id" id="start-modal-ticket-id-input" value="{{ $oldModalMode === 'start' ? $oldModalTicketId : '' }}">
            <input type="hidden" name="_modal_item_name" id="start-modal-item-name-input" value="{{ $oldModalMode === 'start' ? old('_modal_item_name') : '' }}">
            <input type="hidden" name="_modal_item_code" id="start-modal-item-code-input" value="{{ $oldModalMode === 'start' ? old('_modal_item_code') : '' }}">
            <input type="hidden" name="_modal_issue" id="start-modal-issue-input" value="{{ $oldModalMode === 'start' ? old('_modal_issue') : '' }}">

            @if($errors->any() && $oldModalMode === 'start')
                <div id="start-modal-server-errors" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                    <div class="font-bold">Gagal memulai perbaikan:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Ringkasan Aset & Kendala -->
            <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200 space-y-1">
                <div class="flex items-center justify-between gap-2">
                    <span id="start-modal-item" class="text-xs font-bold text-neutral-900 truncate">{{ $oldModalMode === 'start' ? old('_modal_item_name', '-') : '-' }}</span>
                    <span id="start-modal-code" class="text-[11px] font-mono px-2 py-0.5 rounded bg-white border border-neutral-200 text-neutral-600 shrink-0">{{ $oldModalMode === 'start' ? old('_modal_item_code', '—') : '—' }}</span>
                </div>
                <p id="start-modal-issue" class="text-xs text-neutral-600 line-clamp-2">{{ $oldModalMode === 'start' ? old('_modal_issue', '-') : '-' }}</p>
            </div>

            <!-- Pilihan Metode Penanganan -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-2">Lokasi Penanganan Perbaikan <span class="text-rose-600">*</span></label>
                @php
                    $startServiceOld = $oldModalMode === 'start' ? old('service_type', 'internal') : 'internal';
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <label class="cursor-pointer">
                        <input type="radio" name="service_type" value="internal" class="peer sr-only start-service-radio" {{ $startServiceOld === 'internal' ? 'checked' : '' }}>
                        <div class="p-3 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-primary peer-checked:bg-primary-50/50 transition-colors h-full">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="text-xs font-bold text-neutral-900">Di Sekolah (Internal)</span>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-1">Ditangani langsung oleh teknisi lab SIJA.</p>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="service_type" value="vendor_luar" class="peer sr-only start-service-radio" {{ $startServiceOld === 'vendor_luar' ? 'checked' : '' }}>
                        <div class="p-3 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-amber-500 peer-checked:bg-amber-50/60 transition-colors h-full">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                                <span class="text-xs font-bold text-neutral-900">Kirim ke Vendor Luar</span>
                            </div>
                            <p class="text-[11px] text-neutral-500 mt-1">Diserahkan ke bengkel / servis eksternal.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Detail Vendor (Muncul jika Vendor Luar dipilih) -->
            <div id="start-vendor-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-lg bg-amber-50/60 border border-amber-200 {{ $startServiceOld === 'vendor_luar' ? '' : 'hidden' }}">
                <div>
                    <label for="start-vendor-name" class="block text-xs font-semibold text-neutral-800">Nama Vendor / Bengkel <span class="text-rose-600">*</span></label>
                    <input type="text" id="start-vendor-name" name="vendor_name"
                           value="{{ $oldModalMode === 'start' ? old('vendor_name') : '' }}"
                           placeholder="Misal: Service Center MikroTik Depok"
                           class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="start-estimated-finish" class="block text-xs font-semibold text-neutral-800">Estimasi Selesai <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                    <input type="date" id="start-estimated-finish" name="estimated_finish_at"
                           value="{{ $oldModalMode === 'start' ? old('estimated_finish_at') : '' }}"
                           class="mt-1 w-full rounded-lg border-neutral-300 text-xs font-mono focus:border-primary focus:ring-primary">
                </div>
            </div>

            <!-- Keterangan Tambahan -->
            <div>
                <label for="start-action-taken" class="block text-xs font-semibold text-neutral-700">Keterangan Tambahan / Diagnosa Awal <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                <textarea id="start-action-taken" name="action_taken" rows="3"
                          placeholder="Catatan diagnosa awal, gejala komponen, atau nomor tanda terima servis..."
                          class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">{{ $oldModalMode === 'start' ? old('action_taken') : '' }}</textarea>
            </div>

            <div class="pt-3 border-t border-neutral-200 flex items-center justify-end gap-2">
                <button type="button" id="start-modal-cancel" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-200 rounded-lg transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg border border-amber-700 transition-colors">
                    Mulai Perbaikan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Selesai & Laporan Hasil Perbaikan -->
<div id="complete-repair-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="complete-modal-title">
    <div id="complete-modal-backdrop" class="absolute inset-0 bg-neutral-950/60"></div>

    <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white border border-neutral-200 rounded-xl">
        <div class="sticky top-0 z-10 bg-white px-5 py-4 border-b border-neutral-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 id="complete-modal-title" class="text-sm font-bold text-neutral-900 tracking-tight font-heading">Selesai &amp; Laporan Hasil Perbaikan</h3>
                    <p id="complete-modal-subtitle" class="text-[11px] text-neutral-500">Pilih apakah barang layak pakai kembali atau rusak permanen.</p>
                </div>
            </div>
            <button type="button" id="complete-modal-close" class="p-1.5 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 rounded-lg transition-colors" aria-label="Tutup modal selesai perbaikan">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="complete-repair-form" method="POST" action="{{ $oldModalMode === 'complete' && $oldModalTicketId ? route('maintenance.complete', $oldModalTicketId) : '' }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="return_to" value="{{ $returnTarget }}">
            <input type="hidden" name="_modal_mode" value="complete">
            <input type="hidden" name="_modal_ticket_id" id="complete-modal-ticket-id-input" value="{{ $oldModalMode === 'complete' ? $oldModalTicketId : '' }}">
            <input type="hidden" name="_modal_item_name" id="complete-modal-item-name-input" value="{{ $oldModalMode === 'complete' ? old('_modal_item_name') : '' }}">
            <input type="hidden" name="_modal_item_code" id="complete-modal-item-code-input" value="{{ $oldModalMode === 'complete' ? old('_modal_item_code') : '' }}">
            <input type="hidden" name="_modal_issue" id="complete-modal-issue-input" value="{{ $oldModalMode === 'complete' ? old('_modal_issue') : '' }}">
            <input type="hidden" name="_modal_item_stock" id="complete-modal-item-stock-input" value="{{ $oldModalMode === 'complete' ? old('_modal_item_stock', 1) : 1 }}">
            <input type="hidden" name="_modal_default_broken_qty" id="complete-modal-default-broken-qty-input" value="{{ $oldModalMode === 'complete' ? old('_modal_default_broken_qty', 1) : 1 }}">

            @if($errors->any() && $oldModalMode === 'complete')
                <div id="complete-modal-server-errors" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                    <div class="font-bold">Gagal menyimpan laporan selesai:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Ringkasan Aset & Kendala -->
            <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200 space-y-1">
                <div class="flex items-center justify-between gap-2">
                    <span id="complete-modal-item" class="text-xs font-bold text-neutral-900 truncate">{{ $oldModalMode === 'complete' ? old('_modal_item_name', '-') : '-' }}</span>
                    <span id="complete-modal-code" class="text-[11px] font-mono px-2 py-0.5 rounded bg-white border border-neutral-200 text-neutral-600 shrink-0">{{ $oldModalMode === 'complete' ? old('_modal_item_code', '—') : '—' }}</span>
                </div>
                <p id="complete-modal-issue" class="text-xs text-neutral-600 line-clamp-2">{{ $oldModalMode === 'complete' ? old('_modal_issue', '-') : '-' }}</p>
            </div>

            <!-- Filter Hasil Kondisi Akhir: Bener vs Rusak Permanen -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-2">Hasil Kondisi Akhir Barang <span class="text-rose-600">*</span></label>
                @php
                    $completeOutcomeOld = $oldModalMode === 'complete' ? old('outcome', 'repaired') : 'repaired';
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <label class="cursor-pointer">
                        <input type="radio" name="outcome" value="repaired" class="peer sr-only complete-outcome-radio" {{ $completeOutcomeOld !== 'broken' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 transition-colors h-full">
                            <div class="text-xs font-bold text-neutral-900">Bener / Layak Pakai</div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Alat normal &amp; status kembali TERSEDIA.</p>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="outcome" value="broken" class="peer sr-only complete-outcome-radio" {{ $completeOutcomeOld === 'broken' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-rose-500 peer-checked:bg-rose-50/70 transition-colors h-full">
                            <div class="text-xs font-bold text-neutral-900">Rusak Permanen</div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Masuk daftar Barang Rusak &amp; kurangi Master Aset.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Jumlah Unit Rusak Permanen (otomatis tampil bila stok > 1 unit) -->
            <div id="complete-broken-qty-field" class="{{ $completeOutcomeOld === 'broken' && (int) old('_modal_item_stock', 1) > 1 ? '' : 'hidden' }} p-3 rounded-lg bg-rose-50 border border-rose-200 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <label for="complete-broken-qty" class="block text-xs font-semibold text-rose-900">Jumlah Unit Rusak Permanen <span class="text-rose-600">*</span></label>
                    <span class="text-[11px] font-mono text-rose-800">Stok saat ini: <span id="complete-max-stock-label" class="font-bold">{{ (int) old('_modal_item_stock', 1) }}</span> unit</span>
                </div>
                <input type="number" id="complete-broken-qty" name="broken_quantity" min="1"
                       max="{{ max(1, (int) old('_modal_item_stock', 1)) }}"
                       value="{{ $oldModalMode === 'complete' ? old('broken_quantity', old('_modal_default_broken_qty', 1)) : 1 }}"
                       class="w-full rounded-lg border-rose-300 bg-white text-xs font-mono focus:border-rose-500 focus:ring-rose-500">
                <p class="text-[11px] text-rose-800 leading-snug">Stok di Master Aset akan langsung berkurang sesuai jumlah unit ini (dihapus otomatis bila stok menjadi 0).</p>
            </div>

            <!-- Metode Penanganan -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 mb-2">Lokasi Penanganan Akhir <span class="text-rose-600">*</span></label>
                @php
                    $completeServiceOld = $oldModalMode === 'complete' ? old('service_type', 'internal') : 'internal';
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <label class="cursor-pointer">
                        <input type="radio" name="service_type" value="internal" class="peer sr-only complete-service-radio" {{ $completeServiceOld === 'internal' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-primary peer-checked:bg-primary-50/50 transition-colors">
                            <div class="text-xs font-bold text-neutral-900">Di Sekolah (Internal)</div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Biaya &amp; struk opsional</p>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="service_type" value="vendor_luar" class="peer sr-only complete-service-radio" {{ $completeServiceOld === 'vendor_luar' ? 'checked' : '' }}>
                        <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-amber-500 peer-checked:bg-amber-50/60 transition-colors">
                            <div class="text-xs font-bold text-neutral-900">Vendor Luar</div>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Servis bengkel eksternal</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Nama Vendor (Muncul jika Vendor Luar dipilih) -->
            <div id="complete-vendor-field" class="{{ $completeServiceOld === 'vendor_luar' ? '' : 'hidden' }}">
                <label for="complete-vendor-name" class="block text-xs font-semibold text-neutral-700">Nama Vendor / Bengkel <span class="text-rose-600">*</span></label>
                <input type="text" id="complete-vendor-name" name="vendor_name"
                       value="{{ $oldModalMode === 'complete' ? old('vendor_name') : '' }}"
                       placeholder="Misal: Toko Servis Jaringan Cibinong"
                       class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
            </div>

            <!-- Hasil Perbaikan (Wajib) -->
            <div>
                <label id="complete-action-taken-label" for="complete-action-taken" class="block text-xs font-semibold text-neutral-700">Laporan Tindakan &amp; Hasil Uji Layak <span class="text-rose-600">*</span></label>
                <textarea id="complete-action-taken" name="action_taken" rows="3" required
                          placeholder="Jelaskan komponen yang diperbaiki/disetel dan hasil pengujian fungsi alat..."
                          class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">{{ $oldModalMode === 'complete' ? old('action_taken') : '' }}</textarea>
            </div>

            <!-- Sparepart (Opsional) -->
            <div>
                <label for="complete-spareparts" class="block text-xs font-semibold text-neutral-700">Sparepart yang Diganti <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                <input type="text" id="complete-spareparts" name="spareparts_used"
                       value="{{ $oldModalMode === 'complete' ? old('spareparts_used') : '' }}"
                       placeholder="Misal: Adaptor 12V 2A, Konektor RJ45, Kabel Power (kosongkan jika tidak ada)"
                       class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
            </div>

            <!-- Biaya & Struk (Opsional) -->
            <div class="p-3 rounded-lg bg-neutral-50 border border-neutral-200 space-y-3">
                <p id="complete-cost-helper-default" class="text-[11px] text-neutral-600 leading-snug">
                    Harga perbaikan dan foto/PDF struk bersifat <span class="font-semibold text-neutral-800">opsional</span> — dapat dikosongkan apabila perbaikan dilakukan secara internal di sekolah tanpa pengeluaran biaya.
                </p>
                <div id="complete-cost-helper-existing" class="hidden p-2 rounded-md bg-amber-50 border border-amber-200 text-[11px] text-amber-800 leading-snug">
                    Sudah tercatat biaya sebesar <span id="complete-existing-cost-label" class="font-mono font-bold">Rp 0</span> pada log progres sebelumnya. Isi kolom di bawah hanya jika ada biaya tambahan / pelunasan akhir.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="complete-cost" class="block text-xs font-semibold text-neutral-700">Harga / Biaya Perbaikan <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-mono text-neutral-500 pointer-events-none">Rp</span>
                            <input type="number" id="complete-cost" name="cost" min="0" step="100"
                                   value="{{ $oldModalMode === 'complete' ? old('cost') : '' }}"
                                   placeholder="0"
                                   class="w-full pl-9 rounded-lg border-neutral-300 text-xs font-mono focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div>
                        <label for="complete-receipt" class="block text-xs font-semibold text-neutral-700">Foto / PDF Struk Nota <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                        <input type="file" id="complete-receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                               data-feedback-id="complete-receipt-feedback"
                               class="mt-1 block w-full text-xs text-neutral-600 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-neutral-300 file:text-xs file:font-semibold file:bg-white file:text-neutral-700 hover:file:bg-neutral-50">
                        <p class="text-[10px] text-neutral-400 mt-1">Foto otomatis diperkecil sebelum diunggah; PDF/berkas lain maks. {{ $serverMaxMbLabel }} MB (batas server PHP).</p>
                        <p id="complete-receipt-feedback" class="hidden text-[11px] mt-1 font-medium"></p>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-neutral-200 flex items-center justify-end gap-2">
                <button type="button" id="complete-modal-cancel" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-200 rounded-lg transition-colors">
                    Batal
                </button>
                <button type="submit" id="complete-submit-btn" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-700 transition-colors">
                    Simpan Laporan &amp; Selesaikan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Pratinjau Foto Struk Nota Perbaikan -->
<div id="receipt-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title">
    <div id="receipt-modal-backdrop" class="absolute inset-0 bg-neutral-950/75"></div>

    <div class="relative bg-neutral-900 border border-neutral-800 rounded-xl max-w-lg w-full p-5 text-white overflow-hidden">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-800">
            <div>
                <h3 id="receipt-modal-title" class="text-sm font-bold text-white">Struk / Nota Perbaikan</h3>
                <p id="receipt-modal-meta" class="text-[11px] font-mono text-neutral-400 mt-0.5">—</p>
            </div>
            <button type="button" id="receipt-modal-close" class="p-1.5 text-neutral-400 hover:text-white rounded-lg hover:bg-neutral-800 transition-colors" aria-label="Tutup pratinjau struk">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="my-4 rounded-lg overflow-hidden border border-neutral-800 bg-neutral-950 aspect-[4/3] flex items-center justify-center">
            <img id="receipt-modal-img" src="" alt="Struk Pembayaran Perbaikan" class="w-full h-full object-contain">
            <div id="receipt-modal-error" class="hidden px-6 text-center text-xs text-neutral-400 font-mono">
                File struk tidak dapat ditampilkan atau belum tersedia.
            </div>
        </div>

        <div class="flex items-center justify-between gap-2">
            <a id="receipt-modal-newtab" href="#" target="_blank" class="px-3.5 py-2 bg-neutral-800 hover:bg-neutral-700 text-neutral-200 text-xs font-semibold rounded-lg border border-neutral-700 transition-colors">
                Buka di Tab Baru
            </a>
            <button type="button" id="receipt-modal-dismiss" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const startModal = document.getElementById('start-repair-modal');
    const startForm = document.getElementById('start-repair-form');
    const startItemEl = document.getElementById('start-modal-item');
    const startCodeEl = document.getElementById('start-modal-code');
    const startIssueEl = document.getElementById('start-modal-issue');
    const startVendorFields = document.getElementById('start-vendor-fields');
    const startVendorInput = document.getElementById('start-vendor-name');
    const startEstInput = document.getElementById('start-estimated-finish');
    const startActionInput = document.getElementById('start-action-taken');
    const startTicketIdInput = document.getElementById('start-modal-ticket-id-input');
    const startItemNameInput = document.getElementById('start-modal-item-name-input');
    const startItemCodeInput = document.getElementById('start-modal-item-code-input');
    const startIssueInput = document.getElementById('start-modal-issue-input');

    const completeModal = document.getElementById('complete-repair-modal');
    const completeForm = document.getElementById('complete-repair-form');
    const completeItemEl = document.getElementById('complete-modal-item');
    const completeCodeEl = document.getElementById('complete-modal-code');
    const completeIssueEl = document.getElementById('complete-modal-issue');
    const completeVendorField = document.getElementById('complete-vendor-field');
    const completeVendorInput = document.getElementById('complete-vendor-name');
    const completeActionInput = document.getElementById('complete-action-taken');
    const completeSparepartsInput = document.getElementById('complete-spareparts');
    const completeCostInput = document.getElementById('complete-cost');
    const completeReceiptInput = document.getElementById('complete-receipt');
    const completeCostHelperDefault = document.getElementById('complete-cost-helper-default');
    const completeCostHelperExisting = document.getElementById('complete-cost-helper-existing');
    const completeExistingCostLabel = document.getElementById('complete-existing-cost-label');
    const completeTicketIdInput = document.getElementById('complete-modal-ticket-id-input');
    const completeItemNameInput = document.getElementById('complete-modal-item-name-input');
    const completeItemCodeInput = document.getElementById('complete-modal-item-code-input');
    const completeIssueInput = document.getElementById('complete-modal-issue-input');
    const completeItemStockInput = document.getElementById('complete-modal-item-stock-input');
    const completeDefaultBrokenQtyInput = document.getElementById('complete-modal-default-broken-qty-input');
    const completeSubtitleEl = document.getElementById('complete-modal-subtitle');
    const completeBrokenQtyField = document.getElementById('complete-broken-qty-field');
    const completeBrokenQtyInput = document.getElementById('complete-broken-qty');
    const completeMaxStockLabel = document.getElementById('complete-max-stock-label');
    const completeActionLabel = document.getElementById('complete-action-taken-label');
    const completeSubmitBtn = document.getElementById('complete-submit-btn');

    let currentCompleteItemStock = Math.max(1, Number(completeItemStockInput ? completeItemStockInput.value : 1) || 1);

    const receiptModal = document.getElementById('receipt-preview-modal');
    const receiptImg = document.getElementById('receipt-modal-img');
    const receiptError = document.getElementById('receipt-modal-error');
    const receiptTitleEl = document.getElementById('receipt-modal-title');
    const receiptMetaEl = document.getElementById('receipt-modal-meta');
    const receiptNewTab = document.getElementById('receipt-modal-newtab');

    function syncStartVendorVisibility() {
        const checked = document.querySelector('.start-service-radio:checked');
        const isVendor = checked && checked.value === 'vendor_luar';
        if (startVendorFields) {
            startVendorFields.classList.toggle('hidden', !isVendor);
        }
        if (startVendorInput) {
            startVendorInput.required = Boolean(isVendor);
            startVendorInput.disabled = !isVendor;
        }
        if (startEstInput) {
            startEstInput.disabled = !isVendor;
        }
    }

    function syncCompleteVendorVisibility() {
        const checked = document.querySelector('.complete-service-radio:checked');
        const isVendor = checked && checked.value === 'vendor_luar';
        if (completeVendorField) {
            completeVendorField.classList.toggle('hidden', !isVendor);
        }
        if (completeVendorInput) {
            completeVendorInput.required = Boolean(isVendor);
            completeVendorInput.disabled = !isVendor;
        }
    }

    function syncCompleteOutcomeState() {
        const checked = document.querySelector('.complete-outcome-radio:checked');
        const isBroken = checked && checked.value === 'broken';
        const showQty = isBroken && currentCompleteItemStock > 1;

        if (completeMaxStockLabel) {
            completeMaxStockLabel.textContent = String(currentCompleteItemStock);
        }
        if (completeBrokenQtyField) {
            completeBrokenQtyField.classList.toggle('hidden', !showQty);
        }
        if (completeBrokenQtyInput) {
            completeBrokenQtyInput.max = String(currentCompleteItemStock);
            completeBrokenQtyInput.required = Boolean(showQty);
            completeBrokenQtyInput.disabled = !showQty;
        }
        if (completeSubtitleEl) {
            completeSubtitleEl.textContent = isBroken
                ? 'Barang dicatat ke daftar Barang Rusak dan stok Master Aset otomatis berkurang/dihapus.'
                : 'Status barang otomatis kembali TERSEDIA setelah laporan disimpan.';
        }
        if (completeActionLabel) {
            completeActionLabel.innerHTML = isBroken
                ? 'Alasan Kerusakan Permanen &amp; Hasil Pengecekan <span class="text-rose-600">*</span>'
                : 'Laporan Tindakan &amp; Hasil Uji Layak <span class="text-rose-600">*</span>';
        }
        if (completeActionInput) {
            completeActionInput.placeholder = isBroken
                ? 'Jelaskan kerusakan permanen (misal: PCB terbakar, komponen tidak dapat diganti)...'
                : 'Jelaskan komponen yang diperbaiki/disetel dan hasil pengujian fungsi alat...';
        }
        if (completeSubmitBtn) {
            if (isBroken) {
                completeSubmitBtn.textContent = 'Catat Rusak & Kurangi Master Aset';
                completeSubmitBtn.className = 'px-4 py-2 bg-rose-700 hover:bg-rose-600 text-white text-xs font-semibold rounded-lg border border-rose-700 transition-colors';
            } else {
                completeSubmitBtn.textContent = 'Simpan Laporan & Selesaikan';
                completeSubmitBtn.className = 'px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-700 transition-colors';
            }
        }
    }

    document.querySelectorAll('.start-service-radio').forEach(function (radio) {
        radio.addEventListener('change', syncStartVendorVisibility);
    });
    document.querySelectorAll('.complete-service-radio').forEach(function (radio) {
        radio.addEventListener('change', syncCompleteVendorVisibility);
    });
    document.querySelectorAll('.complete-outcome-radio').forEach(function (radio) {
        radio.addEventListener('change', syncCompleteOutcomeState);
    });

    function openStartModal(data, preserveOld) {
        if (!startModal || !startForm) return;
        startForm.action = data.action || '';
        startTicketIdInput.value = data.ticketId || '';
        startItemNameInput.value = data.itemName || '-';
        startItemCodeInput.value = data.itemCode || '—';
        startIssueInput.value = data.issue || '-';

        startItemEl.textContent = data.itemName || '-';
        startCodeEl.textContent = data.itemCode || '—';
        startIssueEl.textContent = data.issue || '-';

        if (!preserveOld) {
            const serverErrBox = document.getElementById('start-modal-server-errors');
            if (serverErrBox) serverErrBox.classList.add('hidden');
            const targetStartService = data.serviceType === 'vendor_luar' ? 'vendor_luar' : 'internal';
            const targetStartRadio = document.querySelector('.start-service-radio[value="' + targetStartService + '"]');
            if (targetStartRadio) targetStartRadio.checked = true;
            if (startVendorInput) startVendorInput.value = data.vendorName || '';
            if (startEstInput) startEstInput.value = data.estimatedFinish || '';
            if (startActionInput) startActionInput.value = '';
        }

        syncStartVendorVisibility();
        startModal.classList.remove('hidden');
        startModal.classList.add('flex');
    }

    function closeStartModal() {
        if (!startModal) return;
        startModal.classList.add('hidden');
        startModal.classList.remove('flex');
    }

    function openCompleteModal(data, preserveOld) {
        if (!completeModal || !completeForm) return;
        completeForm.action = data.action || '';
        completeTicketIdInput.value = data.ticketId || '';
        completeItemNameInput.value = data.itemName || '-';
        completeItemCodeInput.value = data.itemCode || '—';
        completeIssueInput.value = data.issue || '-';

        completeItemEl.textContent = data.itemName || '-';
        completeCodeEl.textContent = data.itemCode || '—';
        completeIssueEl.textContent = data.issue || '-';

        const existingCostNum = Number(data.existingCost || 0);
        if (completeCostHelperDefault && completeCostHelperExisting && completeExistingCostLabel) {
            if (existingCostNum > 0) {
                completeExistingCostLabel.textContent = 'Rp ' + existingCostNum.toLocaleString('id-ID');
                completeCostHelperDefault.classList.add('hidden');
                completeCostHelperExisting.classList.remove('hidden');
            } else {
                completeCostHelperDefault.classList.remove('hidden');
                completeCostHelperExisting.classList.add('hidden');
            }
        }

        currentCompleteItemStock = Math.max(1, Number(data.itemStock || (completeItemStockInput ? completeItemStockInput.value : 1) || 1));
        const defaultBrokenQty = Math.min(
            currentCompleteItemStock,
            Math.max(1, Number(data.brokenQty || (completeDefaultBrokenQtyInput ? completeDefaultBrokenQtyInput.value : 1) || 1))
        );
        if (completeItemStockInput) completeItemStockInput.value = String(currentCompleteItemStock);
        if (completeDefaultBrokenQtyInput) completeDefaultBrokenQtyInput.value = String(defaultBrokenQty);

        if (!preserveOld) {
            const serverErrBox = document.getElementById('complete-modal-server-errors');
            if (serverErrBox) serverErrBox.classList.add('hidden');
            const repairedRadio = document.querySelector('.complete-outcome-radio[value="repaired"]');
            if (repairedRadio) repairedRadio.checked = true;
            if (completeBrokenQtyInput) completeBrokenQtyInput.value = String(defaultBrokenQty);
            const targetService = data.serviceType === 'vendor_luar' ? 'vendor_luar' : 'internal';
            const targetRadio = document.querySelector('.complete-service-radio[value="' + targetService + '"]');
            if (targetRadio) targetRadio.checked = true;
            if (completeVendorInput) completeVendorInput.value = data.vendorName || '';
            if (completeActionInput) completeActionInput.value = '';
            if (completeSparepartsInput) completeSparepartsInput.value = '';
            if (completeCostInput) completeCostInput.value = '';
            if (completeReceiptInput) {
                completeReceiptInput.value = '';
                resetReceiptInputState(completeReceiptInput);
            }
        }

        syncCompleteVendorVisibility();
        syncCompleteOutcomeState();
        completeModal.classList.remove('hidden');
        completeModal.classList.add('flex');
    }

    // Validasi ukuran & kompresi otomatis foto struk sebelum submit agar tidak terkena batas PHP upload_max_filesize
    const serverMaxUploadBytes = {{ (int) $serverMaxUploadBytes }};
    const serverMaxMbLabel = @json($serverMaxMbLabel);
    const safeUploadMaxBytes = Math.floor(serverMaxUploadBytes * 0.92);
    const autoCompressTargetBytes = Math.min(Math.floor(serverMaxUploadBytes * 0.72), 1400 * 1024);

    function formatFileSizeId(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
        return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
    }

    function setReceiptFeedback(input, statusType, message) {
        if (!input) return;
        const feedbackId = input.dataset.feedbackId;
        const feedbackEl = feedbackId ? document.getElementById(feedbackId) : null;
        if (!feedbackEl) return;

        if (!message) {
            feedbackEl.textContent = '';
            feedbackEl.className = 'hidden text-[11px] mt-1 font-medium';
            return;
        }

        const colorClass = statusType === 'error'
            ? 'text-rose-600'
            : (statusType === 'success' ? 'text-emerald-700' : 'text-neutral-600');
        feedbackEl.className = 'text-[11px] mt-1 font-medium ' + colorClass;
        feedbackEl.textContent = message;
    }

    function resetReceiptInputState(input) {
        if (!input) return;
        input.dataset.seq = String((Number(input.dataset.seq || 0)) + 1);
        delete input.dataset.uploadBlocked;
        delete input.dataset.compressing;
        const form = input.closest('form');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        if (submitBtn) submitBtn.disabled = false;
        setReceiptFeedback(input, null, '');
    }

    function encodeCanvasAsJpeg(canvas, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(function (blob) {
                resolve(blob);
            }, 'image/jpeg', quality);
        });
    }

    async function compressReceiptImage(file, targetBytes) {
        const objectUrl = URL.createObjectURL(file);
        try {
            const img = await new Promise(function (resolve, reject) {
                const image = new Image();
                image.onload = function () { resolve(image); };
                image.onerror = function () { reject(new Error('Image decode failed')); };
                image.src = objectUrl;
            });

            const origWidth = img.naturalWidth || img.width || 1;
            const origHeight = img.naturalHeight || img.height || 1;
            const steps = [
                { maxDim: 1800, quality: 0.85 },
                { maxDim: 1600, quality: 0.78 },
                { maxDim: 1400, quality: 0.70 },
                { maxDim: 1200, quality: 0.60 },
                { maxDim: 1000, quality: 0.50 },
            ];

            let bestBlob = null;
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            for (let i = 0; i < steps.length; i++) {
                const step = steps[i];
                const scale = Math.min(1, step.maxDim / Math.max(origWidth, origHeight));
                canvas.width = Math.max(1, Math.round(origWidth * scale));
                canvas.height = Math.max(1, Math.round(origHeight * scale));

                if (!ctx) break;
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                const blob = await encodeCanvasAsJpeg(canvas, step.quality);
                if (!blob) continue;
                if (!bestBlob || blob.size < bestBlob.size) {
                    bestBlob = blob;
                }
                if (blob.size <= targetBytes) {
                    bestBlob = blob;
                    break;
                }
            }

            if (!bestBlob) return null;
            const baseName = (file.name || 'struk').replace(/\.[^.]+$/, '');
            return new File([bestBlob], baseName + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
        } finally {
            URL.revokeObjectURL(objectUrl);
        }
    }

    document.querySelectorAll('input[name="receipt"]').forEach(function (input) {
        const parentForm = input.closest('form');
        if (parentForm && !parentForm.dataset.receiptGuardBound) {
            parentForm.dataset.receiptGuardBound = '1';
            parentForm.addEventListener('submit', function (event) {
                const receiptInputs = parentForm.querySelectorAll('input[name="receipt"]');
                for (let i = 0; i < receiptInputs.length; i++) {
                    const rInput = receiptInputs[i];
                    if (rInput.dataset.compressing === '1' || rInput.dataset.uploadBlocked === '1') {
                        event.preventDefault();
                        rInput.focus();
                        return;
                    }
                }
            });
        }

        input.addEventListener('change', async function () {
            const file = input.files && input.files[0];
            if (!file) {
                resetReceiptInputState(input);
                return;
            }

            const seq = String((Number(input.dataset.seq || 0)) + 1);
            input.dataset.seq = seq;
            delete input.dataset.uploadBlocked;

            const isImage = (file.type && file.type.startsWith('image/')) || /\.(jpe?g|png|webp)$/i.test(file.name || '');
            const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');

            if (!isImage && !isPdf) {
                input.dataset.uploadBlocked = '1';
                setReceiptFeedback(input, 'error', 'Format berkas tidak didukung. Gunakan gambar JPG, PNG, WEBP, atau PDF.');
                return;
            }

            if (isPdf) {
                if (file.size > safeUploadMaxBytes) {
                    input.dataset.uploadBlocked = '1';
                    setReceiptFeedback(
                        input,
                        'error',
                        'Ukuran PDF (' + formatFileSizeId(file.size) + ') melebihi batas upload server PHP (' + serverMaxMbLabel + ' MB). Perkecil PDF atau unggah foto struk.'
                    );
                } else {
                    setReceiptFeedback(input, 'success', 'PDF siap diunggah (' + formatFileSizeId(file.size) + ').');
                }
                return;
            }

            if (file.size <= autoCompressTargetBytes) {
                setReceiptFeedback(input, 'success', 'Gambar siap diunggah (' + formatFileSizeId(file.size) + ').');
                return;
            }

            const form = input.closest('form');
            const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
            input.dataset.compressing = '1';
            if (submitBtn) submitBtn.disabled = true;
            setReceiptFeedback(input, 'info', 'Memperkecil foto (' + formatFileSizeId(file.size) + ') agar sesuai batas server (' + serverMaxMbLabel + ' MB)...');

            try {
                const compressed = await compressReceiptImage(file, autoCompressTargetBytes);
                if (input.dataset.seq !== seq) return;

                let finalFile = file;
                if (compressed && compressed.size < file.size && typeof DataTransfer !== 'undefined') {
                    const dt = new DataTransfer();
                    dt.items.add(compressed);
                    input.files = dt.files;
                    finalFile = compressed;
                }

                if (finalFile.size <= safeUploadMaxBytes) {
                    delete input.dataset.uploadBlocked;
                    if (finalFile !== file) {
                        setReceiptFeedback(
                            input,
                            'success',
                            'Foto diperkecil otomatis: ' + formatFileSizeId(file.size) + ' → ' + formatFileSizeId(finalFile.size) + ' (siap diunggah).'
                        );
                    } else {
                        setReceiptFeedback(input, 'success', 'Gambar siap diunggah (' + formatFileSizeId(finalFile.size) + ').');
                    }
                } else {
                    input.dataset.uploadBlocked = '1';
                    setReceiptFeedback(
                        input,
                        'error',
                        'Ukuran gambar (' + formatFileSizeId(finalFile.size) + ') masih melebihi batas server PHP (' + serverMaxMbLabel + ' MB). Pilih foto dengan ukuran lebih kecil.'
                    );
                }
            } catch (err) {
                if (input.dataset.seq !== seq) return;
                if (file.size <= safeUploadMaxBytes) {
                    delete input.dataset.uploadBlocked;
                    setReceiptFeedback(input, 'info', 'Berkas dipilih (' + formatFileSizeId(file.size) + ').');
                } else {
                    input.dataset.uploadBlocked = '1';
                    setReceiptFeedback(
                        input,
                        'error',
                        'Gagal memproses gambar & ukuran (' + formatFileSizeId(file.size) + ') melebihi batas server PHP (' + serverMaxMbLabel + ' MB).'
                    );
                }
            } finally {
                if (input.dataset.seq === seq) {
                    delete input.dataset.compressing;
                    if (submitBtn) submitBtn.disabled = false;
                }
            }
        });
    });

    function closeCompleteModal() {
        if (!completeModal) return;
        completeModal.classList.add('hidden');
        completeModal.classList.remove('flex');
    }

    function openReceiptModal(url, title, meta) {
        if (!receiptModal || !receiptImg) return;
        if (receiptError) receiptError.classList.add('hidden');
        receiptImg.classList.remove('hidden');
        receiptImg.src = url;
        if (receiptTitleEl) receiptTitleEl.textContent = title || 'Struk / Nota Perbaikan';
        if (receiptMetaEl) receiptMetaEl.textContent = meta || '-';
        if (receiptNewTab) receiptNewTab.href = url;
        receiptModal.classList.remove('hidden');
        receiptModal.classList.add('flex');
    }

    function closeReceiptModal() {
        if (!receiptModal || !receiptImg) return;
        receiptModal.classList.add('hidden');
        receiptModal.classList.remove('flex');
        receiptImg.removeAttribute('src');
        if (receiptError) receiptError.classList.add('hidden');
        receiptImg.classList.remove('hidden');
    }

    if (receiptImg) {
        receiptImg.addEventListener('error', function () {
            if (!receiptImg.getAttribute('src')) return;
            receiptImg.classList.add('hidden');
            if (receiptError) receiptError.classList.remove('hidden');
        });
    }

    document.addEventListener('click', function (event) {
        const startBtn = event.target.closest('.open-start-repair-modal');
        if (startBtn) {
            openStartModal({
                action: startBtn.dataset.action,
                ticketId: startBtn.dataset.ticketId,
                itemName: startBtn.dataset.itemName,
                itemCode: startBtn.dataset.itemCode,
                issue: startBtn.dataset.issue,
                serviceType: startBtn.dataset.serviceType,
                vendorName: startBtn.dataset.vendorName,
                estimatedFinish: startBtn.dataset.estimatedFinish,
            }, false);
            return;
        }

        const completeBtn = event.target.closest('.open-complete-repair-modal');
        if (completeBtn) {
            openCompleteModal({
                action: completeBtn.dataset.action,
                ticketId: completeBtn.dataset.ticketId,
                itemName: completeBtn.dataset.itemName,
                itemCode: completeBtn.dataset.itemCode,
                itemStock: completeBtn.dataset.itemStock,
                brokenQty: completeBtn.dataset.brokenQty,
                issue: completeBtn.dataset.issue,
                serviceType: completeBtn.dataset.serviceType,
                vendorName: completeBtn.dataset.vendorName,
                existingCost: completeBtn.dataset.existingCost,
            }, false);
            return;
        }

        const receiptBtn = event.target.closest('.open-receipt-modal');
        if (receiptBtn) {
            openReceiptModal(
                receiptBtn.dataset.receiptUrl,
                receiptBtn.dataset.receiptTitle,
                receiptBtn.dataset.receiptMeta
            );
        }
    });

    ['start-modal-close', 'start-modal-cancel', 'start-modal-backdrop'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', closeStartModal);
    });

    ['complete-modal-close', 'complete-modal-cancel', 'complete-modal-backdrop'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', closeCompleteModal);
    });

    ['receipt-modal-close', 'receipt-modal-dismiss', 'receipt-modal-backdrop'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', closeReceiptModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (startModal && !startModal.classList.contains('hidden')) closeStartModal();
        if (completeModal && !completeModal.classList.contains('hidden')) closeCompleteModal();
        if (receiptModal && !receiptModal.classList.contains('hidden')) closeReceiptModal();
    });

    // Inisialisasi awal & pemulihan modal otomatis bila validasi server gagal
    syncStartVendorVisibility();
    syncCompleteVendorVisibility();
    syncCompleteOutcomeState();

    const oldModalMode = @json($oldModalMode);
    const oldTicketId = @json($oldModalTicketId);
    const hasErrors = @json($errors->any());

    if (hasErrors && oldModalMode && oldTicketId) {
        if (oldModalMode === 'start' && startForm && startForm.getAttribute('action')) {
            openStartModal({
                action: startForm.getAttribute('action'),
                ticketId: oldTicketId,
                itemName: startItemNameInput.value,
                itemCode: startItemCodeInput.value,
                issue: startIssueInput.value,
            }, true);
        } else if (oldModalMode === 'complete' && completeForm && completeForm.getAttribute('action')) {
            openCompleteModal({
                action: completeForm.getAttribute('action'),
                ticketId: oldTicketId,
                itemName: completeItemNameInput.value,
                itemCode: completeItemCodeInput.value,
                itemStock: completeItemStockInput ? completeItemStockInput.value : 1,
                brokenQty: completeDefaultBrokenQtyInput ? completeDefaultBrokenQtyInput.value : 1,
                issue: completeIssueInput.value,
            }, true);
        }
    }
});
</script>
@endpush

