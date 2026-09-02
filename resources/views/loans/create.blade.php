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
                <x-input-label for="item_id" value="Barang *" />
                <select id="item_id" name="item_id" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    <option value="">— Pilih barang tersedia —</option>
                    @foreach(\App\Models\Item::where('status', 'tersedia')->orderBy('name')->get() as $item)
                        <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>
                            {{ $item->name }} ({{ $item->item_code }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
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
