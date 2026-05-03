<?php

namespace App\Models;

use App\Services\VideoEmbedService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Episode extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Spatie Media Library — déclaration des collections supportées.
     * - thumbnail (single) : vignette principale de l'épisode.
     * - gallery (collection) : médias additionnels.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Conversions partagées par les deux collections (cf. story S1.5).
     * thumb 320 × ratio video, card 640, hero 1280 — tailles ciblées
     * pour les EpisodeCard, listes et hero d'accueil.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 320, 180)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->fit(Manipulations::FIT_CROP, 640, 360)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('hero')
            ->fit(Manipulations::FIT_CROP, 1280, 720)
            ->format('webp')
            ->nonQueued();
    }

    protected $fillable = [
        'titre',
        'description',
        'slug',
        'youtube_url',
        'facebook_url',
        'audio_url',
        'type',
        'statut',
        'date_publication',
        'vues',
        'duree',
        'contenu',
        'thumbnail_url',
        'tags',
        'category',
        'category_id',
        'invite_nom',
        'invite_bio',
        'transcript',
    ];

    protected $casts = [
        'date_publication' => 'datetime',
        'vues' => 'integer',
        'duree' => 'integer',
        'tags' => 'array',
    ];

    protected $dates = [
        'date_publication',
        'created_at',
        'updated_at',
    ];



    // Constantes pour les types d'épisodes
    const TYPE_EPISODE = 'episode';
    const TYPE_COULISSE = 'coulisse';
    const TYPE_BONUS = 'bonus';
    const TYPE_SPECIAL = 'special';

    const TYPES = [
        self::TYPE_EPISODE => 'Épisode',
        self::TYPE_COULISSE => 'Coulisse',
        self::TYPE_BONUS => 'Bonus',
        self::TYPE_SPECIAL => 'Spécial',
    ];

    // Constantes pour les statuts
    const STATUT_BROUILLON = 'draft';
    const STATUT_PROGRAMME = 'scheduled';
    const STATUT_PUBLIE = 'published';
    const STATUT_ARCHIVE = 'archived';

    const STATUTS = [
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_PROGRAMME => 'Programmé',
        self::STATUT_PUBLIE => 'Publié',
        self::STATUT_ARCHIVE => 'Archivé',
    ];

    // Boot method pour générer automatiquement le slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($episode) {
            if (empty($episode->slug)) {
                $episode->slug = Str::slug($episode->titre);
            }
        });

        static::updating(function ($episode) {
            if ($episode->isDirty('titre') && empty($episode->getOriginal('slug'))) {
                $episode->slug = Str::slug($episode->titre);
            }
        });
    }

    // Validation rules
    public static function rules()
    {
        return [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'youtube_url' => 'nullable|url|regex:/^https:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/).+/',
            'type' => 'required|in:episode,coulisse,bonus',
            'date_publication' => 'nullable|date',
            'slug' => 'nullable|string|unique:episodes,slug',
            'is_published' => 'boolean'
        ];
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PUBLIE)
                    ->where('date_publication', '<=', now());
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeEpisodes(Builder $query): Builder
    {
        return $query->where('type', 'episode');
    }

    public function scopeCoulisses(Builder $query): Builder
    {
        return $query->where('type', 'coulisse');
    }

    public function scopeBonus(Builder $query): Builder
    {
        return $query->where('type', 'bonus');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('date_publication', 'desc');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('date_publication')
            ->where('date_publication', '>', now());
    }

    public function scopeAired(Builder $query): Builder
    {
        return $query->whereNotNull('date_publication')
            ->where('date_publication', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    // Accessors

    /**
     * Première URL vidéo disponible. La table episodes utilisera
     * `facebook_url` après S3.1 ; en attendant le fallback sur `youtube_url`
     * suffit et le service sait gérer les deux providers.
     */
    public function getVideoUrlAttribute(): ?string
    {
        $facebook = $this->attributes['facebook_url'] ?? null;
        if (is_string($facebook) && $facebook !== '') {
            return $facebook;
        }

        $youtube = $this->attributes['youtube_url'] ?? null;
        return is_string($youtube) && $youtube !== '' ? $youtube : null;
    }

    public function getVideoProviderAttribute(): ?string
    {
        return app(VideoEmbedService::class)->detectProvider($this->video_url);
    }

    public function getVideoIdAttribute(): ?string
    {
        return app(VideoEmbedService::class)->extractId($this->video_url);
    }

    public function getVideoThumbnailAttribute(): ?string
    {
        return app(VideoEmbedService::class)->thumbnail($this->video_url);
    }

    public function getVideoEmbedUrlAttribute(): ?string
    {
        return app(VideoEmbedService::class)->embedUrl($this->video_url);
    }

    public function getYoutubeVideoIdAttribute(): ?string
    {
        $service = app(VideoEmbedService::class);

        return $service->detectProvider($this->youtube_url) === VideoEmbedService::PROVIDER_YOUTUBE
            ? $service->extractId($this->youtube_url)
            : null;
    }

    public function getYoutubeThumbnailAttribute(): ?string
    {
        $service = app(VideoEmbedService::class);

        return $service->detectProvider($this->youtube_url) === VideoEmbedService::PROVIDER_YOUTUBE
            ? $service->thumbnail($this->youtube_url)
            : null;
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        $service = app(VideoEmbedService::class);

        return $service->detectProvider($this->youtube_url) === VideoEmbedService::PROVIDER_YOUTUBE
            ? $service->embedUrl($this->youtube_url)
            : null;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Relation vers la table `categories`. Le nom est volontairement
     * `categoryRelation` pour ne pas entrer en collision avec la colonne
     * legacy `category` (string) qui survivra le temps de la migration admin.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tagsRelation(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'episode_tag');
    }

    public function getIsAiredAttribute(): bool
    {
        return $this->date_publication && $this->date_publication->isPast();
    }

    public function getIsScheduledAttribute(): bool
    {
        return $this->date_publication && $this->date_publication->isFuture();
    }

    // Mutators
    public function setYoutubeUrlAttribute($value)
    {
        // Normaliser l'URL YouTube
        if ($value && !str_starts_with($value, 'https://')) {
            $value = 'https://' . ltrim($value, '/');
        }
        $this->attributes['youtube_url'] = $value;
    }

    public function setTitreAttribute($value)
    {
        $this->attributes['titre'] = $value;
        
        // Générer le slug si pas encore défini
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }



    // Helper methods
    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'episode' => 'Épisode',
            'coulisse' => 'Coulisse',
            'bonus' => 'Bonus',
            default => ucfirst($this->type)
        };
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_published) {
            return 'Brouillon';
        }

        if ($this->is_scheduled) {
            return 'Programmé';
        }

        if ($this->is_aired) {
            return 'Diffusé';
        }

        return 'Publié';
    }

    // Methods pour la recherche
    public static function search($query)
    {
        return static::where('titre', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->published();
    }

    /**
     * Obtenir le titre
     */
    public function getTitreAttribute($value)
    {
        return $value;
    }

    /**
     * Obtenir la description
     */
    public function getDescriptionAttribute($value)
    {
        return $value;
    }

    /**
     * Obtenir le titre SEO
     */
    public function getSeoTitleAttribute($value)
    {
        return $value ?: $this->attributes['titre'] ?? null;
    }

    /**
     * Obtenir la description SEO
     */
    public function getSeoDescriptionAttribute($value)
    {
        return $value ?: substr(strip_tags($this->attributes['description'] ?? ''), 0, 160);
    }

    /**
     * Obtenir les mots-clés SEO
     */
    public function getSeoKeywordsAttribute($value)
    {
        return $value;
    }

    /**
     * Obtenir l'URL complète de la vidéo YouTube
     */
    public function getYoutubeUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        if ($this->youtube_id) {
            return "https://www.youtube.com/watch?v={$this->youtube_id}";
        }

        return null;
    }

    /**
     * Obtenir la durée formatée
     */
    public function getDureeFormatteeAttribute()
    {
        if (!$this->duree) {
            return null;
        }

        $minutes = floor($this->duree / 60);
        $seconds = $this->duree % 60;

        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }

        return sprintf('0:%02d', $seconds);
    }

    /**
     * Obtenir le type traduit
     */
    public function getTypeLibelleAttribute()
    {
        $locale = app()->getLocale();
        
        $types = [
            'fr' => [
                self::TYPE_EPISODE => 'Épisode',
                self::TYPE_COULISSE => 'Coulisse',
                self::TYPE_BONUS => 'Bonus',
                self::TYPE_SPECIAL => 'Spécial',
            ],
            'en' => [
                self::TYPE_EPISODE => 'Episode',
                self::TYPE_COULISSE => 'Behind the Scenes',
                self::TYPE_BONUS => 'Bonus',
                self::TYPE_SPECIAL => 'Special',
            ],
        ];

        return $types[$locale][$this->type] ?? $this->type;
    }

    /**
     * Obtenir le statut traduit
     */
    public function getStatutLibelleAttribute()
    {
        $locale = app()->getLocale();
        
        $statuts = [
            'fr' => [
                self::STATUT_BROUILLON => 'Brouillon',
                self::STATUT_PROGRAMME => 'Programmé',
                self::STATUT_PUBLIE => 'Publié',
                self::STATUT_ARCHIVE => 'Archivé',
            ],
            'en' => [
                self::STATUT_BROUILLON => 'Draft',
                self::STATUT_PROGRAMME => 'Scheduled',
                self::STATUT_PUBLIE => 'Published',
                self::STATUT_ARCHIVE => 'Archived',
            ],
        ];

        return $statuts[$locale][$this->statut] ?? $this->statut;
    }

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementViews()
    {
        $this->increment('vues');
    }

    /**
     * Obtenir l'épisode précédent
     */
    public function getPreviousEpisode()
    {
        return static::published()
            ->where('date_publication', '<', $this->date_publication)
            ->orderBy('date_publication', 'desc')
            ->first();
    }

    /**
     * Obtenir l'épisode suivant
     */
    public function getNextEpisode()
    {
        return static::published()
            ->where('date_publication', '>', $this->date_publication)
            ->orderBy('date_publication', 'asc')
            ->first();
    }

    /**
     * Obtenir des épisodes similaires
     */
    public function getSimilarEpisodes($limit = 4)
    {
        return static::published()
            ->where('id', '!=', $this->id)
            ->where('type', $this->type)
            ->orderBy('vues', 'desc')
            ->orderBy('date_publication', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir l'URL de la miniature
     */
    public function getThumbnailUrl($conversion = 'medium')
    {
        // Utiliser la miniature YouTube ou l'URL de miniature stockée
        return $this->thumbnail_url ?: $this->youtube_thumbnail;
    }

    /**
     * Obtenir l'URL complète de l'épisode
     */
    public function getUrlAttribute()
    {
        $locale = app()->getLocale();
        return route('episodes.show', ['locale' => $locale, 'slug' => $this->slug]);
    }
} 