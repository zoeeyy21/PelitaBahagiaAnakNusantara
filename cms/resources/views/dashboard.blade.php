<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Halo, {{ auth()->user()->name }}" description="Ringkasan konten dan donasi yayasan hari ini." />
    </x-slot>

    @php
        $statTiles = [
            ['label' => 'Program', 'value' => $programCount, 'icon' => 'folder', 'route' => route('admin.programs.index'), 'tone' => 'primary'],
            ['label' => 'Kegiatan', 'value' => $activityCount, 'icon' => 'calendar', 'route' => route('admin.activities.index'), 'tone' => 'primary'],
            ['label' => 'Donasi', 'value' => $donationCount, 'icon' => 'heart', 'route' => route('admin.donations.index'), 'tone' => 'primary'],
        ];

        $quickActions = [
            ['label' => 'Tambah Program', 'hint' => 'Buat program baru', 'icon' => 'folder', 'route' => route('admin.programs.create')],
            ['label' => 'Tambah Kegiatan', 'hint' => 'Kabar & foto kegiatan', 'icon' => 'calendar', 'route' => route('admin.activities.create')],
            ['label' => 'Statistik Donasi', 'hint' => 'Progres donasi program', 'icon' => 'chart', 'route' => route('admin.statistics.index')],
            ['label' => 'Profil & Kontak', 'hint' => 'Alamat, WA, Struktur, FAQ', 'icon' => 'phone', 'route' => route('admin.contact.edit')],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-10 px-4 pt-8 sm:px-6 lg:px-8">
        <section aria-label="Ringkasan">
            <div class="stagger grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($statTiles as $tile)
                    <a href="{{ $tile['route'] }}" class="panel card-interactive group flex items-start justify-between gap-4 p-5">
                        <div>
                            <p class="text-sm font-medium text-stone-500">{{ $tile['label'] }}</p>
                            <p @class(['mt-2 text-3xl font-bold tabular-nums', 'text-stone-900' => $tile['tone'] === 'primary', 'text-amber-600' => $tile['tone'] === 'amber']) data-count-up>{{ $tile['value'] }}</p>
                        </div>
                        <span @class(['flex size-10 items-center justify-center rounded-2xl transition duration-300 group-hover:scale-110 group-hover:rotate-3', 'bg-primary-50 text-primary-700' => $tile['tone'] === 'primary', 'bg-amber-50 text-amber-600' => $tile['tone'] === 'amber'])>
                            <x-icon :name="$tile['icon']" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-stone-900">Aksi Cepat</h2>
            <div class="stagger mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="panel card-interactive group flex items-center gap-3 p-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-primary-700 text-white shadow-md shadow-primary-700/25 transition duration-300 group-hover:scale-110">
                            <x-icon :name="$action['icon']" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-stone-900">{{ $action['label'] }}</span>
                            <span class="block truncate text-xs text-stone-500">{{ $action['hint'] }}</span>
                        </span>
                        <x-icon name="arrow-right" class="size-4 text-stone-300 transition duration-300 group-hover:translate-x-1 group-hover:text-primary-700" />
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
