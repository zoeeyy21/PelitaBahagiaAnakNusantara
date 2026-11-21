<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $bank_name = '';
    public string $bank_account_number = '';
    public string $bank_account_holder = '';
    public $qris_image = null;
    public ?string $current_qris_image = null;
    public bool $remove_qris_image = false;
    public int $qrisInputKey = 0;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->bank_name = (string) $user->bank_name;
        $this->bank_account_number = (string) $user->bank_account_number;
        $this->bank_account_holder = (string) $user->bank_account_holder;
        $this->current_qris_image = $user->qris_image;
    }

    /**
     * Simpan profil bank (bank, rekening, pemilik rekening, dan QRIS) milik admin.
     */
    public function updateBankInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'regex:/^[0-9\-\s]{5,30}$/'],
            'bank_account_holder' => ['required', 'string', 'max:255'],
            'qris_image' => [$this->current_qris_image && ! $this->remove_qris_image ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, atau tanda -.',
            'qris_image.required' => $this->remove_qris_image ? 'QRIS lama dihapus, unggah gambar QRIS pengganti.' : 'Gambar QRIS wajib diunggah.',
        ], [
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_holder' => 'nama pemilik rekening',
            'qris_image' => 'QRIS',
        ]);

        if ($this->qris_image) {
            if ($user->qris_image && ! str_starts_with($user->qris_image, 'http')) {
                Storage::disk('public')->delete($user->qris_image);
            }

            $validated['qris_image'] = $this->qris_image->store('qris', 'public');
            $this->current_qris_image = $validated['qris_image'];
        } else {
            unset($validated['qris_image']);
        }

        $user->update($validated);

        $this->reset('qris_image', 'remove_qris_image');
        $this->qrisInputKey++;
        $this->dispatch('bank-information-updated');
    }

    /**
     * Hapus foto QRIS yang salah: file baru yang belum disimpan langsung dibatalkan, sedangkan
     * QRIS tersimpan ditandai untuk diganti (QRIS wajib, jadi harus diunggah ulang sebelum disimpan).
     */
    public function removeQrisImage(): void
    {
        if ($this->qris_image) {
            $this->reset('qris_image');
            $this->qrisInputKey++;
        } else {
            $this->remove_qris_image = true;
        }

        $this->resetValidation('qris_image');
    }

    public function undoRemoveQrisImage(): void
    {
        $this->remove_qris_image = false;
    }
}; ?>

<section>
    <header>
        <h2 class="flex items-center gap-2 text-lg font-medium text-gray-900">
            Informasi Bank &amp; QRIS
            <span class="badge bg-red-50 text-red-600">Wajib</span>
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Rekening dan QRIS yayasan untuk menerima donasi. Wajib diisi lengkap.
        </p>
    </header>

    <form wire:submit="updateBankInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="bank_name" value="Nama Bank" />
            <x-text-input wire:model="bank_name" id="bank_name" type="text" class="mt-1 block w-full" placeholder="Mis. BCA, BRI, Mandiri" required />
            <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
        </div>

        <div>
            <x-input-label for="bank_account_number" value="Nomor Rekening" />
            <x-text-input wire:model="bank_account_number" id="bank_account_number" type="text" inputmode="numeric" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->get('bank_account_number')" />
        </div>

        <div>
            <x-input-label for="bank_account_holder" value="Nama Pemilik Rekening" />
            <x-text-input wire:model="bank_account_holder" id="bank_account_holder" type="text" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->get('bank_account_holder')" />
        </div>

        <div>
            <x-input-label for="qris_image" value="Gambar QRIS" />
            @if ($qris_image && method_exists($qris_image, 'temporaryUrl'))
                <img src="{{ $qris_image->temporaryUrl() }}" alt="Pratinjau QRIS" class="mt-2 h-48 w-48 rounded-xl border border-stone-100 object-contain">
            @elseif ($current_qris_image && ! $remove_qris_image)
                <img src="{{ str_starts_with($current_qris_image, 'http') ? $current_qris_image : \Illuminate\Support\Facades\Storage::url($current_qris_image) }}" alt="QRIS saat ini" class="mt-2 h-48 w-48 rounded-xl border border-stone-100 object-contain">
            @endif
            @if ($remove_qris_image && ! $qris_image)
                <div class="mt-2 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-dashed border-red-200 bg-red-50/60 px-4 py-3 text-sm text-red-700">
                    <span>QRIS lama akan dihapus. Unggah gambar QRIS pengganti lalu simpan.</span>
                    <button type="button" wire:click="undoRemoveQrisImage" class="font-semibold underline-offset-2 hover:underline">Batalkan</button>
                </div>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <input wire:model="qris_image" wire:key="qris-input-{{ $qrisInputKey }}" id="qris_image" type="file" accept="image/jpeg,image/png,image/webp" class="field-file flex-1">
                @if ($qris_image || ($current_qris_image && ! $remove_qris_image))
                    <button type="button" wire:click="removeQrisImage" class="btn btn-danger btn-sm">
                        <x-icon name="trash" class="size-4" />
                        {{ $qris_image ? 'Batalkan pilihan' : 'Hapus foto' }}
                    </button>
                @endif
            </div>
            <p class="field-hint mt-1">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
            <div wire:loading wire:target="qris_image" class="mt-1 text-xs text-stone-500">Mengunggah...</div>
            <x-input-error class="mt-2" :messages="$errors->get('qris_image')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="bank-information-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
