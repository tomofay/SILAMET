<x-app-layout>
    <x-slot name="heading">Tambah User Baru</x-slot>
    <x-slot name="title">Tambah User</x-slot>

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
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900 font-heading">Tambah Pengguna Baru</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Registrasi akun siswa, guru, atau teknisi ke dalam sistem laboratorium.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-full border border-neutral-200 bg-white hover:bg-neutral-50 text-xs font-semibold text-neutral-700 shadow-sm transition-colors">
                    Batal
                </a>
                <button type="submit" form="create-user-form" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-neutral-900 hover:bg-neutral-800 text-xs font-semibold text-white shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Pengguna</span>
                </button>
            </div>
        </div>

        <form id="create-user-form" method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Main Form (Left 8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Informasi Dasar & Identitas</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Identitas utama pengguna yang akan dicetak pada kartu barcode dan login sistem.</p>
                        </div>

                        <div>
                            <x-input-label for="name" value="Nama Lengkap *" />
                            <x-text-input id="name" name="name" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('name') }}" required placeholder="Contoh: Muhammad Budi Pratama" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nis_nip" value="NIS / NIP *" />
                                <x-text-input id="nis_nip" name="nis_nip" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('nis_nip') }}" required placeholder="Contoh: 10220938" />
                                <p class="text-[11px] text-neutral-400 mt-1">Digunakan untuk barcode kartu Kiosk.</p>
                                <x-input-error :messages="$errors->get('nis_nip')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="role" value="Peran / Hak Akses *" />
                                <select id="role" name="role" class="mt-1.5 w-full rounded-xl border-neutral-200 text-sm focus:border-neutral-900 focus:ring-neutral-900" required>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->value }}" @selected(old('role', 'siswa') === $r->value)>{{ $r->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="email" value="Alamat Email (Opsional)" />
                                <x-text-input id="email" name="email" type="email" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('email') }}" placeholder="budi@smkn1cibinong.sch.id" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" value="No. WhatsApp (Opsional)" />
                                <x-text-input id="phone" name="phone" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('phone') }}" placeholder="081234567890" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm space-y-5">
                        <div class="border-b border-neutral-100 pb-3">
                            <h2 class="text-sm font-bold text-neutral-900 tracking-tight">Kredensial & Integrasi Notifikasi</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Pengaturan kata sandi masuk dan id kanal pengingat bot otomatis.</p>
                        </div>

                        <div>
                            <x-input-label for="password" value="Password Akun (Opsional — Default: NIS/NIP)" />
                            <x-text-input id="password" name="password" type="password" class="mt-1.5 w-full text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" placeholder="Kosongkan untuk otomatis menggunakan nomor NIS/NIP" />
                            <p class="text-[11px] text-neutral-400 mt-1">Bila dikosongkan, pengguna masuk pertama kali menggunakan NIS/NIP terdaftar.</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telegram_chat_id" value="ID Chat Telegram (Opsional)" />
                            <x-text-input id="telegram_chat_id" name="telegram_chat_id" class="mt-1.5 w-full font-mono text-sm rounded-xl border-neutral-200 focus:border-neutral-900 focus:ring-neutral-900" value="{{ old('telegram_chat_id') }}" placeholder="Contoh: 123456789" />
                            <p class="text-[11px] text-neutral-400 mt-1">Untuk pengingat jatuh tempo peminjaman privat via bot SILAMET.</p>
                            <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Guidance Sidebar (Right 4 Cols) -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Panduan Registrasi</h3>
                        <div class="space-y-3 text-xs text-neutral-600 leading-relaxed">
                            <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200/80 space-y-1">
                                <span class="font-semibold text-neutral-900 block">Siswa</span>
                                <p class="text-neutral-500">Hanya dapat meminjam alat melalui Kiosk Mandiri dan melihat status peminjamannya.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200/80 space-y-1">
                                <span class="font-semibold text-neutral-900 block">Teknisi Lab</span>
                                <p class="text-neutral-500">Dapat memproses approval pengembalian, mencatat tiket perbaikan M&R, dan log inventaris.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200/80 space-y-1">
                                <span class="font-semibold text-neutral-900 block">Kepala Bengkel / Admin</span>
                                <p class="text-neutral-500">Akses penuh ke seluruh konfigurasi gate RFID, master aset, dan ekspor laporan resmi.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 shadow-sm space-y-2.5">
                        <div class="flex items-center gap-2 text-neutral-900 font-semibold text-xs">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Kartu Identitas Otomatis</span>
                        </div>
                        <p class="text-xs text-neutral-600 leading-relaxed">
                            Setelah disimpan, barcode token unik akan dibuat otomatis dan dapat langsung dicetak lewat menu <span class="font-semibold text-neutral-800">Cetak Kartu User</span>.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
