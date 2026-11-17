@props(['path' => null, 'alt' => ''])

@php
    $url = $path ? (str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path)) : null;
@endphp

@if ($url)
    <img {{ $attributes->class('size-12 shrink-0 rounded-lg object-cover') }} src="{{ $url }}" alt="{{ $alt }}" loading="lazy">
@else
    <span {{ $attributes->class('flex size-12 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-stone-400') }}>
        <x-icon name="photo" class="size-5" />
    </span>
@endif
