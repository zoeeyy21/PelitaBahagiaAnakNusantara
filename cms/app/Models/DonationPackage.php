<?php

namespace App\Models;

use Database\Factories\DonationPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationPackage extends Model
{
    /** @use HasFactory<DonationPackageFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'amount',
        'sort_order',
    ];

    protected $attributes = [
        'sort_order' => 0,
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
