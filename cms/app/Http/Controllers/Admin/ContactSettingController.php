<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContactSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.contact.edit', [
            'contact' => SiteSetting::contact(),
            'faqs' => SiteSetting::faqs(),
            'structureImage' => SiteSetting::structureImage(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'faqs' => collect($request->input('faqs', []))
                ->filter(fn ($faq): bool => is_array($faq) && (filled($faq['question'] ?? null) || filled($faq['answer'] ?? null)))
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
            'whatsapp' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'instagram' => ['required', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'tiktok' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'facebook' => ['nullable', 'url:https', 'max:255'],
            'map_url' => ['nullable', 'url:https', 'max:1000'],
            'structure_image' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:3072'],
            'remove_structure_image' => ['nullable', 'boolean'],
            'faqs' => ['array', 'max:30'],
            'faqs.*.question' => ['required', 'string', 'max:255'],
            'faqs.*.answer' => ['required', 'string', 'max:2000'],
        ], [
            'faqs.*.question.required' => 'Pertanyaan FAQ wajib diisi bila jawabannya diisi.',
            'faqs.*.answer.required' => 'Jawaban FAQ wajib diisi bila pertanyaannya diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda + atau -.',
            'instagram.regex' => 'Username Instagram hanya boleh berisi huruf, angka, titik, dan garis bawah.',
            'tiktok.regex' => 'Username TikTok hanya boleh berisi huruf, angka, titik, dan garis bawah.',
            'facebook.url' => 'Link Facebook harus berupa URL lengkap yang diawali https://.',
            'structure_image.max' => 'Ukuran gambar struktur yayasan maksimal 3 MB.',
        ]);

        if ($request->hasFile('structure_image')) {
            $this->deleteStructureImageFile();
            SiteSetting::updateStructureImage($request->file('structure_image')->store('structure', 'public'));
        } elseif ($request->boolean('remove_structure_image')) {
            $this->deleteStructureImageFile();
            SiteSetting::updateStructureImage(null);
        }

        $validated['instagram'] = ltrim($validated['instagram'], '@');
        $validated['tiktok'] = filled($validated['tiktok'] ?? null) ? ltrim($validated['tiktok'], '@') : null;
        unset($validated['structure_image'], $validated['remove_structure_image']);

        SiteSetting::updateFaqs(array_map(fn (array $faq): array => [
            'question' => $faq['question'],
            'answer' => $faq['answer'],
        ], $validated['faqs'] ?? []));
        unset($validated['faqs']);

        SiteSetting::updateContact($validated);

        return redirect()->route('admin.contact.edit')->with('status', 'Informasi profil dan kontak berhasil diperbarui.');
    }

    private function deleteStructureImageFile(): void
    {
        $currentPath = SiteSetting::structureImage();

        if ($currentPath && ! str_starts_with($currentPath, 'http')) {
            Storage::disk('public')->delete($currentPath);
        }
    }
}
