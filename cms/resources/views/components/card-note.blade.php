@props(['label'])

<div class="mt-5 rounded-2xl bg-stone-100/70 px-4 py-3 text-sm">
    <p class="font-semibold text-stone-800">{{ $label }}</p>
    <p class="mt-0.5 text-[13px] text-stone-500">{{ $slot }}</p>
</div>
