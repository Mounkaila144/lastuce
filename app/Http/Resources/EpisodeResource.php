<?php

namespace App\Http\Resources;

use App\Models\Episode;
use App\Services\VideoEmbedService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation Inertia partagée pour un épisode. Toutes les pages publiques
 * (Home, liste, fiche) consomment ce shape — coté front c'est typé via
 * `resources/js/types/domain.ts:EpisodeListItem`.
 */
class EpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Episode $episode */
        $episode = $this->resource;
        $service = app(VideoEmbedService::class);

        $videoUrl = $episode->facebook_url ?: $episode->youtube_url;
        $locale = $request->route('locale') ?? app()->getLocale();

        return [
            'id' => $episode->id,
            'slug' => $episode->slug,
            'titre' => $episode->titre,
            'description' => $episode->description,
            'type' => $episode->type,
            'type_label' => $episode->type_libelle ?? $episode->type,
            'date_publication' => optional($episode->date_publication)->toIso8601String(),
            'duree' => $episode->duree,
            'vues' => (int) ($episode->vues ?? 0),
            'category' => $episode->category,
            'invite_nom' => $episode->invite_nom,
            'thumbnail_url' => $episode->getFirstMediaUrl('thumbnail', 'card')
                ?: ($episode->thumbnail_url ?: $service->thumbnail($videoUrl)),
            'video_url' => $videoUrl,
            'video_provider' => $service->detectProvider($videoUrl),
            'video_thumbnail' => $service->thumbnail($videoUrl),
            'video_embed_url' => $service->embedUrl($videoUrl),
            'url' => "/{$locale}/episodes/{$episode->slug}",
        ];
    }
}
