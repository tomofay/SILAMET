@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-xs text-neutral-700 uppercase tracking-wider']) }}>
    {{ $value ?? $slot }}
</label>
