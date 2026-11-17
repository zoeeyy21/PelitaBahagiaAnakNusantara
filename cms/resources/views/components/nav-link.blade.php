@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-primary-50 px-3.5 py-2 text-sm font-semibold text-primary-700 transition duration-200'
            : 'inline-flex items-center rounded-full px-3.5 py-2 text-sm font-medium text-stone-600 transition duration-200 hover:bg-stone-100 hover:text-stone-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
