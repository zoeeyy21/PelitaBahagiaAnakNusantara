<x-public-layout title="Kegiatan">
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Kegiatan</p>
            <h1 class="mt-3 text-4xl font-extrabold text-primary-950">Berita dan dokumentasi kegiatan</h1>
        </div>
        <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($activities as $activity)
                @include('public.partials.activity-card', ['activity' => $activity])
            @empty
                <p class="rounded-3xl border border-stone-100 bg-white p-6 text-center text-stone-500 sm:col-span-2 lg:col-span-3">Belum ada kegiatan yang dipublikasikan.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $activities->links() }}</div>
    </section>
</x-public-layout>
