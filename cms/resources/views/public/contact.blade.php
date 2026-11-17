<x-public-layout title="Kontak">
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Kontak</p>
            <h1 class="mt-3 text-4xl font-extrabold text-primary-950">Hubungi kanal resmi yayasan</h1>
            <p class="mt-5 leading-8 text-slate-600">Silakan hubungi kami melalui kanal resmi berikut untuk informasi program, kerja sama, maupun donasi.</p>
        </div>
        <div class="grid gap-4">
            <div class="rounded border border-primary-100 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Alamat</p>
                <p class="mt-2 whitespace-pre-line text-slate-800">{{ $contact['address'] }}</p>
                @if ($contact['map_url'])
                    <a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener" class="mt-3 inline-flex text-sm font-semibold text-primary-700">Lihat di peta</a>
                @endif
            </div>
            <div class="rounded border border-primary-100 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">WhatsApp</p>
                <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener" class="mt-2 inline-flex font-semibold text-primary-700">{{ $contact['whatsapp'] }}</a>
            </div>
            <div class="rounded border border-primary-100 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Email dan Instagram</p>
                <a href="mailto:{{ $contact['email'] }}" class="mt-2 block text-slate-800">{{ $contact['email'] }}</a>
                <a href="{{ $contact['instagram_url'] }}" target="_blank" rel="noopener" class="mt-1 block text-slate-800">{{ '@'.$contact['instagram'] }}</a>
            </div>
        </div>
    </section>

    @if ($faqs !== [])
        <section class="mx-auto max-w-4xl px-4 pb-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-950">Pertanyaan yang Sering Diajukan</h2>
            <div class="mt-6 grid gap-3">
                @foreach ($faqs as $faq)
                    <details class="group rounded border border-primary-100 bg-white p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900">
                            {{ $faq['question'] }}
                            <span class="text-primary-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 whitespace-pre-line leading-7 text-slate-600">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif
</x-public-layout>
