@props(['title', 'description' => null, 'back' => null])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="group mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 transition hover:text-primary-700">
                <svg class="size-4 transition group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 0 1 0 1.06L8.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
                Kembali
            </a>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 text-sm text-stone-500">{{ $description }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
