@props([
    'seed' => 0,
    'badge' => null,
    'headline' => null,
    'subline' => null,
    'cover' => null,
    'title' => null,
    'muted' => false,
    'compact' => false,
])

@php
    $palettes = [
        ['header' => 'from-[#e3a07a] to-primary-700', 'accent' => 'text-primary-600'],
        ['header' => 'from-[#5fa58c] to-[#1d5444]', 'accent' => 'text-[#2f7a62]'],
        ['header' => 'from-[#ecc95c] to-[#d6a12b]', 'accent' => 'text-[#b5862a]'],
    ];
    $palette = $muted
        ? ['header' => 'from-stone-300 to-stone-500', 'accent' => 'text-stone-500']
        : $palettes[abs((int) $seed) % count($palettes)];
    $coverUrl = $cover
        ? (str_starts_with($cover, 'http') ? $cover : \Illuminate\Support\Facades\Storage::url($cover))
        : null;
@endphp

<article {{ $attributes->class('group card-interactive flex flex-col overflow-hidden rounded-3xl border border-stone-100 bg-white shadow-[0_8px_30px_rgba(120,90,60,0.08)]') }}>
    <div @class(['relative overflow-hidden bg-gradient-to-b', $palette['header'], 'h-36' => $compact, 'h-56' => ! $compact])>
        @if ($coverUrl)
            <img class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105" src="{{ $coverUrl }}" alt="" loading="lazy">
            <span class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-black/10"></span>
        @else
            <span @class(['absolute rounded-full bg-white transition duration-700 ease-out group-hover:-translate-x-2 group-hover:translate-y-1', '-right-8 -top-20 size-36' => $compact, '-right-10 -top-24 size-48' => ! $compact])></span>
            <span @class(['absolute rounded-full bg-white transition duration-700 ease-out group-hover:translate-x-2', '-bottom-20 -left-14 size-32' => $compact, '-bottom-28 -left-16 size-44' => ! $compact])></span>
        @endif

        @if ($badge)
            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide {{ $palette['accent'] }}">{{ $badge }}</span>
        @endif

        @if ($headline)
            <div @class(['absolute inset-x-0 px-4 text-white', 'bottom-4' => $coverUrl, 'top-[40%]' => ! $coverUrl && $compact, 'top-[34%]' => ! $coverUrl && ! $compact])>
                <p class="truncate text-xl font-bold drop-shadow-sm">{{ $headline }}</p>
                @if ($subline)
                    <p class="mt-1 truncate text-xs text-white/90">{{ $subline }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col px-7 pb-6 pt-7">
        @if ($title)
            <h3 class="text-2xl font-bold leading-snug text-stone-900">{{ $title }}</h3>
        @endif
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center justify-between gap-3 border-t border-stone-100 px-6 py-5">
            {{ $footer }}
        </div>
    @endisset
</article>
