<?php

namespace App\Models;

use Database\Factories\ImpactMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpactMetric extends Model
{
    /** @use HasFactory<ImpactMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'value',
        'suffix',
        'sort_order',
        'is_active',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
