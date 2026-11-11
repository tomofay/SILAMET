<x-guest-layout>
    <div class="mb-4 text-xs text-neutral-400 leading-relaxed">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-xs text-emerald-400 bg-emerald-950/60 border border-emerald-800/60 rounded-lg px-3 py-2">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="py-2.5 px-4 bg-primary hover:bg-primary-500 text-white font-semibold rounded-lg border border-primary/40 transition-colors text-xs">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-xs text-neutral-400 hover:text-white transition-colors px-2 py-1.5">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
