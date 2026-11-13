<x-app-layout>
    <x-slot name="heading">Manajemen Pengguna</x-slot>
    <x-slot name="title">Manajemen User — SILAMET</x-slot>

    <div class="space-y-6 max-w-7xl mx-auto">
        
        <!-- Header Actions (Dribbble Orbix Studio Header) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900 font-heading">
                    Manajemen Pengguna
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    Kelola data siswa peminjam, teknisi operasional, kepala bengkel (kabeng), dan kepala program (kaprog).
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('users.cards') }}?ids={{ $users->pluck('id')->implode(',') }}" target="_blank"
                    class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    <span>Cetak Kartu Siswa</span>
                </a>
                <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden'); document.getElementById('import-modal').classList.add('flex');"
                    class="px-4 py-2 rounded-full border border-emerald-600 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span>Import Excel</span>
                </button>
                <a href="{{ route('users.create') }}" class="px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors shadow-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah User</span>
                </a>
            </div>
        </div>

        <!-- Search & Filter Card (Pill Inputs) -->
        <div class="bg-white rounded-2xl p-5 border border-neutral-200 shadow-sm">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama, NIS/NIP, atau email..."
                        class="w-full pl-9 pr-4 py-2 rounded-full border border-neutral-200 text-xs focus:ring-1 focus:ring-primary focus:border-primary transition-colors shadow-sm" />
                </div>
                <select name="role" aria-label="Filter Role" class="rounded-full border border-neutral-200 bg-white text-xs px-4 py-2 focus:ring-1 focus:ring-primary focus:border-primary w-full sm:w-44 shadow-sm">
                    <option value="">Semua Role</option>
                    @foreach(\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-5 py-2 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold rounded-full shadow-sm transition-colors" title="Terapkan Filter" aria-label="Terapkan Filter">
                    Terapkan
                </button>
                @if(request('search') || request('role'))
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-full hover:bg-neutral-50 flex items-center justify-center transition-colors" title="Reset Filter" aria-label="Reset Filter">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulk-actions" class="hidden bg-primary-50 border border-indigo-200 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-sm">
            <span class="text-xs font-semibold text-primary-700">
                <span id="selected-count" class="font-mono font-bold text-sm">0</span> user dipilih
            </span>
            <button id="btn-print-cards" type="button" class="px-4 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white font-semibold text-xs rounded-full transition-colors flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu Siswa Terpilih</span>
            </button>
        </div>

        <!-- Tabel User (Rounded-2xl Table ala Dribbble) -->
        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50/70 border-b border-neutral-200/80">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider w-10">
                                <input type="checkbox" id="select-all" aria-label="Pilih semua pengguna" class="rounded border-neutral-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Nama Lengkap</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">NIS / NIP</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Email Akun</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Kontak WA</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Peran (Role)</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-5 py-4">
                                    <input type="checkbox" aria-label="Pilih {{ $user->name }}" class="user-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $user->id }}">
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 font-bold text-neutral-700 text-xs font-heading">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="font-bold text-neutral-900 text-xs font-heading">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded-md bg-neutral-100 font-mono text-xs text-neutral-800 font-semibold border border-neutral-200">
                                        {{ $user->nis_nip ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-neutral-600">{{ $user->email ?? '—' }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-neutral-600 whitespace-nowrap">
                                    <div>{{ $user->phone ?? '—' }}</div>
                                    @if($user->telegram_chat_id)
                                        <div class="text-[10px] text-primary-600 mt-0.5">TG: {{ $user->telegram_chat_id }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @php
                                        $badge = match($user->role?->value) {
                                            'siswa' => 'bg-primary-50 text-primary-700 border-indigo-200',
                                            'teknisi' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'kabeng' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'kaprog' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                        };
                                    @endphp
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $badge }}">{{ $user->role?->label() ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a href="{{ route('users.cards') }}?ids={{ $user->id }}" target="_blank" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Cetak Kartu Siswa" aria-label="Cetak Kartu {{ $user->name }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('users.edit', $user->id) }}" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-600 hover:text-neutral-900 transition-colors shadow-sm" title="Ubah Pengguna" aria-label="Edit {{ $user->name }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-rose-50 text-neutral-600 hover:text-rose-600 hover:border-rose-200 transition-colors shadow-sm" title="Hapus Pengguna" aria-label="Hapus {{ $user->name }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-14 h-14 rounded-2xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3 shadow-inner">
                                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-heading font-bold text-neutral-900 text-sm">Tidak Ada Data Pengguna</h3>
                                        <p class="text-xs text-neutral-500 mt-1 text-center">
                                            Coba gunakan kata kunci pencarian yang lain atau tambahkan user baru.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-5 py-3.5 border-t border-neutral-200/80 bg-neutral-50/50">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- Modal Import Excel (Rounded-2xl Container) --}}
    <div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-neutral-950/65" onclick="this.parentElement.classList.add('hidden'); this.parentElement.classList.remove('flex');"></div>
        <div class="relative bg-white border border-neutral-200 rounded-2xl max-w-md w-full p-6 text-neutral-800 z-10 shadow-xl">
            <div class="flex items-center justify-between pb-3.5 border-b border-neutral-200">
                <h3 class="font-bold text-neutral-900 text-base font-heading">Import Data Pengguna (Excel)</h3>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex');" class="p-1.5 text-neutral-400 hover:text-neutral-700 rounded-lg" aria-label="Tutup modal">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Pilih File Spreadsheet (.xlsx / .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-xs text-neutral-600 file:mr-3 file:py-2 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-neutral-100 file:text-neutral-800 hover:file:bg-neutral-200 cursor-pointer border border-neutral-200 rounded-xl" />
                </div>
                <div class="p-4 bg-neutral-50 rounded-xl text-xs border border-neutral-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-neutral-800">Format Kolom Spreadsheet:</span>
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-mono font-semibold text-[10px]">.xlsx / .csv</span>
                    </div>
                    <div class="font-mono text-[11px] text-neutral-700 bg-white p-2.5 rounded-lg border border-neutral-200">
                        nama | nis_nip | email | phone | role
                    </div>
                    <p class="text-[11px] text-neutral-500">
                        Password otomatis dibuat sama dengan NIS/NIP masing-masing pengguna.
                    </p>
                    <a href="{{ route('users.download-template') }}" class="inline-flex items-center gap-1.5 mt-1 font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-full border border-emerald-200 transition-colors">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Unduh Template Excel (.xlsx)</span>
                    </a>
                </div>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex');" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:bg-neutral-50 border border-neutral-200 rounded-full">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-600 text-white font-semibold text-xs rounded-full border border-emerald-600 transition-colors shadow-sm">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const bulkBar = document.getElementById('bulk-actions');
            const countLabel = document.getElementById('selected-count');
            const btnPrint = document.getElementById('btn-print-cards');

            function updateBulkBar() {
                const checked = Array.from(checkboxes).filter(cb => cb.checked);
                countLabel.textContent = checked.length;
                if (checked.length > 0) {
                    bulkBar.classList.remove('hidden');
                } else {
                    bulkBar.classList.add('hidden');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    updateBulkBar();
                    if (selectAll) {
                        selectAll.checked = Array.from(checkboxes).every(c => c.checked);
                    }
                });
            });

            if (btnPrint) {
                btnPrint.addEventListener('click', function () {
                    const ids = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                    if (ids.length > 0) {
                        window.open("{{ route('users.cards') }}?ids=" + ids.join(','), '_blank');
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>