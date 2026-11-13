<x-app-layout>
    <x-slot name="heading">{{ $ticket->status->value === 'open' ? 'Edit Tiket M&R #' . $ticket->id : 'Kelola & Progres Tiket M&R #' . $ticket->id }}</x-slot>
    <x-slot name="title">Edit Tiket #{{ $ticket->id }}</x-slot>

    @php
        $isOpen = $ticket->status->value === 'open';
        $isInProgress = $ticket->status->value === 'in_progress';
        $latestLog = $ticket->latestLog;
        $totalCost = (float) $ticket->logs->sum('cost');
        $estimatedDate = $latestLog?->estimated_finish_at
            ?? $ticket->logs->first(fn ($l) => $l->estimated_finish_at !== null)?->estimated_finish_at;
        $phpMaxMbLabel = number_format(min(4 * 1024 * 1024, max(1024, \Illuminate\Http\UploadedFile::getMaxFilesize())) / 1048576, 1, ',', '.');

        $defaultServiceType = $isOpen
            ? ($latestLog?->service_type?->value ?? '')
            : ($latestLog?->service_type?->value ?? 'internal');

        $selectedServiceType = old('_form_mode') !== 'progress'
            ? old('service_type', $defaultServiceType)
            : $defaultServiceType;

        $selectedVendorName = old('_form_mode') !== 'progress'
            ? old('vendor_name', $latestLog?->vendor_name ?? '')
            : ($latestLog?->vendor_name ?? '');

        $selectedEstimatedFinish = old('_form_mode') !== 'progress'
            ? old('estimated_finish_at', optional($estimatedDate)->format('Y-m-d') ?? '')
            : (optional($estimatedDate)->format('Y-m-d') ?? '');

        $badge = match($ticket->status->value) {
            'open' => 'bg-rose-50 text-rose-700 border-rose-200',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
        };
    @endphp

    <div class="{{ $isOpen ? 'max-w-2xl' : 'max-w-4xl' }} space-y-4">
        <!-- Top Bar -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ route('maintenance.index', ['status' => $ticket->status->value]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Tiket</span>
            </a>

            @if($isInProgress)
                <button type="button"
                        class="open-complete-repair-modal inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg border border-emerald-700 transition-colors"
                        data-action="{{ route('maintenance.complete', $ticket->id) }}"
                        data-ticket-id="{{ $ticket->id }}"
                        data-item-name="{{ $ticket->item->name ?? '-' }}"
                        data-item-code="{{ $ticket->item->item_code ?? '—' }}"
                        data-item-stock="{{ max(1, (int) ($ticket->item->stock ?? 1)) }}"
                        data-broken-qty="{{ max(1, min(max(1, (int) ($ticket->item->stock ?? 1)), (int) ($ticket->preventiveSchedule?->damaged_quantity ?: 1))) }}"
                        data-issue="{{ $ticket->issue_description }}"
                        data-service-type="{{ $latestLog?->service_type?->value ?? 'internal' }}"
                        data-vendor-name="{{ $latestLog?->vendor_name ?? '' }}"
                        data-existing-cost="{{ $totalCost }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Selesai &amp; Uji Layak</span>
                </button>
            @endif
        </div>

        @if($errors->any() && !in_array(old('_modal_mode'), ['start', 'complete'], true))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-xs space-y-1">
                <div class="font-bold">Gagal menyimpan perubahan:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! $isOpen)
            <!-- Ringkasan Detail Tiket (Tampil di halaman Edit saat Sedang Dikerjakan) -->
            <div class="bg-white rounded-xl border border-neutral-200 p-5 space-y-4">
                <div class="flex items-start justify-between flex-wrap gap-3 pb-4 border-b border-neutral-100">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-bold text-neutral-900 tracking-tight font-heading">
                                Tiket <span class="font-mono">#{{ $ticket->id }}</span> — {{ $ticket->item->name ?? '-' }}
                            </h2>
                            @if(filled($ticket->item?->item_code))
                                <span class="font-mono text-[11px] px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 border border-neutral-200">{{ $ticket->item->item_code }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-neutral-500 mt-1">
                            Dilaporkan oleh <span class="font-semibold text-neutral-800">{{ $ticket->reportedBy->name ?? 'Siswa Kiosk' }}</span>
                            <span class="mx-1 text-neutral-300">|</span>
                            <span class="font-mono">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</span>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $badge }}">{{ $ticket->status->label() }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50/70">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Penanganan Saat Ini</div>
                        <div class="mt-1">
                            @if($latestLog)
                                <span class="text-xs font-bold {{ $latestLog->service_type->value === 'vendor_luar' ? 'text-amber-700' : 'text-primary-700' }}">
                                    {{ $latestLog->service_type->value === 'vendor_luar' ? 'Vendor Luar' : 'Internal Sekolah' }}
                                </span>
                                @if($latestLog->service_type->value === 'vendor_luar' && filled($latestLog->vendor_name))
                                    <div class="text-xs text-neutral-600 mt-0.5">{{ $latestLog->vendor_name }}</div>
                                @endif
                            @else
                                <span class="text-xs text-neutral-400">Belum dicatat</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50/70">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Estimasi / Progres</div>
                        <div class="mt-1 text-xs font-mono text-neutral-800">
                            @if($estimatedDate && $isInProgress)
                                Est. Selesai: {{ $estimatedDate->format('d/m/Y') }}
                            @else
                                {{ $ticket->logs->count() }} catatan log
                            @endif
                        </div>
                    </div>

                    <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50/70">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Total Biaya Tercatat</div>
                        <div class="mt-1 font-mono text-sm font-bold text-neutral-900">
                            {{ $totalCost > 0 ? 'Rp ' . number_format($totalCost, 0, ',', '.') : 'Rp 0 (Tanpa biaya)' }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form Utama Edit Tiket -->
        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <div class="flex items-start justify-between flex-wrap gap-2 mb-5 pb-3 border-b border-neutral-100">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">
                        Edit Tiket <span class="font-mono">#{{ $ticket->id }}</span>
                    </h2>
                    @if($isOpen)
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Aset: <span class="font-semibold text-neutral-800">{{ $ticket->item->name ?? '-' }}</span>
                            @if(filled($ticket->item?->item_code))
                                (<span class="font-mono">{{ $ticket->item->item_code }}</span>)
                            @endif
                        </p>
                    @else
                        <p class="text-xs text-neutral-500 mt-0.5">Perbarui deskripsi kendala, status, lokasi penanganan, atau tambah catatan.</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="tab">

                <div>
                    <x-input-label for="issue_description" value="Deskripsi Kendala *" />
                    <textarea id="issue_description" name="issue_description" rows="3" required
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">{{ old('_form_mode') !== 'progress' ? old('issue_description', $ticket->issue_description) : $ticket->issue_description }}</textarea>
                    <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="status" value="Status Tiket *" />
                        @php
                            $selectedStatus = old('_form_mode') !== 'progress'
                                ? old('status', $ticket->status->value)
                                : $ticket->status->value;
                        @endphp
                        <select id="status" name="status" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">
                            <option value="open" @selected($selectedStatus === 'open')>Open (Perlu Tindakan)</option>
                            <option value="in_progress" @selected($selectedStatus === 'in_progress')>In Progress (Sedang Dikerjakan)</option>
                            <option value="resolved" @selected($selectedStatus === 'resolved')>Resolved (Selesai)</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit-service-type" value="Tempat Perbaikan" />
                        <select id="edit-service-type" name="service_type" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">
                            @if($isOpen)
                                <option value="" @selected($selectedServiceType === '')>— Belum ditentukan —</option>
                            @endif
                            <option value="internal" @selected($selectedServiceType === 'internal')>Di Sekolah (Internal Lab)</option>
                            <option value="vendor_luar" @selected($selectedServiceType === 'vendor_luar')>Kirim ke Vendor Luar</option>
                        </select>
                        <x-input-error :messages="$errors->get('service_type')" class="mt-2" />
                    </div>
                </div>

                <!-- Detail Vendor (Muncul dinamis bila memilih Vendor Luar) -->
                <div id="edit-vendor-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-lg bg-amber-50/60 border border-amber-200 {{ $selectedServiceType === 'vendor_luar' ? '' : 'hidden' }}">
                    <div>
                        <label for="edit-vendor-name" class="block text-xs font-semibold text-neutral-800">Nama Vendor / Bengkel <span class="text-rose-600">*</span></label>
                        <input type="text" id="edit-vendor-name" name="vendor_name"
                               value="{{ $selectedVendorName }}"
                               placeholder="Misal: Toko Servis Jaringan Depok"
                               class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                        <x-input-error :messages="$errors->get('vendor_name')" class="mt-1" />
                    </div>
                    <div>
                        <label for="edit-estimated-finish" class="block text-xs font-semibold text-neutral-800">Estimasi Selesai <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                        <input type="date" id="edit-estimated-finish" name="estimated_finish_at"
                               value="{{ $selectedEstimatedFinish }}"
                               class="mt-1 w-full rounded-lg border-neutral-300 text-xs font-mono focus:border-primary focus:ring-primary">
                        <x-input-error :messages="$errors->get('estimated_finish_at')" class="mt-1" />
                    </div>
                </div>

                <!-- Filter Kondisi Akhir Barang (Muncul bila Status dipilih Resolved) -->
                @php
                    $editItemStock = max(1, (int) ($ticket->item->stock ?? 1));
                    $editDefaultBrokenQty = max(1, min($editItemStock, (int) ($ticket->preventiveSchedule?->damaged_quantity ?: 1)));
                    $editSelectedOutcome = old('_form_mode') !== 'progress'
                        ? old('outcome', $ticket->outcome ?? 'repaired')
                        : ($ticket->outcome ?? 'repaired');
                @endphp
                <div id="edit-outcome-fields" class="{{ $selectedStatus === 'resolved' ? '' : 'hidden' }} p-3.5 rounded-lg bg-neutral-50 border border-neutral-200 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-800 mb-2">Hasil Kondisi Barang (Saat Diselesaikan)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <label class="cursor-pointer">
                                <input type="radio" name="outcome" value="repaired" class="peer sr-only edit-outcome-radio" {{ $editSelectedOutcome !== 'broken' ? 'checked' : '' }}>
                                <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/70 transition-colors">
                                    <div class="text-xs font-bold text-neutral-900">Bener / Layak Pakai</div>
                                    <p class="text-[11px] text-neutral-500 mt-0.5">Barang normal &amp; berstatus TERSEDIA kembali.</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="outcome" value="broken" class="peer sr-only edit-outcome-radio" {{ $editSelectedOutcome === 'broken' ? 'checked' : '' }}>
                                <div class="p-2.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 peer-checked:border-rose-500 peer-checked:bg-rose-50/70 transition-colors">
                                    <div class="text-xs font-bold text-neutral-900">Rusak Permanen</div>
                                    <p class="text-[11px] text-neutral-500 mt-0.5">Masuk daftar Barang Rusak &amp; kurangi Master Aset.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    @if($editItemStock > 1)
                        <div id="edit-broken-qty-wrap" class="{{ $editSelectedOutcome === 'broken' ? '' : 'hidden' }}">
                            <label for="edit-broken-qty" class="block text-xs font-semibold text-rose-900">
                                Jumlah Unit Rusak Permanen (Maks. <span class="font-mono">{{ $editItemStock }}</span> unit)
                            </label>
                            <input type="number" id="edit-broken-qty" name="broken_quantity" min="1" max="{{ $editItemStock }}"
                                   value="{{ old('_form_mode') !== 'progress' ? old('broken_quantity', $editDefaultBrokenQty) : $editDefaultBrokenQty }}"
                                   class="mt-1 w-full sm:w-48 rounded-lg border-rose-300 bg-white text-xs font-mono focus:border-rose-500 focus:ring-rose-500">
                            <x-input-error :messages="$errors->get('broken_quantity')" class="mt-1" />
                        </div>
                    @endif
                </div>

                <div>
                    <x-input-label for="notes" value="Catatan (Opsional)" />
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="{{ $isOpen ? 'Catatan awal diagnosa atau rencana penanganan...' : 'Catatan progres terbaru atau alasan pemindahan tempat servis...' }}"
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">{{ old('_form_mode') !== 'progress' ? old('notes') : '' }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('maintenance.index', ['status' => $ticket->status->value]) }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>

        @if(! $isOpen)
            @php
                $progressHasError = old('_form_mode') === 'progress' && $errors->any();
                $progressServiceValue = old('_form_mode') === 'progress'
                    ? old('service_type', 'internal')
                    : ($latestLog?->service_type?->value ?? 'internal');
                $progressVendorValue = old('_form_mode') === 'progress'
                    ? old('vendor_name', '')
                    : ($latestLog?->vendor_name ?? '');
            @endphp

            <!-- Form Nota / Progres Bertahap (Opsional & Collapsible) -->
            @if($isInProgress)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-4 py-3.5 bg-neutral-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Catat Nota Pembelian / Biaya Bertahap <span class="text-neutral-400 font-normal">(Opsional)</span></h3>
                            <p class="text-[11px] text-neutral-500 mt-0.5">
                                Biaya akhir dan struk normalnya cukup diisi sekali saat menekan tombol <span class="font-semibold text-neutral-700">Selesai &amp; Uji Layak</span>. Gunakan formulir ini hanya bila ada pembelian sparepart atau DP vendor di tengah pengerjaan.
                            </p>
                        </div>
                        <button type="button" id="toggle-progress-expense-btn"
                                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-neutral-200 bg-white hover:bg-neutral-100 text-neutral-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span id="toggle-progress-expense-label">{{ $progressHasError ? 'Tutup Form Nota' : 'Tambah Nota Bertahap' }}</span>
                        </button>
                    </div>

                    <div id="progress-expense-panel" class="{{ $progressHasError ? '' : 'hidden' }} border-t border-neutral-200 p-4 sm:p-5">
                        <form method="POST" action="{{ route('maintenance.logs.store', $ticket->id) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="_form_mode" value="progress">
                            <input type="hidden" name="return_to" value="edit">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label for="progress-service-type" class="block text-xs font-semibold text-neutral-700">Tempat Penanganan <span class="text-rose-600">*</span></label>
                                    <select id="progress-service-type" name="service_type" class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                                        <option value="internal" @selected($progressServiceValue === 'internal')>Internal Sekolah</option>
                                        <option value="vendor_luar" @selected($progressServiceValue === 'vendor_luar')>Vendor Luar</option>
                                    </select>
                                </div>

                                <div id="progress-vendor-wrap" class="{{ $progressServiceValue === 'vendor_luar' ? '' : 'hidden' }}">
                                    <label for="progress-vendor-name" class="block text-xs font-semibold text-neutral-700">Nama Vendor / Bengkel <span class="text-rose-600">*</span></label>
                                    <input type="text" id="progress-vendor-name" name="vendor_name"
                                           value="{{ $progressVendorValue }}"
                                           placeholder="Nama toko / vendor"
                                           class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                                </div>

                                <div id="progress-est-wrap" class="{{ $progressServiceValue === 'vendor_luar' ? '' : 'hidden' }}">
                                    <label for="progress-estimated-finish" class="block text-xs font-semibold text-neutral-700">Estimasi Selesai <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                                    <input type="date" id="progress-estimated-finish" name="estimated_finish_at"
                                           value="{{ old('_form_mode') === 'progress' ? old('estimated_finish_at') : optional($estimatedDate)->format('Y-m-d') }}"
                                           class="mt-1 w-full rounded-lg border-neutral-300 text-xs font-mono focus:border-primary focus:ring-primary">
                                </div>
                            </div>

                            <div>
                                <label for="progress-action-taken" class="block text-xs font-semibold text-neutral-700">Keterangan Transaksi / Tindakan Progres <span class="text-rose-600">*</span></label>
                                <textarea id="progress-action-taken" name="action_taken" rows="2" required
                                          placeholder="Misal: Pembelian modul SFP baru / pembayaran DP servis vendor..."
                                          class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">{{ old('_form_mode') === 'progress' ? old('action_taken') : '' }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label for="progress-spareparts" class="block text-xs font-semibold text-neutral-700">Sparepart Dibeli <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                                    <input type="text" id="progress-spareparts" name="spareparts_used"
                                           value="{{ old('_form_mode') === 'progress' ? old('spareparts_used') : '' }}"
                                           placeholder="Misal: IC Regulator 5V"
                                           class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                                </div>

                                <div>
                                    <label for="progress-cost" class="block text-xs font-semibold text-neutral-700">Biaya / Harga <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                                    <div class="relative mt-1">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-mono text-neutral-500 pointer-events-none">Rp</span>
                                        <input type="number" id="progress-cost" name="cost" min="0" step="100"
                                               value="{{ old('_form_mode') === 'progress' ? old('cost') : '' }}"
                                               placeholder="0"
                                               class="w-full pl-9 rounded-lg border-neutral-300 text-xs font-mono focus:border-primary focus:ring-primary">
                                    </div>
                                </div>

                                <div>
                                    <label for="progress-receipt" class="block text-xs font-semibold text-neutral-700">Foto / PDF Struk <span class="text-neutral-400 font-normal">(Opsional)</span></label>
                                    <input type="file" id="progress-receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                           data-feedback-id="progress-receipt-feedback"
                                           class="mt-1 block w-full text-xs text-neutral-600 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border file:border-neutral-300 file:text-xs file:font-semibold file:bg-white file:text-neutral-700 hover:file:bg-neutral-50">
                                    <p class="text-[10px] text-neutral-400 mt-1">Foto otomatis diperkecil sebelum diunggah; PDF/berkas lain maks. {{ $phpMaxMbLabel }} MB (batas server PHP).</p>
                                    <p id="progress-receipt-feedback" class="hidden text-[11px] mt-1 font-medium"></p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-2 border-t border-neutral-100">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition-colors">
                                    <span>Simpan Log Nota Bertahap</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Riwayat Tindakan & Bukti Struk (Terpadu dari Detail Tiket) -->
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="px-4 py-3.5 border-b border-neutral-200 bg-neutral-50 flex items-center justify-between gap-2">
                    <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Riwayat Tindakan &amp; Struk Sejauh Ini</h3>
                    @if($totalCost > 0)
                        <span class="font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-white border border-neutral-200 text-neutral-800">
                            Total: Rp {{ number_format($totalCost, 0, ',', '.') }}
                        </span>
                    @endif
                </div>
                <div class="divide-y divide-neutral-200/70">
                    @forelse($ticket->logs as $log)
                        <div class="p-4 space-y-2">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-xs font-semibold text-neutral-900">{{ $log->handledBy->name ?? 'Teknisi' }}</p>
                                <span class="font-mono text-xs text-neutral-500">{{ optional($log->repaired_at)->format('d/m/Y H:i') }}</span>
                            </div>

                            <p class="text-xs text-neutral-700 leading-relaxed">{{ $log->action_taken }}</p>

                            @if(filled($log->spareparts_used))
                                <div class="text-[11px] text-neutral-600 bg-neutral-50 border border-neutral-200/80 rounded-md px-2.5 py-1.5">
                                    <span class="font-semibold text-neutral-800">Sparepart diganti:</span> {{ $log->spareparts_used }}
                                </div>
                            @endif

                            <div class="pt-1 flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center flex-wrap gap-2 text-xs">
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md border {{ $log->service_type->value === 'internal' ? 'bg-primary-50 text-primary-700 border-primary-100' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                        {{ $log->service_type->value === 'internal' ? 'Internal Sekolah' : 'Vendor Luar' }}
                                    </span>

                                    @if($log->service_type->value === 'vendor_luar' && filled($log->vendor_name))
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border border-neutral-200 bg-neutral-50 text-neutral-700">
                                            Vendor: {{ $log->vendor_name }}
                                        </span>
                                    @endif

                                    @if($log->estimated_finish_at)
                                        <span class="text-[11px] font-mono px-2 py-0.5 rounded-md border border-neutral-200 bg-white text-neutral-600">
                                            Est. Selesai: {{ $log->estimated_finish_at->format('d/m/Y') }}
                                        </span>
                                    @endif

                                    @if((float) $log->cost > 0)
                                        <span class="font-mono text-xs text-neutral-900 font-semibold">Rp {{ number_format($log->cost, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] text-neutral-400">Tanpa biaya</span>
                                    @endif
                                </div>

                                @if(filled($log->receipt_path))
                                    @php $isPdfReceipt = str_ends_with(strtolower((string) $log->receipt_path), '.pdf'); @endphp
                                    @if($isPdfReceipt)
                                        <a href="{{ route('maintenance.receipt', $log->id) }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-neutral-200 bg-neutral-50 hover:bg-neutral-100 text-[11px] font-semibold text-neutral-700 transition-colors">
                                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>Buka Struk PDF</span>
                                        </a>
                                    @else
                                        <button type="button"
                                                class="open-receipt-modal inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-primary-200 bg-primary-50/70 hover:bg-primary-100 text-[11px] font-semibold text-primary-700 transition-colors"
                                                data-receipt-url="{{ route('maintenance.receipt', $log->id) }}"
                                                data-receipt-title="Struk Tiket #{{ $ticket->id }} — {{ $ticket->item->name ?? '-' }}"
                                                data-receipt-meta="{{ $log->service_type->label() }}{{ filled($log->vendor_name) ? ' · ' . $log->vendor_name : '' }}{{ (float) $log->cost > 0 ? ' · Rp ' . number_format($log->cost, 0, ',', '.') : '' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Lihat Struk Nota</span>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-neutral-500 text-xs">Belum ada catatan tindakan perbaikan.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @if(! $isOpen)
        @include('maintenance.partials._repair_modals', ['returnTo' => 'edit'])
    @endif

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const editServiceSelect = document.getElementById('edit-service-type');
        const editVendorFields = document.getElementById('edit-vendor-fields');
        const editVendorInput = document.getElementById('edit-vendor-name');
        const editEstInput = document.getElementById('edit-estimated-finish');

        function syncEditVendorFields() {
            if (!editServiceSelect) return;
            const isVendor = editServiceSelect.value === 'vendor_luar';
            if (editVendorFields) editVendorFields.classList.toggle('hidden', !isVendor);
            if (editVendorInput) {
                editVendorInput.required = isVendor;
                editVendorInput.disabled = !isVendor;
            }
            if (editEstInput) {
                editEstInput.disabled = !isVendor;
            }
        }

        if (editServiceSelect) {
            editServiceSelect.addEventListener('change', syncEditVendorFields);
            syncEditVendorFields();
        }

        const editStatusSelect = document.getElementById('status');
        const editOutcomeFields = document.getElementById('edit-outcome-fields');
        const editBrokenQtyWrap = document.getElementById('edit-broken-qty-wrap');
        const editBrokenQtyInput = document.getElementById('edit-broken-qty');

        function syncEditOutcomeFields() {
            if (!editStatusSelect || !editOutcomeFields) return;
            const isResolved = editStatusSelect.value === 'resolved';
            editOutcomeFields.classList.toggle('hidden', !isResolved);

            const checkedOutcome = document.querySelector('.edit-outcome-radio:checked');
            const isBroken = isResolved && checkedOutcome && checkedOutcome.value === 'broken';

            if (editBrokenQtyWrap) {
                editBrokenQtyWrap.classList.toggle('hidden', !isBroken);
            }
            if (editBrokenQtyInput) {
                editBrokenQtyInput.disabled = !isBroken;
            }
        }

        if (editStatusSelect) {
            editStatusSelect.addEventListener('change', syncEditOutcomeFields);
        }
        document.querySelectorAll('.edit-outcome-radio').forEach(function (radio) {
            radio.addEventListener('change', syncEditOutcomeFields);
        });
        syncEditOutcomeFields();

        const toggleProgressBtn = document.getElementById('toggle-progress-expense-btn');
        const toggleProgressLabel = document.getElementById('toggle-progress-expense-label');
        const progressPanel = document.getElementById('progress-expense-panel');

        if (toggleProgressBtn && progressPanel) {
            toggleProgressBtn.addEventListener('click', function () {
                const willOpen = progressPanel.classList.contains('hidden');
                progressPanel.classList.toggle('hidden', !willOpen);
                if (toggleProgressLabel) {
                    toggleProgressLabel.textContent = willOpen ? 'Tutup Form Nota' : 'Tambah Nota Bertahap';
                }
            });
        }

        const progressServiceSelect = document.getElementById('progress-service-type');
        const progressVendorWrap = document.getElementById('progress-vendor-wrap');
        const progressEstWrap = document.getElementById('progress-est-wrap');
        const progressVendorInput = document.getElementById('progress-vendor-name');
        const progressEstInput = document.getElementById('progress-estimated-finish');

        function syncProgressVendor() {
            if (!progressServiceSelect) return;
            const isVendor = progressServiceSelect.value === 'vendor_luar';
            if (progressVendorWrap) progressVendorWrap.classList.toggle('hidden', !isVendor);
            if (progressEstWrap) progressEstWrap.classList.toggle('hidden', !isVendor);
            if (progressVendorInput) {
                progressVendorInput.required = isVendor;
                progressVendorInput.disabled = !isVendor;
            }
            if (progressEstInput) {
                progressEstInput.disabled = !isVendor;
            }
        }

        if (progressServiceSelect) {
            progressServiceSelect.addEventListener('change', syncProgressVendor);
            syncProgressVendor();
        }
    });
    </script>
    @endpush
</x-app-layout>
