@php
    $activityDate = $activity->dateRangeLabel('d F Y') ?? $activity->published_at?->translatedFormat('d F Y');
@endphp

<x-content-card :seed="$activity->id" badge="Kegiatan" :headline="$activity->title" :subline="$activityDate" :cover="$activity->cover_image" :title="$activity->title">
    <p class="mt-3 text-[15px] leading-6 text-stone-500">{{ $activity->excerpt }}</p>

    @if ($activity->program?->isPubliclyVisible())
        <x-card-note label="Program:">{{ $activity->program->title }}</x-card-note>
    @endif

    <x-slot:footer>
        <span class="text-sm text-stone-500">{{ $activityDate }}</span>
        <a href="{{ route('activities.show', $activity) }}" class="rounded-full bg-primary-700 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-primary-700/30 hover:bg-primary-800">Baca Kegiatan</a>
    </x-slot:footer>
</x-content-card>
