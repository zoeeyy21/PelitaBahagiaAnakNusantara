<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Kegiatan" description="Dokumentasi, galeri foto, dan kabar kegiatan yayasan.">
            <a href="{{ route('admin.activities.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Tambah Kegiatan</a>
        </x-page-header>
    </x-slot>

    <div class="pt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-end">
                <x-view-toggle :mode="$viewMode" />
            </div>
            @if ($viewMode === 'list')
                <div class="overflow-hidden panel">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm">
                            <thead class="bg-stone-50 text-left font-semibold text-stone-600">
                                <tr>
                                    <th class="px-6 py-3">Kegiatan</th>
                                    <th class="px-6 py-3">Program</th>
                                    <th class="px-6 py-3">Tanggal Kegiatan</th>
                                    <th class="px-6 py-3">Galeri</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="stagger divide-y divide-stone-100">
                                @forelse ($activities as $activity)
                                    <tr class="transition-colors hover:bg-primary-50/40">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <x-thumbnail :path="$activity->cover_image" />
                                                <span class="font-semibold text-stone-900">{{ $activity->title }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-stone-500">{{ $activity->program?->title ?? 'Tidak terkait program' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-stone-500">{{ $activity->dateRangeLabel() ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-stone-500">{{ $activity->gallery_items_count }} foto</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="{{ route('admin.activities.edit', $activity) }}" class="btn btn-secondary btn-sm px-3">Edit</a>
                                                <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" data-confirm="Hapus kegiatan ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm px-3">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-6 text-center text-stone-500">Belum ada kegiatan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
            <div class="stagger grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($activities as $activity)
                    <x-content-card :seed="$activity->id" badge="Kegiatan" :headline="$activity->title" :subline="$activity->dateRangeLabel('d F Y')" :cover="$activity->cover_image" :title="$activity->title">
                        <p class="mt-3 text-[15px] leading-6 text-stone-500">{{ $activity->excerpt }}</p>
                        <x-card-note label="Program:">{{ $activity->program?->title ?? 'Tidak terkait program' }} · {{ $activity->gallery_items_count }} foto galeri</x-card-note>

                        <x-slot:footer>
                            <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" data-confirm="Hapus kegiatan ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="btn btn-primary btn-sm">Edit Kegiatan</a>
                        </x-slot:footer>
                    </x-content-card>
                @empty
                    <p class="rounded-3xl border border-stone-100 bg-white p-6 text-center text-stone-500 sm:col-span-2 lg:col-span-3">Belum ada kegiatan.</p>
                @endforelse
            </div>
            @endif
            <div class="mt-8">{{ $activities->links() }}</div>
        </div>
    </div>
</x-app-layout>
