<x-app-layout>
    <x-slot name="heading">Catat Peminjaman Baru</x-slot>
    <x-slot name="title">Catat Peminjaman Manual</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Catat Peminjaman Manual</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Entri peminjaman alat praktikum untuk siswa secara langsung oleh administrator / laboran.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="create-loan-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Peminjaman</span>
                </button>
            </div>
        </div>

        <form id="create-loan-form" method="POST" action="{{ route('loans.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Data Siswa & Durasi Pemakaian</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Tentukan nama siswa peminjam dan batas waktu peminjaman alat.</p>
                        </div>

                        <div>
                            <x-input-label for="user_id" value="Siswa / Peminjam *" />
                            <select id="user_id" name="user_id" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                <option value="">— Pilih siswa peminjam —</option>
                                @foreach(\App\Models\User::where('role', 'siswa')->orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                        {{ $user->name }} ({{ $user->nis_nip }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="usage_duration" value="Durasi Pemakaian *" />
                            <select id="usage_duration" name="usage_duration" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                @foreach(\App\Enums\LoanDuration::cases() as $duration)
                                    <option value="{{ $duration->value }}" @selected(old('usage_duration') === $duration->value)>
                                        {{ $duration->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('usage_duration')" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                            <div>
                                <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Pilih Alat Praktikum *</h2>
                                <p class="text-xs text-neutral-500 mt-0.5">Centang satu atau beberapa alat yang berstatus tersedia untuk dipinjam sekaligus.</p>
                            </div>
                        </div>

                        <div class="border border-neutral-200 rounded-xl p-3 max-h-80 overflow-y-auto space-y-2 bg-neutral-50/50">
                            @forelse(\App\Models\Item::where('status', 'tersedia')->orderBy('name')->get() as $item)
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border border-neutral-200/80 hover:border-neutral-900 cursor-pointer text-xs transition-colors shadow-2xs">
                                    <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" 
                                        @checked(is_array(old('item_ids')) && in_array($item->id, old('item_ids')))
                                        class="rounded-md border-neutral-300 text-neutral-900 focus:ring-neutral-900 h-4 w-4">
                                    <div class="w-8 h-8 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-neutral-900 truncate">{{ $item->name }}</div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-neutral-500 font-mono">{{ $item->item_code }}</span>
                                            @if($item->epc_tag)
                                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-600 border border-neutral-200">UHF RFID</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="font-mono text-[11px] text-neutral-500 shrink-0 px-2 py-0.5 rounded bg-neutral-100 border border-neutral-200">
                                        {{ $item->location ?: 'Lab SIJA' }}
                                    </span>
                                </label>
                            @empty
                                <div class="py-8 text-center text-xs text-neutral-500">
                                    Tidak ada alat praktikum yang berstatus tersedia saat ini.
                                </div>
                            @endforelse
                        </div>
                        <x-input-error :messages="$errors->get('item_ids')" class="mt-2" />
                    </div>
                </div>

                <!-- Guidance Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Integrasi Gate UHF RFID</h3>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Begitu formulir ini disimpan, status alat dalam database berubah menjadi <span class="font-semibold text-neutral-900">dipinjam</span>.
                        </p>
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1">
                            <span class="font-semibold block">Akses Gate Diizinkan</span>
                            <p class="text-emerald-700/90 text-[11px]">Siswa yang membawa alat ini melewati gerbang lab tidak akan memicu sirine alarm gate.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center gap-2 text-neutral-900 font-semibold text-xs">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Aturan Keterlambatan</span>
                        </div>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Alat yang belum dikembalikan melewati batas waktu durasi akan otomatis bertanda <span class="font-semibold text-rose-700">Overdue</span> di dasbor dan mengirim notifikasi pengingat ke siswa.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
