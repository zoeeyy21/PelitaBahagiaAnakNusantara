@php
    $exportQuery = array_filter([
        'search' => $filters['search'] ?: null,
        'program' => $filters['program'],
        'start_date' => $filters['start_date'] ?? '',
        'end_date' => $filters['end_date'] ?? '',
    ], fn ($value): bool => $value !== null);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Donasi" description="Pantau dan konfirmasi donasi yang masuk.">
            <a href="{{ route('admin.donations.export', $exportQuery) }}" class="btn btn-secondary">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.28 8.47a.75.75 0 0 0-1.06 1.06l4.25 4.25a.75.75 0 0 0 1.06 0l4.25-4.25a.75.75 0 1 0-1.06-1.06l-2.97 2.97V2.75Z"/><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/></svg>
                Export CSV / Excel
            </a>
        </x-page-header>
    </x-slot>

    <div class="pt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-6 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_17rem_auto] md:items-start">
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari donatur, no. HP, atau program..." class="field-input" aria-label="Cari donasi">
                <select name="program" class="field-input" aria-label="Filter program">
                    <option value="">Semua program</option>
                    <option value="general" @selected($filters['program'] === 'general')>Donasi Umum</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected($filters['program'] === (string) $program->id)>{{ $program->title }}</option>
                    @endforeach
                </select>
                <x-date-range :start="$filters['start_date']" :end="$filters['end_date']" placeholder="Semua tanggal" />
                <div class="flex gap-3">
                    <button class="btn btn-primary">Terapkan</button>
                    <a href="{{ route('admin.donations.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <p class="mb-3 text-sm text-stone-500">
                {{ number_format($donations->total(), 0, ',', '.') }} donasi
                @if ($filters['start_date'] || $filters['end_date'])
                    · {{ $filters['start_date'] ? \Illuminate\Support\Carbon::parse($filters['start_date'])->translatedFormat('d M Y') : 'awal' }}
                    – {{ $filters['end_date'] ? \Illuminate\Support\Carbon::parse($filters['end_date'])->translatedFormat('d M Y') : 'sekarang' }}
                @else
                    · semua tanggal
                @endif
            </p>

            <div class="overflow-hidden panel">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm">
                        <thead class="bg-stone-50 text-left font-semibold text-stone-600">
                            <tr>
                                <th class="px-6 py-3">Donatur</th>
                                <th class="px-6 py-3">No. HP</th>
                                <th class="px-6 py-3">Program</th>
                                <th class="px-6 py-3 text-right">Nominal</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="stagger divide-y divide-stone-100">
                            @forelse ($donations as $donation)
                                <tr class="transition-colors hover:bg-primary-50/40">
                                    <td class="min-w-[11rem] px-6 py-4 font-semibold text-stone-900">
                                        <a href="{{ route('admin.donations.show', $donation) }}" class="hover:text-primary-700">{{ $donation->donor_name ?? '-' }}{{ $donation->is_anonymous ? ' (anonim)' : '' }}</a>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-stone-500">{{ $donation->donor_phone ?? '-' }}</td>
                                    <td class="min-w-[12rem] px-6 py-4 text-stone-500">{{ $donation->program?->title ?? 'Donasi Umum' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-semibold tabular-nums text-stone-900">Rp{{ number_format($donation->amount, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4"><span @class(['badge', 'badge-active' => $donation->status === 'paid', 'badge-muted' => $donation->status !== 'paid'])>{{ $donation->statusLabel() }}</span></td>
                                    <td class="whitespace-nowrap px-6 py-4 text-stone-500">{{ $donation->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-stone-500">Tidak ada donasi pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-8">{{ $donations->links() }}</div>
        </div>
    </div>
</x-app-layout>
