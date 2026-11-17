<?php

namespace App\Models;

use App\Models\Concerns\HasDateRange;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasDateRange, HasFactory;

    protected $fillable = [
        'program_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'status',
        'start_date',
        'end_date',
        'published_at',
    ];

    protected $attributes = [
        'status' => 'published',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Semua gambar kegiatan berurutan: cover (thumbnail) lalu galeri.
     *
     * @return list<string>
     */
    public function imagePaths(): array
    {
        return array_values(array_filter([
            $this->cover_image,
            ...$this->galleryItems->pluck('image_path')->all(),
        ]));
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
