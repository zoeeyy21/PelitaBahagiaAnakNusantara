<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-bold tracking-tight text-stone-900">Masuk ke CMS</h1>
    <p class="mt-1 text-sm text-stone-500">Kelola program, kegiatan, galeri, dan donasi yayasan.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="mt-6 grid gap-5">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="form.email" id="email" class="mt-2" type="email" name="email" required autofocus autocomplete="username" placeholder="admin@pelitabahagia.org" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div x-data="{ visible: false }">
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata Sandi" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-primary-700 transition hover:text-primary-800" href="{{ route('password.request') }}" wire:navigate>Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative mt-2">
                <x-text-input wire:model="form.password" id="password" class="pr-11" x-bind:type="visible ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" />
                <button type="button" x-on:click="visible = ! visible" class="absolute inset-y-0 right-0 flex items-center px-3.5 text-stone-400 transition hover:text-stone-600" x-bind:aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <svg x-show="! visible" class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path fill-rule="evenodd" d="M.66 10.59a1.65 1.65 0 0 1 0-1.19A10 10 0 0 1 10 3c4.26 0 7.9 2.66 9.34 6.41.15.38.15.81 0 1.19A10 10 0 0 1 10 17c-4.26 0-7.9-2.66-9.34-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                    <svg x-show="visible" x-cloak class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.75-1.75A10 10 0 0 0 19.34 10.6a1.65 1.65 0 0 0 0-1.19A10 10 0 0 0 10 3a9.96 9.96 0 0 0-4.98 1.33L3.28 2.22Zm4.26 4.26 1.53 1.53a2.5 2.5 0 0 1 2.92 2.92l1.53 1.53a4 4 0 0 0-5.98-5.98Z" clip-rule="evenodd"/><path d="M10.75 16.95 8.03 14.23a4 4 0 0 1-2.26-2.26L2.04 8.24A10 10 0 0 0 .66 9.4a1.65 1.65 0 0 0 0 1.2A10 10 0 0 0 10.75 16.95Z"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="inline-flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-stone-300 text-primary-700 shadow-sm focus:ring-primary-500" name="remember">
            <span class="text-sm text-stone-600">Ingat saya</span>
        </label>

        <x-primary-button class="w-full" wire:loading.class="is-loading" wire:loading.attr="disabled" wire:target="login">
            Masuk
        </x-primary-button>
    </form>
</div>
