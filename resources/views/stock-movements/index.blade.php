<x-app-layout>
    <x-slot name="heading">Buku Stok Bahan Habis Pakai</x-slot>
    <x-slot name="title">Buku Stok Bahan — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">

        <!-- Top Action Bar (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Buku Stok Bahan Habis Pakai
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Buku besar mutasi logistik laboratorium (konektor RJ45, kabel LAN, timah solder, dll).
                </p>
            </div>

            <!-- Top Right Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('items.index') }}"
                   class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Katalog Master Aset</span>
                </a>
                <a href="{{ route('items.create') }}"
                   class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Bahan Baru</span>
                </a>
            </div>
        </div>

        <!-- 4 Metric Cards (Dribbble Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Jenis Bahan (BHP)</span>
                    <div class="text-2xl font-extrabold text-neutral-900 mt-1 font-heading tabular-nums">
                        {{ number_format($stats['consumable_count'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-neutral-400 mt-0.5 block">Kategori terdaftar</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-700 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border {{ ($stats['low_stock_count'] ?? 0) > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-neutral-200' }} shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Stok Menipis (&le; 5)</span>
                    <div class="text-2xl font-extrabold text-amber-700 mt-1 font-heading tabular-nums">
                        {{ number_format($stats['low_stock_count'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-amber-600 mt-0.5 block">Perlu pengadaan lab</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0 text-amber-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Total Unit Masuk</span>
                    <div class="text-2xl font-extrabold text-emerald-700 mt-1 font-heading tabular-nums">
                        +{{ number_format($stats['total_in'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-emerald-600 mt-0.5 block">Akumulasi pengadaan</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center shrink-0 text-emerald-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider font-heading">Total Unit Keluar</span>
                    <div class="text-2xl font-extrabold text-rose-700 mt-1 font-heading tabular-nums">
                        -{{ number_format($stats['total_out'] ?? 0) }}
                    </div>
                    <span class="text-[11px] text-rose-600 mt-0.5 block">Terpakai praktikum</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center shrink-0 text-rose-600 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 0l-4 4m4-4l4 4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Form Pencatatan Mutasi Stok (Rounded-2xl Card) -->
        <div class="bg-white rounded-2xl border border-neutral-200 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-neutral-100">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Catat Mutasi Stok Bahan Habis Pakai</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Rekam pengadaan stok masuk (in) atau pengambilan praktikum siswa/guru (out) secara akurat.</p>
                </div>
            </div>

            @if($consumables->isEmpty())
                <div class="mt-4 p-5 rounded-2xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-600 flex items-center justify-between gap-3 flex-wrap">
                    <span>Belum ada barang bertipe <span class="font-mono font-semibold">bahan_habis_pakai</span> di Master Aset (mis. Konektor RJ45, Kabel UTP, Timah Solder).</span>
                    <a href="{{ route('items.create') }}" class="px-4 py-2 rounded-full bg-neutral-900 text-white font-semibold hover:bg-neutral-800 transition-colors shadow-sm">Tambah Bahan Baru</a>
                </div>
            @else
                <form method="POST" action="{{ route('stock-movements.store') }}" class="mt-5 grid grid-cols-1 md:grid-cols-12 gap-3.5 items-start">
                    @csrf
                    <div class="md:col-span-4">
                        <x-input-label for="ledger-item-id" value="Bahan Habis Pakai *" />
                        <select id="ledger-item-id" name="item_id" required class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs focus:border-primary focus:ring-primary">
                            <option value="">-- Pilih Bahan --</option>
                            @foreach($consumables as $c)
                                <option value="{{ $c->id }}" @selected(old('item_id', request('item_id')) == $c->id)>
                                    {{ $c->name }} ({{ $c->item_code }}) — Stok: {{ $c->stock }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('item_id')" class="mt-1.5" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="ledger-type" value="Jenis Mutasi *" />
                        <select id="ledger-type" name="type" required class="mt-1.5 w-full rounded-xl border-neutral-200 text-xs focus:border-primary focus:ring-primary">
                            <option value="out" @selected(old('type', 'out') === 'out')>Keluar (Pakai)</option>
                            <option value="in" @selected(old('type') === 'in')>Masuk (Tambah)</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="ledger-qty" value="Jumlah *" />
                        <x-text-input id="ledger-qty" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required class="mt-1.5 w-full font-mono text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-1.5" />
                    </div>

                    <div class="md:col-span-4">
                        <x-input-label for="ledger-recipient" value="Pengambil / Kelas / Vendor (Opsional)" />
                        <x-text-input id="ledger-recipient" name="recipient_name" type="text" value="{{ old('recipient_name') }}" placeholder="Contoh: XI SIJA 2 / Praktikum UTP" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        <x-input-error :messages="$errors->get('recipient_name')" class="mt-1.5" />
                    </div>

                    <div class="md:col-span-9">
                        <x-input-label for="ledger-desc" value="Keterangan Keperluan / Sumber *" />
                        <x-text-input id="ledger-desc" name="description" type="text" value="{{ old('description') }}" required placeholder="Contoh: Praktikum terminasi kabel UTP Cat6 kelompok 3 / Pengadaan stok lab" class="mt-1.5 w-full text-xs rounded-xl border-neutral-200 focus:border-primary focus:ring-primary" />
                        <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
                    </div>

                    <div class="md:col-span-3 md:pt-6">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Simpan Mutasi</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Tabel Ledger Riwayat Mutasi Stok (Rounded-2xl Table ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-neutral-200 bg-neutral-50/70 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight font-heading">Riwayat Mutasi Stok (Stock Ledger)</h3>

                <form method="GET" action="{{ route('stock-movements.index') }}" class="flex items-center gap-2 flex-wrap">
                    <select name="item_id" aria-label="Filter Bahan" class="rounded-full border border-neutral-300 bg-white text-xs py-1.5 px-3.5 focus:ring-1 focus:ring-primary focus:border-primary shadow-sm">
                        <option value="">Semua Bahan</option>
                        @foreach($consumables as $c)
                            <option value="{{ $c->id }}" @selected(request('item_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <select name="type" aria-label="Filter Jenis" class="rounded-full border border-neutral-300 bg-white text-xs py-1.5 px-3.5 focus:ring-1 focus:ring-primary focus:border-primary shadow-sm">
                        <option value="">Semua Mutasi</option>
                        <option value="in" @selected(request('type') === 'in')>Masuk (In)</option>
                        <option value="out" @selected(request('type') === 'out')>Keluar (Out)</option>
                    </select>

                    <button type="submit" class="px-4 py-1.5 rounded-full bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold border border-neutral-200 shadow-sm transition-colors">
                        Filter
                    </button>
                    @if(request('item_id') || request('type'))
                        <a href="{{ route('stock-movements.index') }}" class="px-3 py-1.5 text-xs font-semibold text-neutral-500 hover:text-neutral-800">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Waktu Transaksi</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Bahan / Barang</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Jenis Mutasi</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Perubahan</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Sisa Saldo</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Penerima &amp; Keperluan</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($movements as $m)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    {{ $m->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-neutral-900 text-xs font-heading">{{ $m->item->name ?? '-' }}</div>
                                            <div class="font-mono text-[11px] text-neutral-500 mt-0.5">{{ $m->item->item_code ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $m->type === 'in' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                        {{ $m->type === 'in' ? 'Masuk (+)' : 'Keluar (-)' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center font-mono font-bold text-xs">
                                    <span class="{{ $m->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $m->type === 'in' ? '+' : '-' }}{{ number_format($m->quantity) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center font-mono text-xs text-neutral-700 font-bold whitespace-nowrap">
                                    {{ number_format($m->stock_after) }}
                                </td>
                                <td class="px-5 py-4 text-xs">
                                    @if(filled($m->recipient_name))
                                        <div class="font-semibold text-neutral-900">{{ $m->recipient_name }}</div>
                                    @endif
                                    <div class="text-[11px] text-neutral-500">{{ $m->description }}</div>
                                </td>
                                <td class="px-5 py-4 text-xs text-neutral-600 whitespace-nowrap">
                                    {{ $m->user->name ?? 'Sistem' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Belum Ada Transaksi Mutasi</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Gunakan form di atas untuk mencatat mutasi stok bahan pertama kali.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>