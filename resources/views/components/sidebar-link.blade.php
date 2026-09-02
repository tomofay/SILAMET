@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full px-3 py-2 rounded-md text-white bg-white/15 font-medium transition duration-150 ease-in-out'
            : 'block w-full px-3 py-2 rounded-md text-white/75 hover:text-white hover:bg-white/10 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
