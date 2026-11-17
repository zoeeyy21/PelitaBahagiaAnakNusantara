@php
    $image = $activity->cover_image
        ? (str_starts_with($activity->cover_image, 'http') ? $activity->cover_image : \Illuminate\Support\Facades\Storage::url($activity->cover_image))
        : 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1200&q=80';
@endphp

<x-public-layout :title="$activity->title" :description="$activity->excerpt">
    <article class="bg-white">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Kegiatan</p>
            <h1 class="mt-3 text-4xl font-extrabold text-primary-950">{{ $activity->title }}</h1>
            <p class="mt-4 text-slate-500">{{ $activity->dateRangeLabel('d F Y') ?? $activity->published_at?->translatedFormat('d F Y') }}</p>
            @if ($activity->program?->isPubliclyVisible())
                <p class="mt-2 text-sm text-slate-600">
                    Bagian dari program
                    <a href="{{ route('programs.show', $activity->program) }}" class="font-semibold text-primary-700">{{ $activity->program->title }}</a>
                </p>
            @endif
            <img class="mt-8 h-[420px] w-full rounded-lg object-cover" src="{{ $image }}" alt="{{ $activity->title }}">
            <div class="mt-8 whitespace-pre-line text-lg leading-9 text-slate-700">{{ $activity->body }}</div>

            @if ($activity->galleryItems->isNotEmpty())
                <section class="mt-12">
                    <h2 class="text-2xl font-bold text-slate-950">Dokumentasi</h2>
                    <div class="mt-6">
                        @include('public.partials.gallery-grid', ['items' => $activity->galleryItems])
                    </div>
                </section>
            @endif
        </div>
    </article>
</x-public-layout>
