<x-guest-layout>
    <div class="mb-5">
        <h2 class="text-lg font-bold text-white tracking-tight">{{ __('Reset Password') }}</h2>
        <p class="text-xs text-neutral-400 mt-1">Atur ulang kata sandi akun petugas laboratorium Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Email') }}
            </label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
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

        <div class="flex items-center justify-end pt-2">
            <button type="submit" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-500 text-white font-semibold rounded-lg border border-primary/40 transition-colors text-sm">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
</x-guest-layout>
