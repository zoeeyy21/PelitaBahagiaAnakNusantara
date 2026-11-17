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
        <div class="min-h-screen">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8 motion-safe:animate-fade-up">
                    {{ $header }}
                </header>
            @endif

            <main class="pb-16">
                {{ $slot }}
            </main>
        </div>

        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show"
                x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0 -translate-y-2"
                class="fixed inset-x-4 top-20 z-50 sm:inset-x-auto sm:right-6" role="status" aria-live="polite">
                <div class="flex items-center gap-3 rounded-2xl border border-primary-100 bg-white py-3 pl-3 pr-2 shadow-xl shadow-stone-900/10 motion-safe:animate-toast-in">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-primary-700">
                        <x-icon name="check" class="size-4" />
                    </span>
                    <p class="text-sm font-medium text-stone-800">{{ session('status') }}</p>
                    <button type="button" x-on:click="show = false" class="ml-2 rounded-full p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-600" aria-label="Tutup notifikasi">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>
            </div>
        @endif

        @if (auth()->check() && ! auth()->user()->hasCompletedBankProfile() && ! request()->routeIs('profile'))
            <dialog id="bank-profile-dialog" aria-labelledby="bank-profile-dialog-title"
                x-data x-init="try { if (sessionStorage.getItem('bank-profile-dialog-dismissed') !== '1') { $el.showModal() } } catch (error) { $el.showModal() }"
                x-on:close="try { sessionStorage.setItem('bank-profile-dialog-dismissed', '1') } catch (error) {}"
                class="w-[min(28rem,calc(100vw-2rem))] rounded-3xl p-0 shadow-2xl backdrop:bg-stone-900/40 backdrop:backdrop-blur-sm open:motion-safe:animate-toast-in">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                            <x-icon name="inbox" />
                        </span>
                        <div>
                            <h2 id="bank-profile-dialog-title" class="text-lg font-semibold text-stone-900">Lengkapi profil bank</h2>
                            <p class="mt-1 text-sm text-stone-500">Anda belum mengisi nama bank, nomor rekening, dan QRIS. Data ini wajib diisi agar donatur dapat menyalurkan donasi.</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <form method="dialog"><button class="btn btn-secondary">Nanti saja</button></form>
                        <a href="{{ route('profile') }}#bank" class="btn btn-primary">Isi sekarang</a>
                    </div>
                </div>
            </dialog>
        @endif

        <dialog id="confirm-dialog" class="w-[min(26rem,calc(100vw-2rem))] rounded-3xl p-0 shadow-2xl backdrop:bg-stone-900/40 backdrop:backdrop-blur-sm open:motion-safe:animate-toast-in">
            <form method="dialog" class="p-6">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <x-icon name="trash" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">Yakin ingin menghapus?</h2>
                        <p class="mt-1 text-sm text-stone-500" data-confirm-message></p>
                        <p class="mt-1 text-sm text-stone-500">Tindakan ini tidak bisa dibatalkan.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button value="cancel" class="btn btn-secondary">Batal</button>
                    <button value="confirm" class="btn btn-danger-solid">Ya, hapus</button>
                </div>
            </form>
        </dialog>
    </body>
</html>
