<?php

namespace App\Models;

use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'reference_code',
        'donation_type',
        'amount',
        'donor_name',
        'donor_email',
        'donor_phone',
        'is_anonymous',
        'message',
        'payment_method',
        'status',
        'paid_at',
    ];

    protected $attributes = [
        'donation_type' => 'general',
        'is_anonymous' => false,
        'payment_method' => 'manual_transfer',
        'status' => 'pending',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'paid' => 'Lunas',
        'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa',
        'cancelled' => 'Batal',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function paymentMethodLabel(): string
    {
        return match (strtolower((string) $this->payment_method)) {
            'qris' => 'QRIS',
            'midtrans' => 'Midtrans',
            'manual_transfer', null, '' => 'Transfer Manual',
            default => strtoupper((string) $this->payment_method),
        };
    }

    public function usesMidtrans(): bool
    {
        return strtolower((string) $this->payment_method) === 'midtrans';
    }

    public function isClosedWithoutPayment(): bool
    {
        return in_array($this->status, ['failed', 'expired', 'cancelled'], true);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }
}
