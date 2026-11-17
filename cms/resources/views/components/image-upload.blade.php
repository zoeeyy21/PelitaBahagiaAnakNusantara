@props(['name', 'label', 'current' => null])

@php
    $previewId = 'preview-'.$name;
    $currentUrl = $current
        ? (str_starts_with($current, 'http') ? $current : \Illuminate\Support\Facades\Storage::url($current))
        : null;
@endphp

<div class="field">
    <label for="{{ $name }}" class="field-label">{{ $label }}</label>
    <div data-preview-frame @class(['overflow-hidden rounded-2xl border border-stone-100 bg-stone-50', 'hidden' => ! $currentUrl])>
        <img id="{{ $previewId }}" src="{{ $currentUrl }}" alt="Pratinjau {{ strtolower($label) }}" class="h-44 w-full object-cover motion-safe:animate-fade-in">
    </div>
    <input type="file" id="{{ $name }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" data-preview="{{ $previewId }}" class="field-file">
    <span class="field-hint">JPG, PNG, atau WEBP, maksimal 2 MB.</span>
    @error($name) <span class="field-error">{{ $message }}</span> @enderror
</div>
