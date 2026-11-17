<x-public-layout title="Donasi">
    <section class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Donasi</p>
            <h1 class="mt-3 text-4xl font-extrabold text-primary-950">Berbagi dukungan untuk program yayasan.</h1>
            <p class="mt-5 leading-8 text-slate-600">Dukungan Anda disalurkan secara aman dan transparan melalui gateway pembayaran Midtrans untuk membantu program pendidikan dan masa depan anak nusantara.</p>
        </div>
        <div class="rounded border border-primary-100 bg-white p-6">
            @include('public.donations.partials.form')
        </div>
    </section>
</x-public-layout>
