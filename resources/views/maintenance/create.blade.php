<x-app-layout>
    <x-slot name="heading">Buat Tiket Kerusakan</x-slot>
    <x-slot name="title">Buat Tiket M&R</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('maintenance.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Buat Tiket Kerusakan Baru</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Laporkan kendala kerusakan alat laboratorium untuk masuk antrean penanganan teknisi.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('maintenance.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="create-ticket-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Tiket Kerusakan</span>
                </button>
            </div>
        </div>

        <form id="create-ticket-form" method="POST" action="{{ route('maintenance.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Detail Barang & Kendala</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Pilih barang Master Aset yang mengalami masalah dan uraikan kendalanya.</p>
                        </div>

                        <div>
                            <x-input-label for="item_id" value="Nama Alat / Barang (Master Aset) *" />
                            <select id="item_id" name="item_id" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                <option value="">— Pilih alat / barang dari Master Aset —</option>
                                @foreach(($items ?? \App\Models\Item::orderBy('name')->orderBy('item_code')->get()) as $item)
                                    <option value="{{ $item->id }}" @selected(old('item_id', $preselectedItemId ?? null) == $item->id)>
                                        {{ $item->name }} ({{ $item->item_code }}) · {{ $item->type->label() }} — {{ $item->location }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="issue_description" value="Deskripsi Kendala & Gejala Kerusakan *" />
                            <textarea id="issue_description" name="issue_description" rows="5"
                                      class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900"
                                      required placeholder="Jelaskan kendala, kode error, gejala visual, atau kronologi kerusakan alat secara spesifik...">{{ old('issue_description') }}</textarea>
                            <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
                        </div>

                        <input type="hidden" name="reported_by" value="{{ auth()->id() }}">
                        <input type="hidden" name="status" value="open">
                    </div>
                </div>

                <!-- Info Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Alur Tiket M&R</h3>
                        <div class="space-y-3 text-xs text-neutral-600 leading-relaxed">
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center font-bold text-[10px] shrink-0">1</span>
                                <div>
                                    <span class="font-semibold text-neutral-900 block">Open (Menunggu)</span>
                                    <p class="text-neutral-500 text-[11px]">Tiket baru masuk antrean verifikasi oleh teknisi lab.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 flex items-center justify-center font-bold text-[10px] shrink-0">2</span>
                                <div>
                                    <span class="font-semibold text-neutral-900 block">In Progress</span>
                                    <p class="text-neutral-500 text-[11px]">Sedang diperbaiki teknisi internal atau dikirim ke vendor luar.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-[10px] shrink-0">3</span>
                                <div>
                                    <span class="font-semibold text-neutral-900 block">Resolved / Damaged</span>
                                    <p class="text-neutral-500 text-[11px]">Selesai uji coba layak pakai atau dinyatakan rusak permanen.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
