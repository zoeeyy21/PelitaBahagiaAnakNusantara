<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /**
     * Jumlah maksimal gambar per kegiatan (gambar pertama menjadi thumbnail).
     */
    public const MAX_IMAGES = 10;

    public function index(Request $request): View
    {
        return view('admin.activities.index', [
            'activities' => Activity::query()
                ->with('program')
                ->withCount('galleryItems')
                ->latest()
                ->paginate(10),
            'viewMode' => $this->resolveViewMode($request, 'activities'),
        ]);
    }

    public function create(): View
    {
        return view('admin.activities.create', [
            'activity' => new Activity(['start_date' => now()]),
            'programs' => Program::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateActivity($request);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['excerpt'] = $this->summarize($validated['body']);
        $validated['published_at'] = now();

        $imagePaths = $this->storeUploadedImages($request);

        DB::transaction(function () use ($validated, $imagePaths): void {
            $activity = Activity::create($this->withoutImageFields($validated));
            $this->syncImages($activity, $imagePaths);
        });

        return redirect()->route('admin.activities.index')->with('status', 'Kegiatan berhasil ditambahkan.');
    }

    public function show(Activity $activity): RedirectResponse
    {
        return redirect()->route('admin.activities.edit', $activity);
    }

    public function edit(Activity $activity): View
    {
        $activity->load('galleryItems');

        return view('admin.activities.edit', [
            'activity' => $activity,
            'programs' => Program::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $activity->load('galleryItems');

        $validated = $this->validateActivity($request, $activity);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $activity);
        $validated['excerpt'] = $this->summarize($validated['body']);

        $currentPaths = $activity->imagePaths();
        $keptPaths = $this->keptImagePaths($request, $currentPaths);
        $imagePaths = [...$keptPaths, ...$this->storeUploadedImages($request)];

        DB::transaction(function () use ($activity, $validated, $imagePaths): void {
            $activity->update($this->withoutImageFields($validated));
            $this->syncImages($activity, $imagePaths);
        });

        foreach (array_diff($currentPaths, $keptPaths) as $removedPath) {
            $this->deleteStoredFile($removedPath);
        }

        return redirect()->route('admin.activities.index')->with('status', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $imagePaths = $activity->imagePaths();

        DB::transaction(function () use ($activity): void {
            $activity->galleryItems()->delete();
            $activity->delete();
        });

        foreach ($imagePaths as $path) {
            $this->deleteStoredFile($path);
        }

        return redirect()->route('admin.activities.index')->with('status', 'Kegiatan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateActivity(Request $request, ?Activity $activity = null): array
    {
        $existingPaths = $activity?->imagePaths() ?? [];
        $remainingSlots = max(0, self::MAX_IMAGES - count($this->keptImagePaths($request, $existingPaths)));

        return $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('activities', 'slug')->ignore($activity)],
            'body' => ['required', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'keep_images' => ['nullable', 'array'],
            'keep_images.*' => ['string', Rule::in($existingPaths)],
            'images' => ['nullable', 'array', 'max:'.$remainingSlots],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'images.max' => 'Maksimal '.self::MAX_IMAGES.' gambar per kegiatan.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ], [
            'images.*' => 'gambar',
        ]);
    }

    /**
     * Gambar lama yang dipertahankan, sesuai urutan dari form. Bila form tidak mengirim
     * pengelola gambar ("manage_images"), semua gambar lama dipertahankan.
     *
     * @param  list<string>  $existingPaths
     * @return list<string>
     */
    private function keptImagePaths(Request $request, array $existingPaths): array
    {
        if (! $request->boolean('manage_images')) {
            return $existingPaths;
        }

        return array_values(array_unique(array_intersect((array) $request->input('keep_images', []), $existingPaths)));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withoutImageFields(array $validated): array
    {
        unset($validated['images'], $validated['keep_images']);

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function storeUploadedImages(Request $request): array
    {
        return array_map(
            fn (UploadedFile $image): string => $image->store('activities', 'public'),
            array_values($request->file('images', [])),
        );
    }

    /**
     * Gambar pertama menjadi cover (thumbnail), sisanya menjadi galeri kegiatan.
     *
     * @param  list<string>  $imagePaths
     */
    private function syncImages(Activity $activity, array $imagePaths): void
    {
        $activity->update(['cover_image' => $imagePaths[0] ?? null]);
        $activity->galleryItems()->delete();

        foreach (array_slice($imagePaths, 1) as $index => $path) {
            $activity->galleryItems()->create([
                'program_id' => $activity->program_id,
                'title' => $activity->title,
                'image_path' => $path,
                'sort_order' => $index,
                'is_published' => true,
            ]);
        }

        $activity->unsetRelation('galleryItems');
    }

    /**
     * Ringkasan dibuat otomatis dari isi kegiatan.
     */
    private function summarize(string $body): string
    {
        return Str::limit(trim(strip_tags($body)), 200);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $title, ?Activity $activity = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 2;

        while (Activity::where('slug', $slug)->when($activity, fn ($query) => $query->where('id', '!=', $activity->getKey()))->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
