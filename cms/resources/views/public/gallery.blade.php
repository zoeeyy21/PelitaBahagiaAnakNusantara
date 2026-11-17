<x-public-layout title="Galeri">
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Galeri</p>
            <h1 class="mt-3 text-4xl font-extrabold text-primary-950">Dokumentasi visual kegiatan</h1>
        </div>
        <div class="mt-8">
            @if ($galleryItems->isEmpty())
                <p class="rounded-3xl border border-stone-100 bg-white p-6 text-center text-stone-500">Belum ada media galeri yang dipublikasikan.</p>
            @else
                @include('public.partials.gallery-grid', ['items' => $galleryItems])
            @endif
        </div>
        <div class="mt-8">{{ $galleryItems->links() }}</div>
    </section>
</x-public-layout>
