<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogArticleResource;
use App\Http\Resources\EpisodeResource;
use App\Models\AstucesSoumise;
use App\Models\BlogArticle;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Story S3.5 — Recherche globale.
 * - GET /api/search/suggestions : autocomplete léger pour l'header.
 * - GET /{locale}/search : page Inertia avec résultats agrégés.
 */
class SearchController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:80']);
        $term = $request->string('q')->toString();

        $episodes = Episode::published()
            ->where(function ($q) use ($term) {
                $q->where('titre', 'like', "%{$term}%")
                    ->orWhere('invite_nom', 'like', "%{$term}%");
            })
            ->limit(5)
            ->get(['id', 'slug', 'titre', 'type'])
            ->map(fn ($e) => [
                'type' => 'episode',
                'label' => $e->titre,
                'href' => "/{$request->route('locale', app()->getLocale())}/episodes/{$e->slug}",
            ]);

        $articles = BlogArticle::publishedAndVisible()
            ->where('titre', 'like', "%{$term}%")
            ->limit(5)
            ->get(['id', 'slug', 'titre'])
            ->map(fn ($a) => [
                'type' => 'article',
                'label' => $a->titre,
                'href' => "/{$request->route('locale', app()->getLocale())}/blog/{$a->slug}",
            ]);

        return response()->json([
            'suggestions' => $episodes->concat($articles)->take(8)->values(),
        ]);
    }

    public function index(Request $request): Response
    {
        $term = trim($request->string('q')->toString());

        if ($term === '' || mb_strlen($term) < 2) {
            return Inertia::render('Search', [
                'query' => $term,
                'results' => [
                    'episodes' => [],
                    'articles' => [],
                    'astuces' => [],
                ],
            ]);
        }

        $episodes = Episode::published()
            ->where(function ($q) use ($term) {
                $q->where('titre', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('invite_nom', 'like', "%{$term}%");
            })
            ->recent()
            ->limit(20)
            ->get();

        $articles = BlogArticle::publishedAndVisible()
            ->where(function ($q) use ($term) {
                $q->where('titre', 'like', "%{$term}%")
                    ->orWhere('extrait', 'like', "%{$term}%");
            })
            ->recent()
            ->limit(20)
            ->get();

        $astuces = AstucesSoumise::approuve()
            ->where(function ($q) use ($term) {
                $q->where('titre_astuce', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'titre_astuce']);

        return Inertia::render('Search', [
            'query' => $term,
            'results' => [
                'episodes' => EpisodeResource::collection($episodes),
                'articles' => BlogArticleResource::collection($articles),
                'astuces' => $astuces->map(fn ($a) => [
                    'id' => $a->id,
                    'titre' => $a->titre_astuce,
                ])->values(),
            ],
        ]);
    }
}
