<x-guest-layout>
    <div class="mb-4 text-xs text-neutral-400 leading-relaxed">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold text-neutral-300 mb-1.5 uppercase tracking-wider">
                {{ __('Password') }}
            </label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="block w-full px-3.5 py-2.5 rounded-lg bg-neutral-950 border border-neutral-800 text-white placeholder-neutral-500 text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-400" />
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="py-2.5 px-5 bg-primary hover:bg-primary-500 text-white font-semibold rounded-lg border border-primary/40 transition-colors text-xs">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</x-guest-layout>
