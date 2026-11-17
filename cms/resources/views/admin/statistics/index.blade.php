@php
    $rupiah = fn (int|float|null $amount): string => 'Rp'.number_format((int) $amount, 0, ',', '.');
    $summaryTiles = [
        ['label' => 'Total Dana Terkumpul', 'value' => $rupiah($summary['total_collected']), 'hint' => number_format($summary['paid_donation_count'], 0, ',', '.').' donasi lunas (termasuk donasi umum)'],
        ['label' => 'Terkumpul untuk Program', 'value' => $rupiah($summary['program_collected']), 'hint' => 'Dari '.$summary['program_count'].' program yang membuka donasi'],
        ['label' => 'Total Target Donasi', 'value' => $rupiah($summary['total_target']), 'hint' => 'Kekurangan '.$rupiah($summary['total_remaining'])],
        ['label' => 'Program Mencapai Target', 'value' => $summary['reached_count'].' / '.$summary['program_count'], 'hint' => 'Program dengan donasi ≥ target'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Statistik Donasi" description="Total keuangan dan perkembangan donasi setiap program." />
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 pt-8 sm:px-6 lg:px-8">
        <section aria-label="Ringkasan keuangan" class="stagger grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($summaryTiles as $tile)
                <div class="panel p-5">
                    <p class="text-sm font-medium text-stone-500">{{ $tile['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-stone-900">{{ $tile['value'] }}</p>
                    <p class="mt-1 text-xs text-stone-500">{{ $tile['hint'] }}</p>
                </div>
            @endforeach
        </section>

        <section>
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama program..." class="field-input w-full sm:w-72">
                <select name="target" class="field-input w-full sm:w-56" onchange="this.form.requestSubmit()">
                    <option value="">Semua status target</option>
                    @foreach (\App\Http\Controllers\Admin\StatisticController::TARGET_FILTERS as $value => $label)
                        <option value="{{ $value }}" @selected($targetFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="sort" class="field-input w-full sm:w-48" onchange="this.form.requestSubmit()">
                    @foreach (\App\Http\Controllers\Admin\StatisticController::SORT_OPTIONS as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary">Cari</button>
                @if ($search !== '' || $targetFilter)
                    <a href="{{ route('admin.statistics.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>

            <div class="panel mt-4 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm">
                        <thead class="bg-stone-50 text-left font-semibold text-stone-600">
                            <tr>
                                <th class="px-6 py-3">Program</th>
                                <th class="px-6 py-3 text-right">Terkumpul</th>
                                <th class="px-6 py-3 text-right">Target</th>
                                <th class="px-6 py-3 text-right">Kekurangan</th>
                                <th class="min-w-[14rem] px-6 py-3">Progres</th>
                            </tr>
                        </thead>
                        <tbody class="stagger divide-y divide-stone-100">
                            @forelse ($programs as $program)
                                @php
                                    $progress = $program->donationProgress();
                                    $isReached = $program->donation_target && (int) $program->collected_amount >= $program->donation_target;
                                @endphp
                                <tr class="transition-colors hover:bg-primary-50/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-thumbnail :path="$program->cover_image" />
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.programs.edit', $program) }}" class="font-semibold text-stone-900 hover:text-primary-700">{{ $program->title }}</a>
                                                <p class="text-xs text-stone-500">{{ $program->statusLabel() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-semibold tabular-nums text-stone-900">{{ $rupiah($program->collected_amount) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right tabular-nums text-stone-600">{{ $program->donation_target ? $rupiah($program->donation_target) : '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right tabular-nums text-stone-600">{{ $rupiah($program->remainingDonationAmount()) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="Progres donasi {{ $program->title }}">
                                                <div @class(['h-full origin-left rounded-full motion-safe:animate-grow-x', 'bg-emerald-600' => $isReached, 'bg-primary-700' => ! $isReached]) style="width: {{ $progress }}%"></div>
                                            </div>
                                            <span class="w-14 shrink-0 text-right text-xs font-semibold tabular-nums text-stone-700">{{ rtrim(rtrim(number_format($progress, 1, ',', '.'), '0'), ',') }}%</span>
                                        </div>
                                        @if ($isReached)
                                            <span class="mt-1.5 inline-block text-xs font-semibold text-emerald-700">Target tercapai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-stone-500">Tidak ada program donasi yang cocok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $programs->links() }}</div>
        </section>
    </div>
</x-app-layout>
