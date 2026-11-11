@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-neutral-300 text-neutral-900 placeholder-neutral-400 focus:border-primary focus:ring-primary rounded-lg text-sm transition-colors']) }}>
