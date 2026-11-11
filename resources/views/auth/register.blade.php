<x-guest-layout>
    <div class="mb-5">
        <h2 class="text-lg font-bold text-white tracking-tight">{{ __('Register') }}</h2>
        <p class="text-xs text-neutral-400 mt-1">Pendaftaran akun baru sistem manajemen inventarisasi laboratorium.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Name') }}
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Email') }}
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Password') }}
            </label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Confirm Password') }}
            </label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-xs text-neutral-400 hover:text-white transition-colors" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="py-2.5 px-5 bg-primary hover:bg-primary-500 text-white font-semibold rounded-lg border border-primary/40 transition-colors text-xs">
                {{ __('Register') }}
            </button>
        </div>
    </form>
</x-guest-layout>
