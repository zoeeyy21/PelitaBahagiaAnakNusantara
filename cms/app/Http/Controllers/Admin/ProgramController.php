<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.programs.index', [
            'programs' => Program::query()
                ->latest()
                ->paginate(10),
            'viewMode' => $this->resolveViewMode($request, 'programs'),
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.create', [
            'program' => new Program,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $packages = $this->pullPackages($validated);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['summary'] = $this->summarize($validated['description']);
        $validated['published_at'] = now();
        $validated['cover_image'] = $this->storeImage($request, $validated, 'cover_image');
        $validated['qris_image'] = $this->storeImage($request, $validated, 'qris_image');

        DB::transaction(function () use ($validated, $packages): void {
            $program = Program::create($validated);

            if ($packages !== null) {
                $this->syncPackages($program, $packages);
            }
        });

        return redirect()->route('admin.programs.index')->with('status', 'Program berhasil ditambahkan.');
    }

    public function show(Program $program): RedirectResponse
    {
        return redirect()->route('admin.programs.edit', $program);
    }

    public function edit(Program $program): View
    {
        $program->load('donationPackages');

        return view('admin.programs.edit', [
            'program' => $program,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $this->validateProgram($request, $program);
        $packages = $this->pullPackages($validated);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $program);
        $validated['summary'] = $this->summarize($validated['description']);

        foreach (['cover_image', 'qris_image'] as $imageField) {
            $storedPath = $this->storeImage($request, $validated, $imageField);

            if ($storedPath) {
                $this->deleteStoredFile($program->{$imageField});
                $validated[$imageField] = $storedPath;
            } else {
                unset($validated[$imageField]);
            }
        }

        DB::transaction(function () use ($program, $validated, $packages): void {
            $program->update($validated);

            if ($packages !== null) {
                $this->syncPackages($program, $packages);
            }
        });

        return redirect()->route('admin.programs.index')->with('status', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->deleteStoredFile($program->cover_image);
        $this->deleteStoredFile($program->qris_image);
        $program->delete();

        return redirect()->route('admin.programs.index')->with('status', 'Program berhasil dihapus.');
    }

    /**
     * Pengaturan donasi (target, paket, nominal minimal, QRIS) hanya divalidasi dan disimpan
     * saat program membuka donasi. Bila tidak, data donasi yang sudah ada dibiarkan apa adanya.
     *
     * @return array<string, mixed>
     */
    private function validateProgram(Request $request, ?Program $program = null): array
    {
        $request->merge([
            'accepts_donation' => $request->boolean('accepts_donation'),
            'packages' => collect($request->input('packages', []))
                ->filter(fn ($package): bool => is_array($package) && (filled($package['name'] ?? null) || filled($package['amount'] ?? null)))
                ->values()
                ->all(),
        ]);

        $acceptsDonation = $request->boolean('accepts_donation');

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('programs', 'slug')->ignore($program)],
            'description' => ['required', 'string'],
            'goal' => ['nullable', 'string', 'max:1000'],
            'beneficiaries' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(array_keys(Program::STATUS_LABELS))],
            'accepts_donation' => ['boolean'],
        ];

        if ($acceptsDonation) {
            $rules += [
                'donation_target' => ['required', 'integer', 'min:1000'],
                'packages' => ['array', 'max:12'],
                'packages.*.name' => ['required', 'string', 'max:100'],
                'packages.*.amount' => ['required', 'integer', 'min:1000'],
                'min_donation_amount' => [Rule::requiredIf(count($request->input('packages')) === 0), 'nullable', 'integer', 'min:1000'],
                'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ];
        }

        return $request->validate($rules, [
            'min_donation_amount.required' => 'Isi nominal minimal donasi bila program tidak memiliki paket donasi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ], [
            'donation_target' => 'target donasi',
            'min_donation_amount' => 'nominal minimal',
            'packages.*.name' => 'nama paket',
            'packages.*.amount' => 'nominal paket',
            'qris_image' => 'QRIS',
        ]);
    }

    /**
     * Ambil daftar paket dari data tervalidasi. Null berarti paket tidak perlu diubah.
     *
     * @param  array<string, mixed>  $validated
     * @return list<array{name: string, amount: int}>|null
     */
    private function pullPackages(array &$validated): ?array
    {
        if (! $validated['accepts_donation']) {
            unset($validated['packages']);

            return null;
        }

        $packages = $validated['packages'] ?? [];
        unset($validated['packages']);

        return $packages;
    }

    /**
     * @param  list<array{name: string, amount: int}>  $packages
     */
    private function syncPackages(Program $program, array $packages): void
    {
        $program->donationPackages()->delete();

        foreach ($packages as $index => $package) {
            $program->donationPackages()->create([
                'name' => $package['name'],
                'amount' => $package['amount'],
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Ringkasan dibuat otomatis dari deskripsi program.
     */
    private function summarize(string $description): string
    {
        return Str::limit(trim(strip_tags($description)), 200);
    }

    /**
     * Simpan gambar hanya bila field tersebut ikut divalidasi (mis. QRIS saat donasi dibuka).
     *
     * @param  array<string, mixed>  $validated
     */
    private function storeImage(Request $request, array $validated, string $field): ?string
    {
        if (! array_key_exists($field, $validated)) {
            return null;
        }

        return $request->file($field)?->store('programs', 'public');
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $title, ?Program $program = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 2;

        while (Program::where('slug', $slug)->when($program, fn ($query) => $query->where('id', '!=', $program->getKey()))->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
