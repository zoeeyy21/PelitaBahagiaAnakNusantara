<x-public-layout title="Konfirmasi Donasi">
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded border border-primary-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-700">Konfirmasi Donasi</p>
                <div id="status-badge-container">
                    @if ($donation->status === 'paid')
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-bold text-primary-800">
                            <span class="size-2 rounded-full bg-primary-600"></span> Berhasil
                        </span>
                    @elseif ($donation->status === 'pending')
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                            <span class="size-2 animate-pulse rounded-full bg-amber-600"></span> Menunggu Pembayaran
                        </span>
                    @elseif ($donation->status === 'failed')
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-800">
                            <span class="size-2 rounded-full bg-red-600"></span> Gagal
                        </span>
                    @elseif ($donation->status === 'expired')
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-slate-500"></span> Kedaluwarsa
                        </span>
                    @elseif ($donation->status === 'cancelled')
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-slate-500"></span> Dibatalkan
                        </span>
                    @else
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-800">
                            {{ ucfirst($donation->status) }}
                        </span>
                    @endif
                </div>
            </div>

            <h1 id="status-title" class="mt-4 text-2xl font-extrabold text-primary-950 sm:text-3xl">
                @if ($donation->status === 'paid')
                    Terima kasih atas dukungannya!
                @elseif ($donation->status === 'pending')
                    Donasi Anda Menunggu Pembayaran
                @elseif ($donation->status === 'failed')
                    Pembayaran Belum Berhasil
                @elseif ($donation->status === 'expired')
                    Waktu Pembayaran Telah Berakhir
                @elseif ($donation->status === 'cancelled')
                    Transaksi Donasi Dibatalkan
                @else
                    Status Donasi: {{ ucfirst($donation->status) }}
                @endif
            </h1>

            <div class="mt-6 grid gap-3 divide-y divide-slate-100 text-slate-700">
                <div class="flex justify-between py-2 text-sm sm:text-base">
                    <span class="font-medium text-slate-500">Kode Referensi</span>
                    <span class="font-bold text-slate-900">{{ $donation->reference_code }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm sm:text-base">
                    <span class="font-medium text-slate-500">Nominal Donasi</span>
                    <span class="font-bold text-primary-700">Rp{{ number_format($donation->amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm sm:text-base">
                    <span class="font-medium text-slate-500">Tujuan Donasi</span>
                    <span class="font-medium text-slate-900">{{ $donation->program?->title ?? 'Donasi Umum' }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm sm:text-base">
                    <span class="font-medium text-slate-500">Nama Donatur</span>
                    <span class="font-medium text-slate-900">{{ $donation->is_anonymous ? 'Anonim (Hamba Allah)' : ($donation->donor_name ?: '-') }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm sm:text-base">
                    <span class="font-medium text-slate-500">Metode Pembayaran</span>
                    <span id="payment-method-text" class="font-semibold text-primary-900">
                        {{ $donation->payment_method === 'midtrans' ? 'Midtrans' : ($donation->payment_method ?: 'Transfer Manual') }}
                    </span>
                </div>
                <div id="expiry-row" class="flex justify-between py-2 text-sm sm:text-base" style="{{ $expiryTime ? '' : 'display: none;' }}">
                    <span class="font-medium text-slate-500">Batas Waktu Pembayaran</span>
                    <span id="expiry-time-text" class="font-medium text-amber-700">{{ $expiryTime ?? '-' }}</span>
                </div>
                <div id="paid-at-row" class="flex justify-between py-2 text-sm sm:text-base" style="{{ $donation->paid_at ? '' : 'display: none;' }}">
                    <span class="font-medium text-slate-500">Waktu Pembayaran</span>
                    <span id="paid-at-text" class="font-medium text-primary-800">{{ $donation->paid_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
            </div>

            {{-- Container Informasi & Status --}}
            <div id="info-container" class="mt-6">
                @if ($donation->status === 'paid')
                    <div class="rounded-lg border border-primary-200 bg-primary-50 p-5 text-sm leading-6 text-primary-950">
                        <p class="font-semibold text-primary-900">Pembayaran Terkonfirmasi</p>
                        <p class="mt-1">Donasi Anda sebesar <strong>Rp{{ number_format($donation->amount, 0, ',', '.') }}</strong> telah berhasil kami terima. Terima kasih telah menjadi bagian dari perubahan bagi anak-anak Indonesia!</p>
                    </div>
                @elseif ($donation->status === 'pending')
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-5 text-sm leading-6 text-amber-950">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-amber-900">Menunggu Verifikasi Pembayaran</p>
                                <p class="mt-1 text-xs text-amber-800 sm:text-sm">
                                    Silakan selesaikan pembayaran pada kanal yang Anda pilih di Midtrans. Sistem akan otomatis mendeteksi pembayaran Anda.
                                </p>
                            </div>
                            <button type="button" 
                                    id="refresh-status-btn"
                                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded bg-primary-700 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-800 disabled:opacity-50">
                                <svg id="refresh-icon" class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span id="refresh-text">Cek Status Sekarang</span>
                            </button>
                        </div>
                        <p id="poll-indicator" class="mt-3 flex items-center gap-1.5 text-xs text-amber-700">
                            <span class="inline-block size-1.5 animate-ping rounded-full bg-amber-500"></span>
                            <span>Pemeriksaan otomatis aktif setiap 5 detik...</span>
                        </p>
                    </div>
                @elseif ($donation->status === 'expired')
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
                        <p class="font-semibold text-slate-900">Masa Pembayaran Berakhir</p>
                        <p class="mt-1">Batas waktu pembayaran untuk transaksi ini telah kedaluwarsa. Silakan buat donasi baru untuk melanjutkan niat baik Anda.</p>
                    </div>
                @elseif ($donation->status === 'cancelled')
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
                        <p class="font-semibold text-slate-900">Transaksi Dibatalkan</p>
                        <p class="mt-1">Transaksi donasi ini telah dibatalkan. Anda dapat membuat donasi baru kapan saja.</p>
                    </div>
                @else
                    <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-900">
                        <p class="font-semibold">Pembayaran Gagal</p>
                        <p class="mt-1">Transaksi tidak dapat diselesaikan. Silakan coba kembali atau gunakan metode pembayaran lainnya.</p>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('donations.create') }}" class="rounded bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-800">
                    Buat Donasi Baru
                </a>
                <a href="{{ route('programs.index') }}" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Lihat Program
                </a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkStatusUrl = '{{ route('donations.check-status', $donation) }}';
            let currentStatus = '{{ $donation->status }}';
            let pollTimer = null;

            const refreshBtn = document.getElementById('refresh-status-btn');
            const refreshIcon = document.getElementById('refresh-icon');
            const refreshText = document.getElementById('refresh-text');
            const statusBadgeContainer = document.getElementById('status-badge-container');
            const statusTitle = document.getElementById('status-title');
            const paymentMethodText = document.getElementById('payment-method-text');
            const expiryRow = document.getElementById('expiry-row');
            const expiryTimeText = document.getElementById('expiry-time-text');
            const paidAtRow = document.getElementById('paid-at-row');
            const paidAtText = document.getElementById('paid-at-text');
            const infoContainer = document.getElementById('info-container');

            function updateUI(data) {
                if (data.payment_method) {
                    paymentMethodText.textContent = data.payment_method;
                }

                if (data.expiry_time) {
                    expiryTimeText.textContent = data.expiry_time;
                    expiryRow.style.display = 'flex';
                }

                if (data.paid_at) {
                    paidAtText.textContent = data.paid_at;
                    paidAtRow.style.display = 'flex';
                }

                if (data.status === 'paid') {
                    statusBadgeContainer.innerHTML = `
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-bold text-primary-800">
                            <span class="size-2 rounded-full bg-primary-600"></span> Berhasil
                        </span>
                    `;
                    statusTitle.textContent = 'Terima kasih atas dukungannya!';
                    infoContainer.innerHTML = `
                        <div class="rounded-lg border border-primary-200 bg-primary-50 p-5 text-sm leading-6 text-primary-950">
                            <p class="font-semibold text-primary-900">Pembayaran Terkonfirmasi</p>
                            <p class="mt-1">Donasi Anda sebesar <strong>Rp{{ number_format($donation->amount, 0, ',', '.') }}</strong> telah berhasil kami terima. Terima kasih telah menjadi bagian dari perubahan bagi anak-anak Indonesia!</p>
                        </div>
                    `;
                    stopPolling();
                } else if (data.status === 'expired') {
                    statusBadgeContainer.innerHTML = `
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-slate-500"></span> Kedaluwarsa
                        </span>
                    `;
                    statusTitle.textContent = 'Waktu Pembayaran Telah Berakhir';
                    infoContainer.innerHTML = `
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
                            <p class="font-semibold text-slate-900">Masa Pembayaran Berakhir</p>
                            <p class="mt-1">Batas waktu pembayaran untuk transaksi ini telah kedaluwarsa. Silakan buat donasi baru untuk melanjutkan niat baik Anda.</p>
                        </div>
                    `;
                    stopPolling();
                } else if (data.status === 'cancelled') {
                    statusBadgeContainer.innerHTML = `
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                            <span class="size-2 rounded-full bg-slate-500"></span> Dibatalkan
                        </span>
                    `;
                    statusTitle.textContent = 'Transaksi Donasi Dibatalkan';
                    infoContainer.innerHTML = `
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
                            <p class="font-semibold text-slate-900">Transaksi Dibatalkan</p>
                            <p class="mt-1">Transaksi donasi ini telah dibatalkan. Anda dapat membuat donasi baru kapan saja.</p>
                        </div>
                    `;
                    stopPolling();
                } else if (data.status === 'failed') {
                    statusBadgeContainer.innerHTML = `
                        <span id="status-badge" class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-800">
                            <span class="size-2 rounded-full bg-red-600"></span> Gagal
                        </span>
                    `;
                    statusTitle.textContent = 'Pembayaran Belum Berhasil';
                    infoContainer.innerHTML = `
                        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-sm leading-6 text-red-900">
                            <p class="font-semibold">Pembayaran Gagal</p>
                            <p class="mt-1">Transaksi tidak dapat diselesaikan. Silakan coba kembali atau gunakan metode pembayaran lainnya.</p>
                        </div>
                    `;
                    stopPolling();
                }
            }

            async function fetchStatus(isManual = false) {
                if (isManual && refreshBtn) {
                    refreshBtn.disabled = true;
                    if (refreshIcon) refreshIcon.classList.add('animate-spin');
                    if (refreshText) refreshText.textContent = 'Mengecek...';
                }

                try {
                    const response = await fetch(checkStatusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        currentStatus = data.status;
                        updateUI(data);
                    }
                } catch (err) {
                    console.error('Error fetching donation status:', err);
                } finally {
                    if (isManual && refreshBtn) {
                        refreshBtn.disabled = false;
                        if (refreshIcon) refreshIcon.classList.remove('animate-spin');
                        if (refreshText) refreshText.textContent = 'Cek Status Sekarang';
                    }
                }
            }

            function startPolling() {
                if (currentStatus === 'pending') {
                    pollTimer = setInterval(function () {
                        fetchStatus(false);
                    }, 5000); // Polling setiap 5 detik
                }
            }

            function stopPolling() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    fetchStatus(true);
                });
            }

            // Jalankan polling otomatis jika status masih pending
            if (currentStatus === 'pending') {
                startPolling();
            }
        });
    </script>
</x-public-layout>
