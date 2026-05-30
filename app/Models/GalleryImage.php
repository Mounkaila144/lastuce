<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Image de la galerie publique (tournages, coulisses, événements…).
 * L'image elle-même est portée par Spatie Media Library, collection `image`.
 */
class GalleryImage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'titre',
        'description',
        'is_visible',
        'ordre',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Collection `image` (single file) — mêmes conversions WebP que les
     * épisodes pour rester cohérent côté CDN.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 400, 400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->fit(Manipulations::FIT_MAX, 800, 800)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('hero')
            ->fit(Manipulations::FIT_MAX, 1600, 1600)
            ->format('webp')
            ->nonQueued();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderByDesc('created_at');
    }
}
