<?php

namespace App\Models;

use App\Models\Concerns\HasDateRange;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasDateRange, HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'goal',
        'beneficiaries',
        'start_date',
        'end_date',
        'cover_image',
        'status',
        'is_featured',
        'accepts_donation',
        'donation_target',
        'min_donation_amount',
        'qris_image',
        'published_at',
    ];

    protected $attributes = [
        'status' => 'active',
        'is_featured' => false,
        'accepts_donation' => false,
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function donationPackages(): HasMany
    {
        return $this->hasMany(DonationPackage::class)->orderBy('sort_order')->orderBy('id');
    }

    public const STATUS_LABELS = [
        'active' => 'Aktif',
        'inactive' => 'Non-aktif',
    ];

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Program yang sedang berjalan dan membuka donasi.
     */
    #[Scope]
    protected function acceptingDonations(Builder $query): Builder
    {
        return $query->published()->where('accepts_donation', true);
    }

    /**
     * Tambahkan total donasi lunas sebagai atribut "collected_amount".
     */
    #[Scope]
    protected function withCollectedAmount(Builder $query): Builder
    {
        return $query->withSum(['donations as collected_amount' => fn (Builder $query) => $query->where('status', 'paid')], 'amount');
    }

    /**
     * Filter program yang donasi lunasnya sudah (true) atau belum (false) mencapai target.
     */
    #[Scope]
    protected function whereDonationTargetReached(Builder $query, bool $reached = true): Builder
    {
        $collected = Donation::query()
            ->selectRaw('coalesce(sum(amount), 0)')
            ->whereColumn('donations.program_id', 'programs.id')
            ->where('status', 'paid');

        if ($reached) {
            return $query->whereNotNull('donation_target')
                ->where($collected, '>=', DB::raw('programs.donation_target'));
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('donation_target')
            ->orWhere($collected, '<', DB::raw('programs.donation_target')));
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'active';
    }

    public function isAcceptingDonations(): bool
    {
        return $this->isPubliclyVisible() && $this->accepts_donation;
    }

    /**
     * Donatur boleh mengisi nominal sendiri bila ada nominal minimal, atau bila program tidak punya paket.
     */
    public function allowsCustomAmount(): bool
    {
        return $this->min_donation_amount !== null || $this->donationPackages->isEmpty();
    }

    /**
     * Persentase donasi terkumpul terhadap target (0–100). Butuh scope withCollectedAmount.
     */
    public function donationProgress(): float
    {
        if (! $this->donation_target) {
            return 0;
        }

        return min(100, round((int) $this->collected_amount / $this->donation_target * 100, 1));
    }

    public function remainingDonationAmount(): int
    {
        return max(0, (int) $this->donation_target - (int) $this->collected_amount);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'accepts_donation' => 'boolean',
            'donation_target' => 'integer',
            'min_donation_amount' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
