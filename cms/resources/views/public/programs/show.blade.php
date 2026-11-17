@php
    $image = $program->cover_image
        ? (str_starts_with($program->cover_image, 'http') ? $program->cover_image : \Illuminate\Support\Facades\Storage::url($program->cover_image))
        : 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1200&q=80';
@endphp

<x-public-layout :title="$program->title" :description="$program->summary">
    <article class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <div>
                <p class="flex items-center gap-3 text-sm font-semibold uppercase tracking-wide text-primary-700">
                    Program
                    <span class="rounded-full {{ $program->status === 'active' ? 'bg-primary-100 text-primary-800' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs normal-case tracking-normal">{{ $program->statusLabel() }}</span>
                </p>
                <h1 class="mt-3 text-4xl font-extrabold text-primary-950">{{ $program->title }}</h1>
                <p class="mt-5 leading-8 text-slate-600">{{ $program->summary }}</p>
                @if ($program->isAcceptingDonations())
                    <a href="{{ route('donations.create', ['program_id' => $program->id]) }}" x-data x-on:click.prevent="$dispatch('open-donation', {{ $program->id }})" class="mt-6 inline-flex rounded bg-primary-700 px-5 py-3 text-sm font-semibold text-white">Donasi untuk program ini</a>
                @endif
            </div>
            <img class="h-full min-h-[320px] w-full rounded-lg object-cover" src="{{ $image }}" alt="{{ $program->title }}">
        </div>
    </article>

    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
        <div class="rounded border border-primary-100 bg-white p-6">
            <h2 class="text-2xl font-bold text-slate-950">Deskripsi</h2>
            <div class="mt-4 whitespace-pre-line leading-8 text-slate-600">{{ $program->description }}</div>
        </div>
        <aside class="space-y-4">
            @if ($program->dateRangeLabel())
                <div class="rounded border border-primary-100 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-500">Periode program</p>
                    <p class="mt-2 text-slate-700">{{ $program->dateRangeLabel('d F Y') }}</p>
                </div>
            @endif
            @if ($program->isAcceptingDonations() && $program->donation_target)
                <div class="rounded border border-primary-100 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-500">Donasi terkumpul</p>
                    <p class="mt-2 text-xl font-bold text-primary-800">Rp{{ number_format((int) $program->collected_amount, 0, ',', '.') }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-primary-700" style="width: {{ $program->donationProgress() }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">dari target Rp{{ number_format($program->donation_target, 0, ',', '.') }}</p>
                </div>
            @endif
            @if ($program->isAcceptingDonations() && $program->qris_image)
                <div class="rounded border border-primary-100 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-500">Donasi via QRIS</p>
                    <img class="mt-3 w-full rounded" src="{{ str_starts_with($program->qris_image, 'http') ? $program->qris_image : \Illuminate\Support\Facades\Storage::url($program->qris_image) }}" alt="QRIS donasi {{ $program->title }}" loading="lazy">
                </div>
            @endif
            <div class="rounded border border-primary-100 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Tujuan</p>
                <p class="mt-2 text-slate-700">{{ $program->goal ?? 'Akan diperbarui.' }}</p>
            </div>
            <div class="rounded border border-primary-100 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Penerima manfaat</p>
                <p class="mt-2 text-slate-700">{{ $program->beneficiaries ?? 'Masyarakat penerima manfaat.' }}</p>
            </div>
        </aside>
    </section>

    @if ($program->activities->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-950">Kegiatan Program</h2>
            <div class="mt-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($program->activities as $activity)
                    @include('public.partials.activity-card', ['activity' => $activity])
                @endforeach
            </div>
        </section>
    @endif

    @if ($program->galleryItems->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-950">Dokumentasi</h2>
            <div class="mt-6">
                @include('public.partials.gallery-grid', ['items' => $program->galleryItems])
            </div>
        </section>
    @endif
</x-public-layout>
