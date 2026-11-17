@props(['mode'])

<div class="inline-flex rounded-full border border-stone-200 bg-white p-1 text-sm font-semibold" role="group" aria-label="Pilih tampilan">
    @foreach (['card' => 'Card', 'list' => 'List'] as $option => $label)
        <a href="{{ request()->fullUrlWithQuery(['view' => $option, 'page' => null]) }}"
            @class(['inline-flex items-center gap-2 rounded-full px-4 py-1.5', 'bg-primary-700 text-white' => $mode === $option, 'text-stone-600 hover:text-primary-700' => $mode !== $option])
            @if ($mode === $option) aria-current="true" @endif>
            @if ($option === 'card')
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 3h6v6H3zM11 3h6v6h-6zM3 11h6v6H3zM11 11h6v6h-6z"/></svg>
            @else
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4h14v2H3zM3 9h14v2H3zM3 14h14v2H3z"/></svg>
            @endif
            {{ $label }}
        </a>
    @endforeach
</div>
