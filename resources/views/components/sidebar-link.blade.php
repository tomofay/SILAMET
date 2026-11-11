@props(['active'])

@php
$classes = ($active ?? false)
            ? 'group flex items-center w-full px-3 py-2 rounded-lg text-white bg-primary font-semibold border border-primary/40 transition-colors duration-150'
            : 'group flex items-center w-full px-3 py-2 rounded-lg text-neutral-400 hover:text-neutral-100 hover:bg-neutral-800/70 border border-transparent font-medium transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
