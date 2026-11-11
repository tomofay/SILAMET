<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-rose-600 border border-rose-700/40 rounded-lg font-semibold text-xs text-white hover:bg-rose-700 active:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500/30 transition-colors']) }}>
    {{ $slot }}
</button>
