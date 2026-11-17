@props([
    'label' => null,
    'startName' => 'start_date',
    'endName' => 'end_date',
    'start' => null,
    'end' => null,
    'placeholder' => 'Pilih rentang tanggal',
    'hint' => null,
])

@php
    $toDateString = fn ($value): string => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) ($value ?? '');
@endphp

<div {{ $attributes->class('field') }}
    x-data="dateRange({ start: @js($toDateString($start)), end: @js($toDateString($end)) })"
    x-on:keydown.escape="open = false"
    x-on:click.outside="open = false">
    @if ($label)
        <span class="field-label">{{ $label }}</span>
    @endif

    <input type="hidden" name="{{ $startName }}" x-model="start" value="{{ $toDateString($start) }}">
    <input type="hidden" name="{{ $endName }}" x-model="end" value="{{ $toDateString($end) }}">

    <div class="relative">
        <button type="button" x-on:click="toggle()" class="field-input flex items-center gap-3 text-left" :aria-expanded="open">
            <x-icon name="calendar" class="size-4 text-stone-400" />
            <span class="flex-1 truncate" :class="start ? 'text-stone-900' : 'text-stone-400'" x-text="displayLabel() || @js($placeholder)">{{ $placeholder }}</span>
            <span x-show="start" x-cloak x-on:click.stop="clear()" role="button" aria-label="Hapus tanggal" class="rounded-full p-0.5 text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </span>
        </button>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms
            class="absolute left-0 z-30 mt-2 w-72 rounded-2xl border border-stone-100 bg-white p-4 shadow-xl shadow-stone-900/10">
            <div class="flex items-center justify-between">
                <button type="button" x-on:click="shiftMonth(-1)" class="rounded-full p-1.5 text-stone-500 hover:bg-stone-100" aria-label="Bulan sebelumnya">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 0 1 0 1.06L8.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
                </button>
                <p class="text-sm font-semibold text-stone-900" x-text="monthLabel()"></p>
                <button type="button" x-on:click="shiftMonth(1)" class="rounded-full p-1.5 text-stone-500 hover:bg-stone-100" aria-label="Bulan berikutnya">
                    <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 0 1 0 1.06L8.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
                </button>
            </div>

            <div class="mt-3 grid grid-cols-7 text-center text-xs font-semibold text-stone-400">
                <template x-for="weekday in ['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg']" :key="weekday">
                    <span class="py-1" x-text="weekday"></span>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-y-1 text-center text-sm">
                <template x-for="cell in cells()" :key="cell.key">
                    <button type="button" x-on:click="cell.date && pick(cell.date)" x-on:mouseenter="hover = cell.date"
                        :disabled="! cell.date"
                        :class="{
                            'invisible': ! cell.date,
                            'bg-primary-700 text-white font-semibold': isEdge(cell.date),
                            'bg-primary-50 text-primary-800': ! isEdge(cell.date) && isInRange(cell.date),
                            'text-stone-700 hover:bg-stone-100': cell.date && ! isEdge(cell.date) && ! isInRange(cell.date),
                            'ring-1 ring-inset ring-primary-300': cell.date === today && ! isEdge(cell.date),
                        }"
                        class="mx-auto flex size-9 items-center justify-center rounded-full transition" x-text="cell.day"></button>
                </template>
            </div>
            <p class="mt-3 text-xs text-stone-500" x-text="start && ! end ? 'Pilih tanggal selesai.' : 'Pilih tanggal mulai.'"></p>
        </div>
    </div>

    @if ($hint)
        <span class="field-hint">{{ $hint }}</span>
    @endif
    @error($startName) <span class="field-error">{{ $message }}</span> @enderror
    @error($endName) <span class="field-error">{{ $message }}</span> @enderror
</div>
