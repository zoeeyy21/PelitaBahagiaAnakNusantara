<div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($items as $item)
        @php
            $itemImage = str_starts_with($item->image_path, 'http') ? $item->image_path : \Illuminate\Support\Facades\Storage::url($item->image_path);
        @endphp
        <figure class="flex flex-col overflow-hidden rounded-3xl border border-stone-100 bg-white shadow-[0_8px_30px_rgba(120,90,60,0.08)]">
            <img class="h-56 w-full object-cover" src="{{ $itemImage }}" alt="{{ $item->title }}" loading="lazy">
            <figcaption class="flex-1 px-7 pb-6 pt-6">
                <p class="text-xl font-bold leading-snug text-stone-900">{{ $item->title }}</p>
                @if ($item->caption)
                    <p class="mt-2 text-sm leading-6 text-stone-500">{{ $item->caption }}</p>
                @endif
            </figcaption>
        </figure>
    @endforeach
</div>
