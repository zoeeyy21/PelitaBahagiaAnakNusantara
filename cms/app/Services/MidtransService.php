<?php

namespace App\Services;

use App\Models\Donation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('midtrans.server_key', '');
        Config::$clientKey = (string) config('midtrans.client_key', '');
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Create Snap transaction token and redirect URL for a donation.
     *
     * @throws Exception
     */
    public function createSnapTransaction(Donation $donation): object
    {
        if (empty(Config::$serverKey)) {
            throw new Exception('Midtrans Server Key belum dikonfigurasi pada environment.');
        }

        $donorName = $donation->is_anonymous
            ? 'Donatur'
            : ($donation->donor_name ?: 'Donatur');

        $programTitle = $donation->program?->title ?? 'Donasi Umum';

        $customerDetails = [
            'first_name' => $donorName,
        ];

        if (! empty($donation->donor_email)) {
            $customerDetails['email'] = $donation->donor_email;
        }

        if (! empty($donation->donor_phone)) {
            $customerDetails['phone'] = $donation->donor_phone;
        }

        $params = [
            'transaction_details' => [
                'order_id' => $donation->reference_code,
                'gross_amount' => (int) $donation->amount,
            ],
            'customer_details' => $customerDetails,
            'item_details' => [
                [
                    'id' => $donation->program_id ? 'program-'.$donation->program_id : 'donation-general',
                    'price' => (int) $donation->amount,
                    'quantity' => 1,
                    'name' => Str::limit($programTitle, 50, ''),
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'day',
                'duration' => 1,
            ],
        ];

        return Snap::createTransaction($params);
    }

    /**
     * Map Midtrans raw status to internal donation status.
     */
    public function mapTransactionStatus(string $midtransStatus, ?string $fraudStatus = null): string
    {
        return match ($midtransStatus) {
            'capture' => ($fraudStatus === 'challenge') ? 'pending' : 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'failed',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'failure' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Format Midtrans payment_type into human-friendly label.
     */
    public function formatPaymentType(?string $type, object|array|null $response = null): string
    {
        if (! $type || $type === 'manual_transfer') {
            return $type === 'manual_transfer' ? 'Transfer Manual' : 'Midtrans';
        }

        $res = is_array($response) ? (object) $response : $response;

        return match ($type) {
            'qris' => isset($res->acquirer) ? 'QRIS ('.strtoupper($res->acquirer).')' : 'QRIS',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
            'bank_transfer' => $this->formatBankTransfer($res),
            'echannel' => 'Mandiri Bill Payment',
            'cstore' => isset($res->store) ? ucfirst($res->store) : 'Convenience Store',
            'credit_card' => 'Kartu Kredit'.(isset($res->bank) ? ' ('.strtoupper($res->bank).')' : ''),
            'akulaku' => 'Akulaku PayLater',
            'kredivo' => 'Kredivo',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function formatBankTransfer(?object $res): string
    {
        if (! $res) {
            return 'Virtual Account';
        }

        if (isset($res->va_numbers) && is_array($res->va_numbers) && count($res->va_numbers) > 0) {
            $bank = strtoupper($res->va_numbers[0]->bank ?? '');

            return $bank ? "Virtual Account {$bank}" : 'Virtual Account';
        }

        if (isset($res->permata_va_number)) {
            return 'Virtual Account Permata';
        }

        return 'Virtual Account';
    }

    /**
     * Fetch the transaction status from Midtrans and update the donation record.
     *
     * @return array{synced: bool, status: string, payment_method: string, expiry_time: ?string, message: string}
     */
    public function syncTransactionStatus(Donation $donation): array
    {
        if (empty(Config::$serverKey)) {
            return [
                'synced' => false,
                'status' => $donation->status,
                'payment_method' => $donation->payment_method,
                'expiry_time' => null,
                'message' => 'Midtrans Server Key belum dikonfigurasi.',
            ];
        }

        try {
            $response = Transaction::status($donation->reference_code);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), '404')) {
                return [
                    'synced' => false,
                    'status' => $donation->status,
                    'payment_method' => $donation->payment_method,
                    'expiry_time' => null,
                    'message' => 'Transaksi belum dipilih metode pembayaran di Midtrans.',
                ];
            }

            return [
                'synced' => false,
                'status' => $donation->status,
                'payment_method' => $donation->payment_method,
                'expiry_time' => null,
                'message' => 'Gagal menghubungi Midtrans: '.$e->getMessage(),
            ];
        }

        $rawStatus = (string) ($response->transaction_status ?? 'pending');
        $fraudStatus = $response->fraud_status ?? null;
        $mappedStatus = $this->mapTransactionStatus($rawStatus, $fraudStatus);

        $paymentType = $response->payment_type ?? null;
        $formattedMethod = $this->formatPaymentType($paymentType, $response);

        $donation->status = $mappedStatus;
        if ($formattedMethod) {
            $donation->payment_method = $formattedMethod;
        }

        if ($mappedStatus === 'paid' && ! $donation->paid_at) {
            $donation->paid_at = isset($response->settlement_time)
                ? Carbon::parse($response->settlement_time)
                : now();
        }

        $donation->save();

        return [
            'synced' => true,
            'status' => $donation->status,
            'payment_method' => $donation->payment_method,
            'expiry_time' => $response->expiry_time ?? null,
            'message' => 'Status berhasil disinkronkan.',
        ];
    }
}
