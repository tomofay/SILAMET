<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-white border border-neutral-200 rounded-lg font-semibold text-xs text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-25 transition-colors']) }}>
    {{ $slot }}
</button>
