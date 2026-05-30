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
 * Logo de partenaire affiché dans le bandeau défilant de la page d'accueil.
 *
 * Distinct du modèle {@see Partenariat} qui, lui, stocke les *demandes* de
 * partenariat soumises via le formulaire public. Ici on ne gère que des logos
 * éditorialisés par l'administrateur. Le logo est porté par Spatie Media
 * Library (collection `logo`).
 */
class Partner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'nom',
        'site_web',
        'is_visible',
        'ordre',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Collection `logo` (single file). Conversion `logo` bornée pour rester
     * net sur fond clair, en WebP synchrone.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('logo')
            ->fit(Manipulations::FIT_MAX, 320, 160)
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
