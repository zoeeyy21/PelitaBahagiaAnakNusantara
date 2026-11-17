<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Profil" description="Kelola informasi bank, akun, dan kata sandi." />
    </x-slot>

    <div class="pt-8">
        <div class="stagger max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div id="bank" class="panel scroll-mt-24 p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-bank-information-form />
                </div>
            </div>

            <div class="panel p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="panel p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="panel p-6 sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
