@props(['name', 'label', 'current' => null, 'fit' => 'cover', 'zoomable' => false, 'removable' => false, 'hint' => 'JPG, PNG, atau WEBP, maksimal 2 MB.'])

@php
    $previewId = 'preview-'.$name;
    $currentUrl = $current
        ? (str_starts_with($current, 'http') ? $current : \Illuminate\Support\Facades\Storage::url($current))
        : null;
    $interactive = $zoomable || $removable;
    $zoomButton = 'flex size-8 items-center justify-center rounded-full text-white transition hover:bg-white/20';
@endphp

<div class="field" @if ($interactive) x-data="imageField({ originalSrc: @js($currentUrl ?? '') })" @endif>
    <label for="{{ $name }}" class="field-label">{{ $label }}</label>
    <div data-preview-frame @if ($interactive) x-show="hasImage && ! removed" @endif @class(['relative overflow-hidden rounded-2xl border border-stone-100 bg-stone-50', 'hidden' => ! $currentUrl])>
        @if ($zoomable)
            {{-- Kotak pratinjau: zoom dengan tombol / Ctrl+scroll / cubit, geser dengan drag --}}
            <div x-ref="stage" class="h-80 touch-pan-y select-none overflow-hidden bg-white" :class="scale > 1 ? 'cursor-grab active:cursor-grabbing touch-none' : 'cursor-zoom-in'"
                x-on:wheel="onWheel($event)" x-on:dblclick="onDoubleClick($event)"
                x-on:pointerdown="onPointerDown($event)" x-on:pointermove="onPointerMove($event)" x-on:pointerup="onPointerUp($event)" x-on:pointercancel="onPointerUp($event)">
                <img id="{{ $previewId }}" x-ref="zoomImage" src="{{ $currentUrl }}" alt="Pratinjau {{ strtolower($label) }}" draggable="false"
                    class="h-full w-full object-contain p-3 will-change-transform" :style="`transform: ${transform()}`">
            </div>
            <div class="absolute bottom-3 right-3 flex items-center gap-0.5 rounded-full bg-stone-900/80 p-1 shadow-lg backdrop-blur">
                <button type="button" x-on:click="zoomBy(1 / 1.5)" :disabled="scale <= 1" class="{{ $zoomButton }} disabled:opacity-40" aria-label="Perkecil">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14"/></svg>
                </button>
                <button type="button" x-on:click="reset()" class="h-8 min-w-14 rounded-full px-2 text-xs font-semibold tabular-nums text-white transition hover:bg-white/20" aria-label="Kembalikan ukuran" x-text="`${Math.round(scale * 100)}%`">100%</button>
                <button type="button" x-on:click="zoomBy(1.5)" class="{{ $zoomButton }}" aria-label="Perbesar">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </button>
                <span class="mx-0.5 h-5 w-px bg-white/25" aria-hidden="true"></span>
                <button type="button" x-on:click="$dispatch('open-image-zoom', { name: @js($name), src: $refs.zoomImage.src })" class="{{ $zoomButton }}" aria-label="Lihat layar penuh">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                </button>
            </div>
        @else
            <img id="{{ $previewId }}" src="{{ $currentUrl }}" alt="Pratinjau {{ strtolower($label) }}" @if ($interactive) x-ref="zoomImage" @endif @class([
                'w-full motion-safe:animate-fade-in',
                'h-44 object-cover' => $fit === 'cover',
                'h-64 bg-white object-contain p-3' => $fit === 'contain',
            ])>
        @endif
    </div>
    @if ($removable)
        <input type="hidden" name="remove_{{ $name }}" :value="removed ? 1 : 0" value="0">
        <div x-show="removed" x-cloak class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-dashed border-red-200 bg-red-50/60 px-4 py-3 text-sm text-red-700">
            <span>Foto akan dihapus saat perubahan disimpan.</span>
            <button type="button" x-on:click="undoRemove()" class="font-semibold underline-offset-2 hover:underline">Batalkan</button>
        </div>
    @endif
    <div class="flex flex-wrap items-center gap-3">
        <input type="file" id="{{ $name }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" data-preview="{{ $previewId }}" @class(['field-file', 'flex-1' => $removable]) @if ($interactive) x-ref="input" x-on:change="fileChosen($event)" @endif>
        @if ($removable)
            <button type="button" x-show="hasImage && ! removed" x-cloak x-on:click="removePhoto()" class="btn btn-danger btn-sm">
                <x-icon name="trash" class="size-4" />
                <span x-text="hasNewFile ? 'Batalkan pilihan' : 'Hapus foto'">Hapus foto</span>
            </button>
        @endif
    </div>
    <span class="field-hint">{{ $hint }}</span>
    @error($name) <span class="field-error">{{ $message }}</span> @enderror

    @if ($zoomable)
        {{-- Layar penuh --}}
        <dialog x-data="zoomPan" x-ref="dialog" x-on:open-image-zoom.window="$event.detail.name === @js($name) && show($event.detail.src)" x-on:close="reset()"
            class="m-0 h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-stone-950/95" aria-label="Pratinjau {{ strtolower($label) }}">
            <div class="flex h-full w-full flex-col">
                <div class="flex items-center justify-between gap-3 px-4 py-3 text-white sm:px-6">
                    <p class="truncate text-sm font-semibold">{{ $label }}</p>
                    <div class="flex items-center gap-1.5">
                        <button type="button" x-on:click="zoomBy(1 / 1.5)" class="flex size-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Perkecil">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14"/></svg>
                        </button>
                        <button type="button" x-on:click="reset()" class="h-9 min-w-16 rounded-full bg-white/10 px-3 text-xs font-semibold tabular-nums hover:bg-white/20" aria-label="Kembalikan ukuran" x-text="`${Math.round(scale * 100)}%`">100%</button>
                        <button type="button" x-on:click="zoomBy(1.5)" class="flex size-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Perbesar">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <a :href="src" target="_blank" rel="noopener" class="ml-1 hidden h-9 items-center rounded-full bg-white/10 px-3 text-xs font-semibold hover:bg-white/20 sm:inline-flex">Buka gambar asli</a>
                        <button type="button" x-on:click="$refs.dialog.close()" class="ml-1 flex size-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Tutup">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                </div>
                <div x-ref="stage" class="flex min-h-0 flex-1 touch-none select-none items-center justify-center overflow-hidden" :class="scale > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in'"
                    x-on:wheel="onWheel($event)" x-on:dblclick="onDoubleClick($event)"
                    x-on:pointerdown="onPointerDown($event)" x-on:pointermove="onPointerMove($event)" x-on:pointerup="onPointerUp($event)" x-on:pointercancel="onPointerUp($event)">
                    <img x-ref="zoomImage" :src="src" alt="{{ $label }}" draggable="false"
                        class="max-h-full max-w-full object-contain will-change-transform" :style="`transform: ${transform()}`">
                </div>
                <p class="px-4 py-3 text-center text-xs text-white/60">Scroll / cubit untuk zoom · seret untuk menggeser · klik dua kali untuk perbesar · Esc untuk menutup</p>
            </div>
        </dialog>
    @endif
</div>
