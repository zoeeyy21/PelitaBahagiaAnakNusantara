@props(['name' => 'images', 'label' => 'Gambar', 'current' => [], 'max' => 10])

@php
    $existingImages = collect($current)->map(fn (string $path): array => [
        'path' => $path,
        'url' => str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::url($path),
    ])->values()->all();
@endphp

<div class="field" x-data="multiImageUpload({ existing: @js($existingImages), max: {{ (int) $max }} })">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <label for="{{ $name }}" class="field-label">{{ $label }}</label>
        <span class="text-xs font-semibold tabular-nums text-stone-500"><span x-text="total()">{{ count($existingImages) }}</span>/{{ $max }} gambar</span>
    </div>

    <div class="rounded-xl border border-primary-100 bg-primary-50/60 px-4 py-3 text-xs leading-5 text-primary-900">
        <strong>Catatan:</strong> image pertama yang di upload sebagai thumbnail, dan selanjutnya sebagai galeri.
    </div>

    <input type="hidden" name="manage_images" value="1">
    <template x-for="image in existing" :key="image.path">
        <input type="hidden" name="keep_images[]" :value="image.path">
    </template>

    <div x-show="total() > 0" x-cloak class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <template x-for="(image, index) in previews()" :key="image.key">
            <figure class="group relative aspect-square overflow-hidden rounded-xl border border-stone-100 bg-stone-50 motion-safe:animate-fade-in">
                <img :src="image.url" alt="" class="size-full object-cover">
                <span x-show="index === 0" class="absolute left-2 top-2 rounded-full bg-primary-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Thumbnail</span>
                <span x-show="index > 0" class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-stone-600">Galeri</span>
                <button type="button" x-on:click="remove(image)" class="absolute right-2 top-2 rounded-full bg-white/90 p-1 text-stone-600 shadow transition hover:bg-red-50 hover:text-red-600" aria-label="Hapus gambar">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                </button>
            </figure>
        </template>
    </div>

    <input type="file" id="{{ $name }}" name="{{ $name }}[]" multiple accept="image/jpeg,image/png,image/webp" x-ref="input" x-on:change="add($event)" class="field-file">
    <span class="field-hint">JPG, PNG, atau WEBP, maksimal 2 MB per gambar, hingga {{ $max }} gambar.</span>
    <span x-show="warning" x-cloak class="field-error" x-text="warning"></span>
    @error($name) <span class="field-error">{{ $message }}</span> @enderror
    @foreach ($errors->get($name.'.*') as $messages)
        <span class="field-error">{{ $messages[0] }}</span>
    @endforeach
</div>
