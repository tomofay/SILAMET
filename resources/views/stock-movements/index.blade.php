<x-app-layout>
    <x-slot name="heading">Buku Stok Bahan Habis Pakai</x-slot>
    <x-slot name="title">Buku Stok Bahan</x-slot>

    <div class="space-y-4">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 border border-neutral-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jenis Bahan (BHP)</span>
                    <div class="text-2xl font-bold text-neutral-900 mt-1 font-mono tabular-nums">
                        {{ number_format($stats['consumable_count'] ?? 0) }}
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-primary/10 text-primary-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 border {{ ($stats['low_stock_count'] ?? 0) > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-neutral-200' }} flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Stok Menipis (&le; 5)</span>
                    <div class="text-2xl font-bold text-amber-700 mt-1 font-mono tabular-nums">
                        {{ number_format($stats['low_stock_count'] ?? 0) }}
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 border border-neutral-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Unit Masuk</span>
                    <div class="text-2xl font-bold text-emerald-700 mt-1 font-mono tabular-nums">
                        +{{ number_format($stats['total_in'] ?? 0) }}
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4" />
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 border border-neutral-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Unit Keluar</span>
                    <div class="text-2xl font-bold text-rose-700 mt-1 font-mono tabular-nums">
                        -{{ number_format($stats['total_out'] ?? 0) }}
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-rose-50 text-rose-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 0l-4 4m4-4l4 4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Form Pencatatan Mutasi Stok + Filter -->
        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-neutral-100">
                <div>
                    <h2 class="text-base font-bold text-neutral-900 tracking-tight font-heading">Catat Mutasi Stok Bahan Habis Pakai</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Rekam pengadaan stok masuk (in) atau pengambilan praktikum siswa/guru (out) secara akurat.</p>
                </div>
                <a href="{{ route('items.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-200 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Katalog Master Aset</span>
                </a>
            </div>

            @if($consumables->isEmpty())
                <div class="mt-4 p-4 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-600 flex items-center justify-between gap-3 flex-wrap">
                    <span>Belum ada barang bertipe <span class="font-mono font-semibold">bahan_habis_pakai</span> di Master Aset (mis. Konektor RJ45, Kabel UTP, Timah Solder).</span>
                    <a href="{{ route('items.create') }}" class="px-3 py-1.5 rounded-lg bg-primary text-white font-semibold hover:bg-primary-700 transition-colors">Tambah Bahan Baru</a>
                </div>
            @else
                <form method="POST" action="{{ route('stock-movements.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                    @csrf
                    <div class="md:col-span-4">
                        <x-input-label for="ledger-item-id" value="Bahan Habis Pakai *" />
                        <select id="ledger-item-id" name="item_id" required class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                            <option value="">-- Pilih Bahan --</option>
                            @foreach($consumables as $c)
                                <option value="{{ $c->id }}" @selected(old('item_id', request('item_id')) == $c->id)>
                                    {{ $c->name }} ({{ $c->item_code }}) — Stok: {{ $c->stock }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('item_id')" class="mt-1" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="ledger-type" value="Jenis Mutasi *" />
                        <select id="ledger-type" name="type" required class="mt-1 w-full rounded-lg border-neutral-300 text-xs focus:border-primary focus:ring-primary">
                            <option value="out" @selected(old('type', 'out') === 'out')>Keluar (Pakai)</option>
                            <option value="in" @selected(old('type') === 'in')>Masuk (Tambah)</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="ledger-qty" value="Jumlah *" />
                        <x-text-input id="ledger-qty" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required class="mt-1 w-full font-mono text-xs" />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                    </div>

                    <div class="md:col-span-4">
                        <x-input-label for="ledger-recipient" value="Pengambil / Kelas / Vendor (Opsional)" />
                        <x-text-input id="ledger-recipient" name="recipient_name" type="text" value="{{ old('recipient_name') }}" placeholder="Contoh: XI SIJA 2 / Praktikum UTP" class="mt-1 w-full text-xs" />
                        <x-input-error :messages="$errors->get('recipient_name')" class="mt-1" />
                    </div>

                    <div class="md:col-span-9">
                        <x-input-label for="ledger-desc" value="Keterangan Keperluan / Sumber *" />
                        <x-text-input id="ledger-desc" name="description" type="text" value="{{ old('description') }}" required placeholder="Contoh: Praktikum terminasi kabel UTP Cat6 kelompok 3 / Pengadaan stok lab" class="mt-1 w-full text-xs" />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="md:col-span-3 md:pt-5">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Simpan Mutasi</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Tabel Ledger Riwayat Mutasi Stok -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="p-4 border-b border-neutral-200 bg-neutral-50 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <h3 class="font-bold text-neutral-900 text-sm tracking-tight">Riwayat Mutasi Stok (Stock Ledger)</h3>

                <form method="GET" action="{{ route('stock-movements.index') }}" class="flex items-center gap-2 flex-wrap">
                    <select name="item_id" aria-label="Filter Bahan" class="rounded-lg border-neutral-200 text-xs py-1.5 px-2.5 focus:border-primary focus:ring-primary">
                        <option value="">Semua Bahan</option>
                        @foreach($consumables as $c)
                            <option value="{{ $c->id }}" @selected(request('item_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <select name="type" aria-label="Filter Tipe Mutasi" class="rounded-lg border-neutral-200 text-xs py-1.5 px-2.5 focus:border-primary focus:ring-primary">
                        <option value="">Semua Tipe</option>
                        <option value="in" @selected(request('type') === 'in')>Stok Masuk (In)</option>
                        <option value="out" @selected(request('type') === 'out')>Pemakaian (Out)</option>
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition-colors">Filter</button>
                    @if(request()->hasAny(['item_id', 'type']) && (request('item_id') || request('type')))
                        <a href="{{ route('stock-movements.index') }}" class="px-3 py-1.5 bg-white hover:bg-neutral-100 text-neutral-600 border border-neutral-200 text-xs font-semibold rounded-lg transition-colors">Reset</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Bahan Habis Pakai</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Tipe</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase tracking-wider">Saldo Stok</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pengambil &amp; Keterangan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/70">
                        @forelse($movements as $mv)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-neutral-700">
                                    {{ optional($mv->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-neutral-900 text-xs">{{ $mv->item->name ?? '-' }}</div>
                                    <div class="font-mono text-[11px] text-neutral-500 mt-0.5">{{ $mv->item->item_code ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($mv->type->value === 'in')
                                        <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            MASUK
                                        </span>
                                    @else
                                        <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200">
                                            KELUAR
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold text-xs {{ $mv->type->value === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $mv->type->value === 'in' ? '+' : '-' }}{{ $mv->quantity }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap font-mono text-xs text-neutral-600">
                                    {{ $mv->stock_before }} &rarr; <span class="font-bold text-neutral-900">{{ $mv->stock_after }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($mv->recipient_name)
                                        <div class="font-semibold text-xs text-neutral-800">{{ $mv->recipient_name }}</div>
                                    @endif
                                    <div class="text-xs text-neutral-600">{{ $mv->description }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-neutral-600">
                                    {{ $mv->user->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <p class="font-semibold text-neutral-800 text-sm">Belum Ada Riwayat Mutasi Stok</p>
                                    <p class="text-xs text-neutral-500 mt-1">Gunakan formulir di atas untuk mencatat penambahan atau pemakaian bahan habis pakai.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-4 py-3 border-t border-neutral-200 bg-neutral-50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

