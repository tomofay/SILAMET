<x-app-layout>
    <x-slot name="heading">Tambah User Baru</x-slot>
    <x-slot name="title">Tambah User</x-slot>

    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Tambah User Baru</h2>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" value="Nama Lengkap *" />
                <x-text-input id="name" name="name" class="mt-1 w-full" value="{{ old('name') }}" required placeholder="contoh: Budi Santoso" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="nis_nip" value="NIS / NIP *" />
                    <x-text-input id="nis_nip" name="nis_nip" class="mt-1 w-full" value="{{ old('nis_nip') }}" required placeholder="contoh: 12345 atau NIP001" />
                    <x-input-error :messages="$errors->get('nis_nip')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="role" value="Role *" />
                    <select id="role" name="role" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}" @selected(old('role', 'siswa') === $r->value)>{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="email" value="Email (opsional)" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 w-full" value="{{ old('email') }}" placeholder="contoh: budi@email.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="phone" value="No. Telp (opsional)" />
                    <x-text-input id="phone" name="phone" class="mt-1 w-full" value="{{ old('phone') }}" placeholder="contoh: 08123456789" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="password" value="Password (opsional — default = NIS/NIP)" />
                <x-text-input id="password" name="password" type="password" class="mt-1 w-full" placeholder="Kosongkan untuk default NIS/NIP" />
                <p class="text-xs text-neutral-400 mt-1">Jika dikosongkan, password akan diisi otomatis dengan NIS/NIP.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan</button>
            </div>
        </form>
    </div>
</x-app-layout>
