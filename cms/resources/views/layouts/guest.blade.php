<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-cream font-sans text-stone-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
            <span class="pointer-events-none absolute -left-24 -top-24 size-80 rounded-full bg-primary-100/70 blur-2xl motion-safe:animate-fade-in" aria-hidden="true"></span>
            <span class="pointer-events-none absolute -bottom-32 -right-20 size-96 rounded-full bg-primary-200/50 blur-3xl motion-safe:animate-fade-in" aria-hidden="true"></span>

            <div class="relative w-full sm:max-w-md motion-safe:animate-fade-up">
                <a href="/" wire:navigate class="flex justify-center">
                    <x-application-logo class="h-16" />
                </a>

                <div class="panel mt-8 px-6 py-8 sm:px-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-stone-400">&copy; {{ date('Y') }} Yayasan Pelita Bahagia Anak Nusantara</p>
            </div>
        </div>
    </body>
</html>
