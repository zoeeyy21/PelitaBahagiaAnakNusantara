@php
    $initialPackages = old('packages', $program->exists
        ? $program->donationPackages->map(fn ($package): array => ['name' => $package->name, 'amount' => $package->amount])->all()
        : []);
@endphp

<div class="grid gap-6" x-data="{ acceptsDonation: @js((bool) old('accepts_donation', $program->accepts_donation)), packages: @js(array_values($initialPackages)) }">
    <label class="field">
        <span class="field-label">Nama Program</span>
        <input name="title" value="{{ old('title', $program->title) }}" class="field-input" required>
        @error('title') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <input type="hidden" name="slug" value="{{ old('slug', $program->slug) }}">

    <label class="field">
        <span class="field-label">Deskripsi</span>
        <textarea name="description" rows="8" class="field-input" required>{{ old('description', $program->description) }}</textarea>
        @error('description') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <div class="grid gap-6 md:grid-cols-2 md:items-start">
        <label class="field">
            <span class="field-label">Tujuan</span>
            <textarea name="goal" rows="4" class="field-input">{{ old('goal', $program->goal) }}</textarea>
        </label>
        <label class="field">
            <span class="field-label">Penerima Manfaat</span>
            <input name="beneficiaries" value="{{ old('beneficiaries', $program->beneficiaries) }}" class="field-input">
        </label>
    </div>

    <x-date-range label="Periode Program" :start="old('start_date', $program->start_date)" :end="old('end_date', $program->end_date)" hint="Opsional. Klik tanggal mulai, lalu tanggal selesai." />

    <div class="grid gap-6 md:grid-cols-2 md:items-start">
        <label class="field">
            <span class="field-label">Status</span>
            <select name="status" class="field-input">
                @foreach (\App\Models\Program::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $program->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <x-image-upload name="cover_image" label="Cover" :current="$program->cover_image" />
    </div>

    <div class="rounded-2xl border border-stone-100 bg-stone-50/60 p-5">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="hidden" name="accepts_donation" value="0">
            <input type="checkbox" name="accepts_donation" value="1" x-model="acceptsDonation" class="mt-0.5 rounded border-stone-300 text-primary-700 focus:ring-primary-500">
            <span>
                <span class="block text-sm font-semibold text-stone-800">Buka donasi untuk program ini</span>
                <span class="block text-xs text-stone-500">Jika tidak dicentang, program tetap tampil tanpa tombol donasi.</span>
            </span>
        </label>

        <div x-show="acceptsDonation" x-cloak x-transition.opacity class="mt-6 grid gap-6 border-t border-stone-200/70 pt-6">
            <div class="grid gap-6 md:grid-cols-2 md:items-start">
                <label class="field">
                    <span class="field-label">Target Donasi (Rp)</span>
                    <input type="number" name="donation_target" min="1000" step="1000" value="{{ old('donation_target', $program->donation_target) }}" class="field-input" placeholder="25000000" x-bind:required="acceptsDonation">
                    @error('donation_target') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field">
                    <span class="field-label">Nominal Minimal Donasi Custom (Rp) <span class="font-normal text-stone-400" x-show="packages.length">(opsional)</span></span>
                    <input type="number" name="min_donation_amount" min="1000" step="1000" value="{{ old('min_donation_amount', $program->min_donation_amount) }}" class="field-input" placeholder="10000" x-bind:required="acceptsDonation && packages.length === 0">
                    <span class="field-hint" x-text="packages.length ? 'Kosongkan bila donatur hanya boleh memilih paket.' : 'Wajib diisi karena belum ada paket donasi.'"></span>
                    @error('min_donation_amount') <span class="field-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="field">
                <div class="flex items-center justify-between gap-3">
                    <span class="field-label">Paket Donasi <span class="font-normal text-stone-400">(opsional)</span></span>
                    <button type="button" x-on:click="packages.push({ name: '', amount: '' })" x-show="packages.length < 12" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" />Tambah Paket</button>
                </div>
                <template x-for="(package, index) in packages" :key="index">
                    <div class="grid grid-cols-[1fr_9rem_auto] items-center gap-3">
                        <input type="text" :name="`packages[${index}][name]`" x-model="package.name" placeholder="Nama paket, mis. Paket Sekolah" maxlength="100" class="field-input" x-bind:required="acceptsDonation">
                        <input type="number" :name="`packages[${index}][amount]`" x-model="package.amount" placeholder="Nominal" min="1000" step="1000" class="field-input" x-bind:required="acceptsDonation">
                        <button type="button" x-on:click="packages.splice(index, 1)" class="btn btn-danger btn-sm px-3" aria-label="Hapus paket"><x-icon name="trash" class="size-4" /></button>
                    </div>
                </template>
                <p x-show="packages.length === 0" class="field-hint">Belum ada paket. Donatur akan mengisi nominal sendiri.</p>
                @error('packages') <span class="field-error">{{ $message }}</span> @enderror
                @foreach ($errors->get('packages.*') as $messages)
                    <span class="field-error">{{ $messages[0] }}</span>
                @endforeach
            </div>

            <div class="md:w-1/2">
                <x-image-upload name="qris_image" label="QRIS Program" :current="$program->qris_image" />
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t border-stone-100 pt-6">
        <a href="{{ route('admin.programs.index') }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan</button>
    </div>
</div>
