<div class="grid gap-6">
    <label class="field">
        <span class="field-label">Judul</span>
        <input name="title" value="{{ old('title', $activity->title) }}" class="field-input" required>
        @error('title') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <input type="hidden" name="slug" value="{{ old('slug', $activity->slug) }}">

    <div class="grid gap-6 md:grid-cols-2 md:items-start">
        <label class="field">
            <span class="field-label">Program Terkait</span>
            <select name="program_id" class="field-input">
                <option value="">Tidak terkait program tertentu</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected((int) old('program_id', $activity->program_id) === $program->id)>{{ $program->title }}</option>
                @endforeach
            </select>
        </label>
        <x-date-range label="Tanggal Kegiatan" :start="old('start_date', $activity->start_date)" :end="old('end_date', $activity->end_date)" hint="Klik tanggal mulai, lalu tanggal selesai." />
    </div>

    <label class="field">
        <span class="field-label">Isi Kegiatan</span>
        <textarea name="body" rows="9" class="field-input" required>{{ old('body', $activity->body) }}</textarea>
        @error('body') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <x-multi-image-upload name="images" label="Gambar Kegiatan" :current="$activity->exists ? $activity->imagePaths() : []" :max="\App\Http\Controllers\Admin\ActivityController::MAX_IMAGES" />

    <div class="flex justify-end gap-3 border-t border-stone-100 pt-6">
        <a href="{{ route('admin.activities.index') }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan</button>
    </div>
</div>
