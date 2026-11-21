<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Detail Donasi" :back="route('admin.donations.index')" />
    </x-slot>

    <div @class(['mx-auto grid max-w-7xl gap-6 px-4 pt-8 sm:px-6 lg:px-8', 'lg:grid-cols-[minmax(0,48rem)_20rem]' => $donation->usesMidtrans()])>
        <div class="panel overflow-hidden motion-safe:animate-fade-up">
            <div class="relative overflow-hidden bg-gradient-to-br from-primary-400 to-primary-800 px-6 py-8 text-white sm:px-8">
                <span class="absolute -right-12 -top-16 size-44 rounded-full bg-white/10"></span>
                <span class="absolute -bottom-16 left-1/3 size-36 rounded-full bg-white/10"></span>
                <div class="relative">
                    <p class="text-sm text-white/80">Nominal donasi</p>
                    <p class="mt-1 text-4xl font-bold tabular-nums">Rp{{ number_format($donation->amount, 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm text-white/80">{{ $donation->reference_code }}</p>
                </div>
            </div>

            <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:grid-cols-2 sm:p-8">
                @foreach ([
                    'Donatur' => ($donation->donor_name ?? '-').($donation->is_anonymous ? ' (anonim)' : ''),
                    'No. HP / WhatsApp' => $donation->donor_phone ?? '-',
                    'Email' => $donation->donor_email ?? '-',
                    'Program' => $donation->program?->title ?? 'Donasi Umum',
                    'Metode Pembayaran' => $donation->paymentMethodLabel(),
                    'Tanggal Donasi' => $donation->created_at->toIndonesianDateTime(),
                    'Dibayar' => $donation->paid_at?->toIndonesianDateTime() ?? '-',
                ] as $label => $value)
                    <div>
                        <dt class="text-stone-500">{{ $label }}</dt>
                        <dd class="mt-1 font-semibold text-stone-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($donation->message)
                <div class="px-6 pb-8 sm:px-8">
                    <p class="text-sm text-stone-500">Pesan / doa donatur</p>
                    <blockquote class="mt-2 rounded-2xl border-l-4 border-primary-300 bg-stone-50 p-4 text-sm italic leading-6 text-stone-700">{{ $donation->message }}</blockquote>
                </div>
            @endif
        </div>

        @if ($donation->usesMidtrans())
            <div class="h-fit">
                <form method="POST" action="{{ route('admin.donations.sync-midtrans', $donation) }}" class="panel p-6 motion-safe:animate-fade-up" style="animation-delay: 80ms">
                    @csrf
                    <h2 class="font-semibold text-stone-900">Midtrans</h2>
                    <p class="mt-1 text-sm text-stone-500">Ambil status terbaru transaksi ini dari Midtrans.</p>
                    <button class="btn btn-secondary mt-5 w-full">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Sinkronkan dari Midtrans
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
