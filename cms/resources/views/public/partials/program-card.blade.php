<x-content-card :seed="$program->id" :badge="$program->statusLabel()" :headline="$program->title" :subline="$program->beneficiaries" :cover="$program->cover_image" :title="$program->title">
    <p class="mt-3 text-[15px] leading-6 text-stone-500">{{ $program->summary }}</p>

    @if ($program->goal)
        <x-card-note label="Target Capaian:">{{ \Illuminate\Support\Str::limit($program->goal, 90) }}</x-card-note>
    @endif

    <x-slot:footer>
        <a href="{{ route('programs.show', $program) }}" class="text-sm font-semibold text-stone-800 hover:text-primary-700">Pelajari Detail &rarr;</a>
        @if ($program->isAcceptingDonations())
            <a href="{{ route('donations.create', ['program_id' => $program->id]) }}" x-data x-on:click.prevent="$dispatch('open-donation', {{ $program->id }})" class="rounded-full bg-primary-700 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-primary-700/30 hover:bg-primary-800">Dukung Program</a>
        @endif
    </x-slot:footer>
</x-content-card>
