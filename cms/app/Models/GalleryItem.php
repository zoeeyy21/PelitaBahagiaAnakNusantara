<?php

namespace App\Models;

use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'activity_id',
        'title',
        'caption',
        'image_path',
        'sort_order',
        'is_published',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_published' => true,
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
