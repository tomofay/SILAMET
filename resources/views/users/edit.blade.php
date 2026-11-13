<x-app-layout>
    <x-slot name="heading">Edit User — {{ $user->name }}</x-slot>
    <x-slot name="title">Edit User — {{ $user->name }}</x-slot>

    <div class="space-y-6">
        <!-- Action Header Bar (Orbix Studio style) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-neutral-200/80">
            <div class="flex items-center gap-3">
                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 bg-white text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Edit Pengguna: {{ $user->name }}</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Perbarui profil, nomor telepon, kredensial, atau peran pengguna sistem.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="edit-user-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Perbarui Pengguna</span>
                </button>
            </div>
        </div>

        <form id="edit-user-form" method="POST" action="{{ route('users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Informasi Dasar & Identitas</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Data pokok yang terhubung dengan akun laboratorium.</p>
                        </div>

                        <div>
                            <x-input-label for="name" value="Nama Lengkap *" />
                            <x-text-input id="name" name="name" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('name', $user->name) }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nis_nip" value="NIS / NIP *" />
                                <x-text-input id="nis_nip" name="nis_nip" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('nis_nip', $user->nis_nip) }}" required />
                                <x-input-error :messages="$errors->get('nis_nip')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="role" value="Peran / Hak Akses *" />
                                <select id="role" name="role" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->value }}" @selected(old('role', $user->role?->value) === $r->value)>{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="email" value="Alamat Email" />
                                <x-text-input id="email" name="email" type="email" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('email', $user->email) }}" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" value="No. WhatsApp" />
                                <x-text-input id="phone" name="phone" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('phone', $user->phone) }}" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Kredensial & Integrasi Notifikasi</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Ubah kata sandi akun atau hubungkan dengan bot Telegram.</p>
                        </div>

                        <div>
                            <x-input-label for="password" value="Password Baru (Opsional)" />
                            <x-text-input id="password" name="password" type="password" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" placeholder="Kosongkan jika tidak ingin mengubah kata sandi" />
                            <p class="text-[11px] text-neutral-400 mt-1">Biarkan kosong agar kata sandi tetap seperti sebelumnya.</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telegram_chat_id" value="ID Chat Telegram (Opsional)" />
                            <x-text-input id="telegram_chat_id" name="telegram_chat_id" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}" placeholder="Contoh: 123456789" />
                            <p class="text-[11px] text-neutral-400 mt-1">Otomatis terisi bila pengguna mengirim <span class="font-mono">/daftar {{ $user->nis_nip ?? 'NIS' }}</span> ke bot Telegram SILAMET.</p>
                            <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Guidance / Profile Summary Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ringkasan Akun</h3>
                        <div class="flex items-center gap-3 pb-3 border-b border-neutral-100">
                            <div class="w-12 h-12 rounded-full bg-neutral-900 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-neutral-900 text-sm truncate">{{ $user->name }}</h4>
                                <span class="font-mono text-xs text-neutral-500">{{ $user->nis_nip ?? '—' }}</span>
                            </div>
                        </div>

                        <dl class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500">Status Akun</dt>
                                <dd class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Aktif</span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500">Terdaftar Sejak</dt>
                                <dd class="font-mono text-neutral-900">{{ optional($user->created_at)->format('d M Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-neutral-500">Peminjaman Selesai</dt>
                                <dd class="font-mono font-semibold text-neutral-900">{{ $user->loans()->where('status', 'selesai')->count() }} kali</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center gap-2 text-neutral-900 font-semibold text-xs">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <span>Keamanan Akses</span>
                        </div>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Pastikan peran (Role) yang diberikan sesuai dengan tupoksi pengguna di laboratorium demi menjaga integritas data gate dan audit peminjaman.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
