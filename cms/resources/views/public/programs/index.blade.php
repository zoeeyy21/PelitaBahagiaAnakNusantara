<x-public-layout title="Program">
    <section class="bg-cream">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Program</p>
            <h1 class="mt-3 text-4xl font-bold text-stone-900">Program yayasan yang sedang berjalan</h1>
            <p class="mt-4 text-lg leading-8 text-stone-500">Setiap program dirancang berbasis data lapangan dan kebutuhan riil anak-anak Indonesia untuk memberikan dampak jangka panjang.</p>
        </div>
        <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($programs as $program)
                @include('public.partials.program-card', ['program' => $program])
            @empty
                <p class="rounded-3xl border border-stone-100 bg-white p-6 text-center text-stone-500 sm:col-span-2 lg:col-span-3">Belum ada program yang dipublikasikan.</p>
            @endforelse
        </div>
        <div class="mt-10">{{ $programs->links() }}</div>
    </div>
    </section>
</x-public-layout>
