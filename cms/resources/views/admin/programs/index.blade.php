<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Program" description="Kelola program yayasan yang tampil di website.">
            <a href="{{ route('admin.programs.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />Tambah Program</a>
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
                                    <th class="px-6 py-3">Nama Program</th>
                                    <th class="px-6 py-3">Periode</th>
                                    <th class="px-6 py-3">Donasi</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="stagger divide-y divide-stone-100">
                                @forelse ($programs as $program)
                                    <tr class="transition-colors hover:bg-primary-50/40">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <x-thumbnail :path="$program->cover_image" />
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-stone-900">{{ $program->title }}</p>
                                                    <p class="truncate text-xs text-stone-500">{{ $program->beneficiaries ?? '-' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-stone-500">{{ $program->dateRangeLabel() ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-stone-500">{{ $program->accepts_donation ? 'Target Rp'.number_format((int) $program->donation_target, 0, ',', '.') : 'Tidak dibuka' }}</td>
                                        <td class="px-6 py-4 "><span @class(['badge', 'badge-active' => $program->isPubliclyVisible(), 'badge-muted' => ! ($program->isPubliclyVisible())])>{{ $program->isPubliclyVisible() ? 'Aktif' : 'Non-aktif' }}</span></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-secondary btn-sm px-3">Edit</a>
                                                <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" data-confirm="Hapus program ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm px-3">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-6 text-center text-stone-500">Belum ada program.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
            <div class="stagger grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($programs as $program)
                    <x-content-card :seed="$program->id" :badge="$program->statusLabel()" :muted="! $program->isPubliclyVisible()" :headline="$program->title" :subline="$program->beneficiaries" :cover="$program->cover_image" :title="$program->title">
                        <p class="mt-3 text-[15px] leading-6 text-stone-500">{{ $program->summary }}</p>
                        @if ($program->goal)
                            <x-card-note label="Target Capaian:">{{ \Illuminate\Support\Str::limit($program->goal, 90) }}</x-card-note>
                        @endif

                        <x-slot:footer>
                            <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" data-confirm="Hapus program ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                            <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-primary btn-sm">Edit Program</a>
                        </x-slot:footer>
                    </x-content-card>
                @empty
                    <p class="rounded-3xl border border-stone-100 bg-white p-6 text-center text-stone-500 sm:col-span-2 lg:col-span-3">Belum ada program.</p>
                @endforelse
            </div>
            @endif
            <div class="mt-8">{{ $programs->links() }}</div>
        </div>
    </div>
</x-app-layout>
