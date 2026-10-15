<x-app-layout>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Catat Peminjaman Baru</h2>

        <form method="POST" action="{{ route('loans.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="user_id" value="Siswa / Peminjam *" />
                <select id="user_id" name="user_id" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
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
                <x-input-label for="item_ids" value="Pilih Barang (Bisa Pilih Lebih dari 1) *" />
                <div class="mt-1 border border-neutral-300 rounded-md p-3 max-h-56 overflow-y-auto space-y-2 bg-neutral-50/50">
                    @forelse(\App\Models\Item::where('status', 'tersedia')->orderBy('name')->get() as $item)
                        <label class="flex items-center gap-3 p-2 bg-white rounded border border-neutral-200 hover:border-primary hover:bg-primary/5 cursor-pointer text-sm">
                            <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" 
                                @checked(is_array(old('item_ids')) && in_array($item->id, old('item_ids')))
                                class="rounded border-neutral-300 text-primary focus:ring-primary h-4 w-4">
                            <div class="flex-1">
                                <span class="font-medium text-neutral-800">{{ $item->name }}</span>
                                <span class="text-xs text-neutral-500 font-mono ml-1">({{ $item->item_code }})</span>
                            </div>
                            <span class="text-xs text-neutral-500">{{ $item->location }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-neutral-400 py-3 text-center">Tidak ada barang yang berstatus tersedia.</p>
                    @endforelse
                </div>
                <x-input-error :messages="$errors->get('item_ids')" class="mt-2" />
                <p class="text-xs text-neutral-500 mt-1">Centang satu atau lebih barang yang akan dipinjam sekaligus.</p>
            </div>

            <div>
                <x-input-label for="usage_duration" value="Durasi Pemakaian *" />
                <select id="usage_duration" name="usage_duration" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    @foreach(\App\Enums\LoanDuration::cases() as $duration)
                        <option value="{{ $duration->value }}" @selected(old('usage_duration') === $duration->value)>
                            {{ $duration->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('usage_duration')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan Peminjaman</button>
            </div>
        </form>
    </div>
</x-app-layout>
