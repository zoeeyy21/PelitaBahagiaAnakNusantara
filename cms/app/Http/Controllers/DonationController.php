<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Program;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DonationController extends Controller
{
    /**
     * Nominal minimal untuk donasi umum (tanpa program).
     */
    public const GENERAL_MIN_AMOUNT = 10000;

    /**
     * Pilihan nominal cepat untuk donasi umum.
     *
     * @var list<int>
     */
    public const GENERAL_PRESET_AMOUNTS = [50000, 100000, 250000, 500000];

    public function create(Request $request): View
    {
        return view('public.donations.create', [
            'selectedProgram' => $request->integer('program_id') ?: null,
        ]);
    }

    public function store(Request $request, MidtransService $midtransService): JsonResponse|RedirectResponse
    {
        $program = $request->filled('program_id')
            ? Program::query()->acceptingDonations()->with('donationPackages')->find($request->input('program_id'))
            : null;

        $validated = $request->validate([
            'program_id' => ['nullable', Rule::exists('programs', 'id')->where('status', 'active')->where('accepts_donation', true)],
            'amount' => ['required', 'integer', ...$this->amountRules($program)],
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['required', 'string', 'max:30'],
            'is_anonymous' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.in' => 'Pilih salah satu paket donasi yang tersedia.',
        ]);

        $isAnonymous = (bool) ($validated['is_anonymous'] ?? false);
        $programId = $validated['program_id'] ?? null;

        $donation = Donation::create([
            'program_id' => $programId,
            'reference_code' => 'YPB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'donation_type' => $programId ? 'program' : 'general',
            'amount' => $validated['amount'],
            'donor_name' => $validated['donor_name'],
            'donor_email' => $validated['donor_email'] ?? null,
            'donor_phone' => $validated['donor_phone'],
            'is_anonymous' => $isAnonymous,
            'message' => $validated['message'] ?? null,
            'payment_method' => 'midtrans',
            'status' => 'pending',
        ]);

        try {
            $snapTransaction = $midtransService->createSnapTransaction($donation);
            $snapToken = $snapTransaction->token;
        } catch (Throwable $e) {
            Log::error('Midtrans Snap Error: '.$e->getMessage(), [
                'donation_id' => $donation->id,
                'reference_code' => $donation->reference_code,
            ]);

            $donation->update(['status' => 'failed']);

            $errorMessage = 'Gagal memproses pembayaran melalui Midtrans: '.$e->getMessage().'. Silakan coba beberapa saat lagi atau hubungi pihak yayasan.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return back()->withInput()->withErrors([
                'amount' => $errorMessage,
            ]);
        }

        Cache::put("donation_snap_token_{$donation->reference_code}", $snapToken, now()->addDay());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'redirect_url' => $snapTransaction->redirect_url ?? null,
                'donation_url' => route('donations.show', $donation),
                'reference_code' => $donation->reference_code,
            ]);
        }

        return redirect()->route('donations.show', $donation)
            ->with('snap_token', $snapToken);
    }

    /**
     * Program dengan paket saja: nominal harus salah satu paket, kecuali nominal minimal custom diisi.
     *
     * @return list<mixed>
     */
    private function amountRules(?Program $program): array
    {
        if (! $program) {
            return ['min:'.self::GENERAL_MIN_AMOUNT];
        }

        $packageAmounts = $program->donationPackages->pluck('amount')->all();

        if (! $program->allowsCustomAmount()) {
            return [Rule::in($packageAmounts)];
        }

        $minimum = $program->min_donation_amount ?? self::GENERAL_MIN_AMOUNT;

        return [function (string $attribute, mixed $value, \Closure $fail) use ($minimum, $packageAmounts): void {
            if ((int) $value < $minimum && ! in_array((int) $value, $packageAmounts, true)) {
                $fail('Nominal donasi minimal Rp'.number_format($minimum, 0, ',', '.').'.');
            }
        }];
    }

    public function show(Donation $donation, MidtransService $midtransService): View
    {
        $donation->load('program');

        $expiryTime = null;
        if ($donation->status === 'pending' && $donation->payment_method !== 'manual_transfer') {
            $syncResult = $midtransService->syncTransactionStatus($donation);
            $expiryTime = $syncResult['expiry_time'] ?? null;
        }

        $snapToken = Cache::get("donation_snap_token_{$donation->reference_code}") ?? session('snap_token');

        return view('public.donations.show', [
            'donation' => $donation,
            'expiryTime' => $expiryTime,
            'snapToken' => $snapToken,
        ]);
    }

    public function checkStatus(Donation $donation, MidtransService $midtransService): JsonResponse
    {
        $expiryTime = null;
        if ($donation->payment_method !== 'manual_transfer') {
            $syncResult = $midtransService->syncTransactionStatus($donation);
            $expiryTime = $syncResult['expiry_time'] ?? null;
        }

        $statusLabels = [
            'paid' => 'Berhasil',
            'pending' => 'Menunggu Pembayaran',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
        ];

        $snapToken = Cache::get("donation_snap_token_{$donation->reference_code}");

        return response()->json([
            'status' => $donation->status,
            'status_label' => $statusLabels[$donation->status] ?? ucfirst($donation->status),
            'payment_method' => $donation->payment_method ?: 'Midtrans',
            'paid_at' => $donation->paid_at?->format('d M Y H:i'),
            'expiry_time' => $expiryTime,
            'snap_token' => $snapToken,
            'is_finished' => in_array($donation->status, ['paid', 'failed', 'expired', 'cancelled'], true),
        ]);
    }
}
