<form method="POST" action="{{ route('donations.store') }}"
    x-data="{
        amount: @js((string) old('amount', '')),
        programId: @js((string) old('program_id', $selectedProgram ?? '')),
        options: @js($donationOptions),
        loading: false,
        current() {
            return this.options[this.programId] ?? this.options[''];
        },
        formatRupiah(value) {
            return 'Rp' + Number(value).toLocaleString('id-ID');
        },
        error: '',
        async submit(event) {
            this.error = '';
            this.loading = true;

            try {
                const response = await fetch(this.$el.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(this.$el),
                });
                const data = await response.json();

                if (! response.ok) {
                    this.error = data.errors
                        ? Object.values(data.errors)[0][0]
                        : (data.message || 'Gagal memproses donasi melalui Midtrans.');
                    this.loading = false;

                    return;
                }

                if (data.snap_token && window.snap) {
                    const goToStatus = () => window.location.href = data.donation_url;
                    window.snap.pay(data.snap_token, { onSuccess: goToStatus, onPending: goToStatus, onError: goToStatus, onClose: goToStatus });

                    return;
                }

                if (data.snap_token) {
                    this.error = 'Halaman pembayaran Midtrans tidak dapat dimuat. Periksa koneksi internet Anda lalu coba lagi.';
                    this.loading = false;

                    return;
                }

                window.location.href = data.donation_url;
            } catch (err) {
                this.error = 'Terjadi gangguan jaringan atau server saat menghubungi sistem pembayaran.';
                this.loading = false;
            }
        },
    }"
    x-on:submit.prevent="submit"
    x-init="$watch('programId', () => { amount = '' })"
    x-on:open-donation.window="programId = $event.detail ? String($event.detail) : ''; $dispatch('open-modal', 'donation')">
    @csrf
    <input type="hidden" name="donation_form" value="1">
    <div class="grid gap-5">
        <div x-show="error" x-cloak class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
            <p class="font-semibold">Perhatian</p>
            <p class="mt-1" x-text="error"></p>
        </div>

        <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Tujuan Donasi</span>
            <select name="program_id" x-model="programId" class="rounded border-slate-300">
                <option value="">Donasi Umum</option>
                @foreach ($donationPrograms as $program)
                    <option value="{{ $program->id }}" @selected((string) old('program_id', $selectedProgram ?? '') === (string) $program->id)>{{ $program->title }}</option>
                @endforeach
            </select>
            @error('program_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <div>
            <span class="text-sm font-semibold text-slate-700" x-text="current().packages.length && current().packages[0].name ? 'Paket Donasi' : 'Nominal'">Nominal</span>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4" x-show="current().packages.length">
                <template x-for="option in current().packages" :key="option.amount + (option.name ?? '')">
                    <button type="button" x-on:click="amount = String(option.amount)" :class="amount === String(option.amount) ? 'border-primary-600 bg-primary-50' : 'border-slate-200'" class="rounded border p-3 text-left text-sm hover:border-primary-300">
                        <span x-show="option.name" class="block font-semibold text-slate-800" x-text="option.name"></span>
                        <span x-text="formatRupiah(option.amount)"></span>
                    </button>
                </template>
            </div>
            <template x-if="current().allowsCustom">
                <div>
                    <input type="number" name="amount" x-model="amount" :min="current().min ?? 0" :placeholder="current().packages.length ? 'Atau isi nominal manual' : 'Isi nominal donasi'" class="mt-3 w-full rounded border-slate-300" required>
                    <span x-show="current().min" class="mt-1 block text-xs text-slate-500" x-text="'Minimal ' + formatRupiah(current().min)"></span>
                </div>
            </template>
            <template x-if="! current().allowsCustom">
                <div>
                    <input type="hidden" name="amount" :value="amount">
                    <p x-show="! amount" class="mt-2 text-xs text-slate-500">Pilih salah satu paket di atas.</p>
                </div>
            </template>
            @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-slate-700">Nama Donatur</span>
                <input type="text" name="donor_name" value="{{ old('donor_name') }}" class="rounded border-slate-300" required>
                @error('donor_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-slate-700">Nomor WhatsApp</span>
                <input type="tel" name="donor_phone" value="{{ old('donor_phone') }}" class="rounded border-slate-300" required>
                @error('donor_phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Email <span class="font-normal text-slate-500">(opsional)</span></span>
            <input type="email" name="donor_email" value="{{ old('donor_email') }}" class="rounded border-slate-300">
            @error('donor_email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Pesan <span class="font-normal text-slate-500">(opsional)</span></span>
            <textarea name="message" rows="3" class="rounded border-slate-300">{{ old('message') }}</textarea>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))>
            <span class="text-sm font-semibold text-slate-700">Sembunyikan nama saya (anonim)</span>
        </label>

        <button type="submit" x-bind:disabled="loading" class="inline-flex items-center justify-center gap-2 rounded bg-primary-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 disabled:cursor-not-allowed disabled:opacity-60">
            <span x-show="loading" x-cloak class="size-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>
            <span x-text="loading ? 'Menghubungkan Midtrans...' : 'Lanjut ke Pembayaran'">Lanjut ke Pembayaran</span>
        </button>
    </div>
</form>
