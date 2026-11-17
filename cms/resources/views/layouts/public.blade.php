<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'Website resmi Yayasan Pelita Bahagia Anak Nusantara.' }}">
        <meta property="og:title" content="{{ $title ?? 'Yayasan Pelita Bahagia Anak Nusantara' }}">
        <meta property="og:description" content="{{ $description ?? 'Profil yayasan, program, kegiatan, galeri, dan donasi.' }}">

        <title>{{ isset($title) ? $title.' - ' : '' }}Yayasan Pelita Bahagia Anak Nusantara</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if (config('midtrans.client_key'))
            <script src="{{ config('midtrans.snap_url') }}" data-client-key="{{ config('midtrans.client_key') }}" defer></script>
        @endif
    </head>
    <body class="bg-cream font-sans text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="sticky top-0 z-40 border-b border-primary-100 bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('programs.index') }}" class="flex items-center">
                        <x-application-logo class="h-11 sm:h-12" />
                    </a>

                    <nav class="hidden items-center gap-6 text-sm font-medium text-slate-700 md:flex">
                        <a href="{{ route('about') }}" class="hover:text-primary-700">Tentang Kami</a>
                        <a href="{{ route('programs.index') }}" class="hover:text-primary-700">Program</a>
                        <a href="{{ route('activities.index') }}" class="hover:text-primary-700">Kegiatan</a>
                        <a href="{{ route('gallery') }}" class="hover:text-primary-700">Galeri</a>
                        <a href="{{ route('contact') }}" class="hover:text-primary-700">Kontak</a>
                    </nav>

                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="hidden rounded border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-800 sm:inline-flex">CMS</a>
                        @else
                            <a href="{{ route('login') }}" class="hidden rounded border border-primary-200 px-4 py-2 text-sm font-semibold text-primary-800 sm:inline-flex">Admin</a>
                        @endauth
                        <a href="{{ route('donations.create') }}" x-data x-on:click.prevent="$dispatch('open-donation')" class="rounded bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-800">Donasi</a>
                    </div>
                </div>
                <nav class="mx-auto flex max-w-7xl gap-4 overflow-x-auto px-4 pb-3 text-sm font-medium text-slate-700 sm:px-6 md:hidden lg:px-8">
                    <a href="{{ route('about') }}" class="shrink-0">Tentang</a>
                    <a href="{{ route('programs.index') }}" class="shrink-0">Program</a>
                    <a href="{{ route('activities.index') }}" class="shrink-0">Kegiatan</a>
                    <a href="{{ route('gallery') }}" class="shrink-0">Galeri</a>
                    <a href="{{ route('contact') }}" class="shrink-0">Kontak</a>
                </nav>
            </header>

            <main>
                {{ $slot }}
            </main>

            <footer class="border-t border-primary-100 bg-white">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
                    <div>
                        <p class="font-bold text-primary-950">Yayasan Pelita Bahagia Anak Nusantara</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Kanal resmi yayasan untuk program sosial, kegiatan, dokumentasi, komunikasi, dan donasi.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Navigasi</p>
                        <div class="mt-3 grid gap-2 text-sm text-slate-600">
                            <a href="{{ route('programs.index') }}">Program</a>
                            <a href="{{ route('activities.index') }}">Kegiatan</a>
                            <a href="{{ route('gallery') }}">Galeri</a>
                            <a href="{{ route('donations.create') }}" x-data x-on:click.prevent="$dispatch('open-donation')">Donasi</a>
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Kontak</p>
                        <div class="mt-3 grid gap-2 text-sm text-slate-600">
                            <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener">WhatsApp: {{ $contact['whatsapp'] }}</a>
                            <a href="mailto:{{ $contact['email'] }}">Email: {{ $contact['email'] }}</a>
                            <a href="{{ $contact['instagram_url'] }}" target="_blank" rel="noopener">Instagram: {{ '@'.$contact['instagram'] }}</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        @unless (request()->routeIs('donations.create'))
            <x-modal name="donation" :show="$errors->any() && old('donation_form')" maxWidth="xl" focusable>
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Donasi</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-primary-950">Berbagi dukungan untuk program yayasan.</h2>
                        </div>
                        <button type="button" x-on:click="show = false" class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
                    </div>
                    <div class="mt-6">
                        @include('public.donations.partials.form')
                    </div>
                </div>
            </x-modal>
        @endunless

        @livewireScripts
    </body>
</html>
