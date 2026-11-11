<x-app-layout>
    <x-slot name="heading">Edit User — {{ $user->name }}</x-slot>
    <x-slot name="title">Edit User</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar User</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">Edit User: {{ $user->name }}</h2>

            <form method="POST" action="{{ route('users.update', $user->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Nama Lengkap *" />
                    <x-text-input id="name" name="name" class="mt-1 w-full text-sm" value="{{ old('name', $user->name) }}" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nis_nip" value="NIS / NIP *" />
                        <x-text-input id="nis_nip" name="nis_nip" class="mt-1 w-full font-mono text-sm" value="{{ old('nis_nip', $user->nis_nip) }}" required />
                        <x-input-error :messages="$errors->get('nis_nip')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="role" value="Role *" />
                        <select id="role" name="role" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->value }}" @selected(old('role', $user->role?->value) === $r->value)>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 w-full text-sm" value="{{ old('email', $user->email) }}" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="No. Telp / WhatsApp" />
                        <x-text-input id="phone" name="phone" class="mt-1 w-full font-mono text-sm" value="{{ old('phone', $user->phone) }}" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="telegram_chat_id" value="ID Chat Telegram (opsional)" />
                    <x-text-input id="telegram_chat_id" name="telegram_chat_id" class="mt-1 w-full font-mono text-sm" value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}" placeholder="Contoh: 123456789" />
                    <p class="text-[11px] text-neutral-500 mt-1">Otomatis terisi bila pengguna mengirim <span class="font-mono">/daftar {{ $user->nis_nip ?? 'NIS' }}</span> ke bot Telegram SILAMET.</p>
                    <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Password Baru (opsional)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 w-full text-sm" placeholder="Kosongkan jika tidak ingin mengubah" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Perbarui Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
