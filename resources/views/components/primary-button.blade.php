<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-primary border border-primary/40 rounded-lg font-semibold text-xs text-white tracking-tight hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors']) }}>
    {{ $slot }}
</button>
