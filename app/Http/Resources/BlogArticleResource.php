<?php

namespace App\Http\Resources;

use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var BlogArticle $article */
        $article = $this->resource;
        $locale = $request->route('locale') ?? app()->getLocale();

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'titre' => $article->titre,
            'extrait' => $article->extrait,
            'image' => $article->image,
            'date_publication' => optional($article->date_publication)->toIso8601String(),
            'categorie' => $article->categorie,
            'mots_cles' => $article->mots_cles ?? [],
            'vues' => (int) ($article->vues ?? 0),
            'reading_time' => $article->reading_time,
            'url' => "/{$locale}/blog/{$article->slug}",
        ];
    }
}
