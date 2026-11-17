@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl bg-primary-50 px-4 py-2.5 text-start text-base font-semibold text-primary-700 transition'
            : 'block w-full rounded-xl px-4 py-2.5 text-start text-base font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
