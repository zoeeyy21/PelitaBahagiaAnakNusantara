@props(['value'])

<label {{ $attributes->merge(['class' => 'field-label block']) }}>
    {{ $value ?? $slot }}
</label>
