<x-app-layout>
    <x-slot name="heading">Catat Peminjaman Baru</x-slot>
    <x-slot name="title">Catat Peminjaman</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('loans.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Peminjaman</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">Formulir Peminjaman Manual</h2>

            <form method="POST" action="{{ route('loans.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="user_id" value="Siswa / Peminjam *" />
                    <select id="user_id" name="user_id" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="">— Pilih siswa —</option>
                        @foreach(\App\Models\User::where('role', 'siswa')->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                {{ $user->name }} ({{ $user->nis_nip }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="item_ids" value="Pilih Barang (Bisa Lebih dari 1) *" />
                    <div class="mt-1 border border-neutral-200 rounded-lg p-2.5 max-h-60 overflow-y-auto space-y-1.5 bg-neutral-50">
                        @forelse(\App\Models\Item::where('status', 'tersedia')->orderBy('name')->get() as $item)
                            <label class="flex items-center gap-3 p-2.5 bg-white rounded-lg border border-neutral-200 hover:border-primary hover:bg-primary-50/40 cursor-pointer text-xs transition-colors">
                                <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" 
                                    @checked(is_array(old('item_ids')) && in_array($item->id, old('item_ids')))
                                    class="rounded border-neutral-300 text-primary focus:ring-primary h-4 w-4">
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold text-neutral-900">{{ $item->name }}</span>
                                    <span class="text-[11px] text-neutral-500 font-mono ml-1.5 px-1.5 py-0.5 rounded bg-neutral-100 border border-neutral-200">{{ $item->item_code }}</span>
                                </div>
                                <span class="font-mono text-[11px] text-neutral-500 shrink-0">{{ $item->location }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-neutral-500 py-4 text-center">Tidak ada barang yang berstatus tersedia.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('item_ids')" class="mt-2" />
                    <p class="text-[11px] text-neutral-500 mt-1">Centang satu atau lebih barang yang akan dipinjam sekaligus.</p>
                </div>

                <div>
                    <x-input-label for="usage_duration" value="Durasi Pemakaian *" />
                    <select id="usage_duration" name="usage_duration" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                        @foreach(\App\Enums\LoanDuration::cases() as $duration)
                            <option value="{{ $duration->value }}" @selected(old('usage_duration') === $duration->value)>
                                {{ $duration->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('usage_duration')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('loans.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
