<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Profil & Kontak" description="Data ini tampil di halaman Tentang Kami, Kontak, dan footer website." />
    </x-slot>

    <div class="pt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
            <form method="POST" action="{{ route('admin.contact.update') }}" enctype="multipart/form-data" class="panel p-6 motion-safe:animate-fade-up sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid gap-6">
                    <label class="field">
                        <span class="field-label">Alamat</span>
                        <textarea name="address" rows="3" class="field-input" required>{{ old('address', $contact['address']) }}</textarea>
                        @error('address') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="field">
                            <span class="field-label">Nomor WhatsApp</span>
                            <input name="whatsapp" value="{{ old('whatsapp', $contact['whatsapp']) }}" placeholder="0812-3456-7890" class="field-input" required>
                            @error('whatsapp') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field">
                            <span class="field-label">Email</span>
                            <input type="email" name="email" value="{{ old('email', $contact['email']) }}" class="field-input" required>
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="field">
                        <span class="field-label">Username Instagram</span>
                        <input name="instagram" value="{{ old('instagram', $contact['instagram']) }}" placeholder="pelitabahagia" class="field-input" required>
                        @error('instagram') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="field">
                        <span class="field-label">Link Google Maps</span>
                        <input type="url" name="map_url" value="{{ old('map_url', $contact['map_url']) }}" placeholder="https://maps.app.goo.gl/..." class="field-input">
                        <span class="field-hint">Opsional. Jika diisi, halaman Kontak menampilkan tombol "Lihat di peta".</span>
                        @error('map_url') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <div class="field border-t border-stone-100 pt-6">
                        <span class="field-label">Bagan Struktur Organisasi</span>
                        <p class="field-hint mt-1 mb-3">Upload gambar bagan struktur kepengurusan yayasan yang tampil di halaman Tentang Kami.</p>
                        <x-image-upload name="structure_image" label="Foto Bagan Struktur" :current="$structureImage" />
                    </div>

                    <div class="field border-t border-stone-100 pt-6" x-data="{ faqs: @js(array_values(old('faqs', $faqs))) }">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="field-label">FAQ <span class="font-normal text-stone-400">(opsional)</span></span>
                                <p class="field-hint mt-1">Pertanyaan yang sering diajukan, tampil di halaman Kontak website.</p>
                            </div>
                            <button type="button" x-on:click="faqs.push({ question: '', answer: '' })" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" />Tambah FAQ</button>
                        </div>
                        <template x-for="(faq, index) in faqs" :key="index">
                            <div class="grid gap-3 rounded-2xl border border-stone-100 bg-stone-50/60 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-stone-500" x-text="`FAQ ${index + 1}`"></span>
                                    <button type="button" x-on:click="faqs.splice(index, 1)" class="btn btn-danger btn-sm px-3" aria-label="Hapus FAQ"><x-icon name="trash" class="size-4" /></button>
                                </div>
                                <input type="text" :name="`faqs[${index}][question]`" x-model="faq.question" maxlength="255" placeholder="Pertanyaan" class="field-input">
                                <textarea :name="`faqs[${index}][answer]`" x-model="faq.answer" rows="3" maxlength="2000" placeholder="Jawaban" class="field-input"></textarea>
                            </div>
                        </template>
                        <p x-show="faqs.length === 0" class="field-hint">Belum ada FAQ.</p>
                        @foreach ($errors->get('faqs*') as $messages)
                            <span class="field-error">{{ $messages[0] }}</span>
                        @endforeach
                    </div>

                    <div class="flex justify-end border-t border-stone-100 pt-6">
                        <button class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</x-app-layout>
