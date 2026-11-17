<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-stone-200/70 bg-white/85 backdrop-blur-md">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-10" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden lg:ms-8 lg:flex lg:items-center lg:gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        Dashboard
                    </x-nav-link>
                    <x-nav-link :href="route('admin.programs.index')" :active="request()->routeIs('admin.programs.*')" wire:navigate>
                        Program
                    </x-nav-link>
                    <x-nav-link :href="route('admin.activities.index')" :active="request()->routeIs('admin.activities.*')" wire:navigate>
                        Kegiatan
                    </x-nav-link>
                    <x-nav-link :href="route('admin.contact.edit')" :active="request()->routeIs('admin.contact.*')" wire:navigate>
                        Profil & Kontak
                    </x-nav-link>
                    <x-nav-link :href="route('admin.donations.index')" :active="request()->routeIs('admin.donations.*')" wire:navigate>
                        Donasi
                    </x-nav-link>
                    <x-nav-link :href="route('admin.statistics.index')" :active="request()->routeIs('admin.statistics.*')" wire:navigate>
                        Statistik
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:ms-6 lg:flex lg:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full py-1.5 pl-1.5 pr-3 text-sm font-medium text-stone-600 ring-1 ring-inset ring-stone-200 transition hover:bg-stone-50 hover:text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            <span class="flex size-7 items-center justify-center rounded-full bg-primary-700 text-xs font-bold uppercase text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-700 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-stone-100 lg:hidden">
        <div class="space-y-1 px-4 pb-3 pt-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.programs.index')" :active="request()->routeIs('admin.programs.*')" wire:navigate>
                Program
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.activities.index')" :active="request()->routeIs('admin.activities.*')" wire:navigate>
                Kegiatan
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.contact.edit')" :active="request()->routeIs('admin.contact.*')" wire:navigate>
                Profil & Kontak
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.donations.index')" :active="request()->routeIs('admin.donations.*')" wire:navigate>
                Donasi
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.statistics.index')" :active="request()->routeIs('admin.statistics.*')" wire:navigate>
                Statistik
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-stone-100 pb-3 pt-4">
            <div class="px-8">
                <div class="font-medium text-base text-stone-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-stone-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1 px-4">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
