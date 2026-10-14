<x-app-layout>
    <x-slot name="heading">Manajemen User</x-slot>
    <x-slot name="title">Manajemen User</x-slot>

    <div class="space-y-4">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-neutral-500 text-sm">Kelola akun siswa, teknisi, kabeng & kaprog</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark transition">
                    + Tambah User
                </a>
                <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden'); document.getElementById('import-modal').classList.add('flex');"
                    class="inline-flex items-center px-4 py-2 bg-success text-white text-sm font-medium rounded-md hover:bg-success-dark transition">
                    📥 Import Excel
                </button>
                <a href="{{ route('users.cards') }}?ids={{ $users->pluck('id')->implode(',') }}" target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-info text-white text-sm font-medium rounded-md hover:bg-info-dark transition">
                    🪪 Cetak Kartu
                </a>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS/NIP, email..."
                    class="flex-1 rounded-md border-neutral-300 shadow-sm text-sm focus:border-primary focus:ring-primary" />
                <select name="role" class="rounded-md border-neutral-300 shadow-sm text-sm focus:border-primary focus:ring-primary w-full sm:w-40">
                    <option value="">Semua Role</option>
                    @foreach(\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-neutral-800 text-white text-sm rounded-md hover:bg-neutral-700 transition">Cari</button>
                @if(request('search') || request('role'))
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Reset</a>
                @endif
            </form>
        </div>

        {{-- Tabel User --}}
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all" class="rounded border-neutral-300 text-primary focus:ring-primary">
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Nama</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">NIS/NIP</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Telp</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-neutral-500 uppercase tracking-wider">Role</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-neutral-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <input type="checkbox" class="user-checkbox rounded border-neutral-300 text-primary focus:ring-primary" value="{{ $user->id }}">
                                </td>
                                <td class="px-5 py-3 font-medium text-neutral-800">{{ $user->name }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-neutral-600">{{ $user->nis_nip ?? '—' }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ $user->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-neutral-600">{{ $user->phone ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $badge = match($user->role?->value) {
                                            'siswa' => 'bg-primary/10 text-primary-dark',
                                            'teknisi' => 'bg-info/10 text-info-dark',
                                            'kabeng' => 'bg-success/10 text-success-dark',
                                            'kaprog' => 'bg-warning/10 text-warning-dark',
                                            default => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $user->role?->label() ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('users.cards') }}?ids={{ $user->id }}" target="_blank" class="text-info-dark hover:underline text-xs" title="Cetak Kartu">🪪</a>
                                    <a href="{{ route('users.edit', $user->id) }}" class="text-info-dark hover:underline text-xs">Edit</a>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger hover:underline text-xs">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-neutral-400">Belum ada user. Klik "Tambah User" atau "Import Excel".</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- Tombol cetak kartu terpilih --}}
        <div id="bulk-actions" class="hidden bg-primary/5 border border-primary/20 rounded-lg p-3 flex items-center gap-3">
            <span class="text-sm text-primary-dark font-medium"><span id="selected-count">0</span> user dipilih</span>
            <a id="print-selected-btn" href="#" target="_blank"
                class="inline-flex items-center px-3 py-1.5 bg-info text-white text-xs font-medium rounded-md hover:bg-info-dark transition">
                🪪 Cetak Kartu Terpilih
            </a>
        </div>
    </div>

    {{-- Modal Import Excel --}}
    <div id="import-modal" class="fixed inset-0 z-40 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex');"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-neutral-800">📥 Import User dari Excel</h3>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex');"
                    class="text-neutral-500 hover:text-neutral-800 text-xl leading-none">&times;</button>
            </div>

            <div class="mb-4 bg-info/10 border border-info/30 rounded-md p-3 text-xs text-info-dark">
                <p class="font-semibold mb-1">Format kolom Excel/CSV:</p>
                <code class="block bg-white rounded px-2 py-1 mt-1">nama | nis_nip | email | phone | role</code>
                <p class="mt-2">Role: <span class="font-mono">siswa</span>, <span class="font-mono">teknisi</span>, <span class="font-mono">kabeng</span>, <span class="font-mono">kaprog</span></p>
                <p class="mt-1">Password default = NIS/NIP pengguna.</p>
            </div>

            <a href="{{ route('users.download-template') }}" class="inline-flex items-center text-xs text-primary hover:underline mb-4">
                📄 Download Template CSV
            </a>

            <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Pilih File (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                        class="block w-full text-sm text-neutral-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary-dark file:cursor-pointer" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex');"
                        class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-success text-white text-sm font-medium rounded-md hover:bg-success-dark">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');
            const printBtn = document.getElementById('print-selected-btn');

            function updateBulk() {
                const checked = document.querySelectorAll('.user-checkbox:checked');
                const count = checked.length;
                selectedCount.textContent = count;
                bulkActions.classList.toggle('hidden', count === 0);

                if (count > 0) {
                    const ids = Array.from(checked).map(c => c.value).join(',');
                    printBtn.href = '{{ route("users.cards") }}?ids=' + ids;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulk();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateBulk));
        });
    </script>
    @endpush
</x-app-layout>
