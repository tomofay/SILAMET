<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-lg font-bold text-white tracking-tight">Login Petugas Laboratorium</h2>
        <p class="text-xs text-neutral-400 mt-1">Akses khusus Teknisi, Kepala Bengkel (Kabeng), dan Kepala Program (Kaprog).</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- NIS/NIP or Email -->
        <div>
            <label for="login" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                NIP / NIS / Email
            </label>
            <input 
                id="login" 
                type="text" 
                name="login" 
                value="{{ old('login') }}" 
                required 
                autofocus 
                autocomplete="username" 
                placeholder="Contoh: 197501012000121001 atau kaprog@silamet.sch.id"
                class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
            />
            <x-input-error :messages="$errors->get('login')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                Kata Sandi
            </label>
            <input 
                id="password" 
                type="password" 
                name="password" 
                required 
                autocomplete="current-password" 
                placeholder="Masukkan kata sandi"
                class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between text-xs pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-neutral-700 bg-neutral-950 text-primary focus:ring-primary focus:ring-offset-neutral-900" name="remember">
                <span class="text-neutral-400 select-none">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-neutral-400 hover:text-white transition-colors" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-500 text-white font-semibold rounded-lg border border-primary/40 transition-colors text-sm flex items-center justify-center gap-2">
                <span>Masuk ke Dashboard</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </form>
</x-guest-layout>
