<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Episode;
use App\Models\Category;
use App\Http\Resources\EpisodeResource;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class EpisodeController extends Controller
{
    /**
     * Liste paginée des épisodes avec filtres (story S3.3, Inertia/Vue).
     */
    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'recent')->toString();
        $type = $request->string('type', 'all')->toString();
        $categorySlug = $request->string('category')->toString();
        $month = $request->string('month')->toString();
        $search = trim($request->string('search')->toString());

        $query = Episode::published();

        if ($type !== 'all' && in_array($type, array_keys(Episode::TYPES), true)) {
            $query->where('type', $type);
        }

        if ($categorySlug !== '') {
            $query->whereHas('categoryRelation', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $m] = explode('-', $month);
            $query->whereYear('date_publication', (int) $year)
                ->whereMonth('date_publication', (int) $m);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('invite_nom', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'oldest' => $query->orderBy('date_publication', 'asc'),
            'popular' => $query->orderBy('vues', 'desc'),
            'title' => $query->orderBy('titre', 'asc'),
            default => $query->orderBy('date_publication', 'desc'),
        };

        $paginator = $query->paginate(12)->withQueryString();

        $categories = Category::orderBy('position')->orderBy('nom')->get(['id', 'nom', 'slug']);

        return Inertia::render('Episodes/Index', [
            'episodes' => EpisodeResource::collection($paginator),
            'filters' => [
                'sort' => $sort,
                'type' => $type,
                'category' => $categorySlug,
                'month' => $month,
                'search' => $search,
            ],
            'options' => [
                'types' => collect(Episode::TYPES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'sorts' => [
                    ['value' => 'recent', 'label' => 'Plus récents'],
                    ['value' => 'oldest', 'label' => 'Plus anciens'],
                    ['value' => 'popular', 'label' => 'Plus populaires'],
                    ['value' => 'title', 'label' => 'Titre A-Z'],
                ],
                'categories' => $categories->map(fn ($c) => ['value' => $c->slug, 'label' => $c->nom])->values(),
                'months' => $this->availableMonths(),
            ],
        ]);
    }

    /**
     * Liste de "YYYY-MM" pour le filtre date. On groupe côté PHP : c'est
     * portable MySQL/SQLite et la volumétrie reste raisonnable.
     */
    private function availableMonths(): array
    {
        return Cache::remember('episodes.available_months', 3600, function () {
            return Episode::published()
                ->whereNotNull('date_publication')
                ->orderBy('date_publication', 'desc')
                ->pluck('date_publication')
                ->map(fn ($d) => optional($d)->format('Y-m'))
                ->filter()
                ->unique()
                ->values()
                ->map(fn ($ym) => ['value' => $ym, 'label' => $ym])
                ->all();
        });
    }

    /**
     * Fiche épisode (story S3.4, Inertia/Vue).
     *
     * Compteur de vues : on s'appuie sur la session (clé ad-hoc) pour ne
     * compter qu'une fois par visiteur authentifié au navigateur. La logique
     * legacy par IP est conservée comme fallback (utile pour les visiteurs
     * qui refusent les cookies de session).
     */
    public function show(Request $request, $locale, $slug): Response
    {
        $episode = Episode::where('slug', $slug)
            ->published()
            ->with(['media', 'tagsRelation', 'categoryRelation'])
            ->firstOrFail();

        $sessionKey = 'episode_seen.' . $episode->id;
        if (! $request->session()->has($sessionKey)) {
            $cacheKey = 'episode_view.' . $episode->id . '.' . $request->ip();
            if (! Cache::has($cacheKey)) {
                $episode->increment('vues');
                Cache::put($cacheKey, true, 3600);
            }
            $request->session()->put($sessionKey, true);
        }

        $similar = Episode::published()
            ->where('type', $episode->type)
            ->where('id', '!=', $episode->id)
            ->recent()
            ->limit(3)
            ->get();

        $previous = Episode::published()
            ->where('date_publication', '<', $episode->date_publication)
            ->orderBy('date_publication', 'desc')
            ->first();

        $next = Episode::published()
            ->where('date_publication', '>', $episode->date_publication)
            ->orderBy('date_publication', 'asc')
            ->first();

        $resource = (new EpisodeResource($episode))->toArray($request);

        return Inertia::render('Episodes/Show', [
            'episode' => array_merge($resource, [
                'contenu' => $episode->contenu,
                'transcript' => $episode->transcript,
                'invite_bio' => $episode->invite_bio,
                'category_label' => optional($episode->categoryRelation)->nom ?? $episode->category,
                'category_slug' => optional($episode->categoryRelation)->slug,
                'tags' => $episode->tagsRelation->map(fn ($t) => [
                    'slug' => $t->slug,
                    'nom' => $t->nom,
                ])->all(),
            ]),
            'similar' => EpisodeResource::collection($similar),
            'previous' => $previous ? [
                'slug' => $previous->slug,
                'titre' => $previous->titre,
            ] : null,
            'next' => $next ? [
                'slug' => $next->slug,
                'titre' => $next->titre,
            ] : null,
            'seo' => [
                'title' => $episode->titre,
                'description' => $episode->description ?: \Illuminate\Support\Str::limit(strip_tags((string) $episode->contenu), 160),
                'image' => $episode->thumbnail_url ?? $resource['video_thumbnail'],
                'video_url' => $resource['video_embed_url'],
                'video_provider' => $resource['video_provider'],
                'published_time' => optional($episode->date_publication)->toIso8601String(),
                'duration_iso' => $episode->duree ? 'PT' . (int) $episode->duree . 'S' : null,
            ],
        ]);
    }

    /**
     * API pour charger plus d'épisodes (AJAX)
     */
    public function loadMore(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'type' => 'sometimes|string|in:all,episode,coulisse,bonus',
            'search' => 'sometimes|string|max:100'
        ]);

        try {
            $perPage = 6;
            $page = $request->get('page');
            $typeFilter = $request->get('type', 'all');
            $search = $request->get('search');

            $query = Episode::published()->with('media');

            // Filtres
            if ($typeFilter !== 'all') {
                switch ($typeFilter) {
                    case 'episode':
                        $query->episodes();
                        break;
                    case 'coulisse':
                        $query->coulisses();
                        break;
                    case 'bonus':
                        $query->bonus();
                        break;
                }
            }

            if ($search) {
                $query->search($search);
            }

            $episodes = $query->recent()
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'episodes' => $episodes->items(),
                'has_more' => $episodes->hasMorePages(),
                'current_page' => $episodes->currentPage(),
                'total' => $episodes->total()
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du chargement d\'épisodes supplémentaires: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('Erreur lors du chargement')
            ], 500);
        }
    }

    /**
     * Flux RSS des épisodes
     */
    public function rss()
    {
        try {
            $episodes = Cache::remember('episodes_rss', 1800, function () {
                return Episode::published()
                    ->with('media')
                    ->recent()
                    ->limit(20)
                    ->get();
            });

            return response()->view('pages.episodes.rss', compact('episodes'))
                ->header('Content-Type', 'application/rss+xml');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du flux RSS: ' . $e->getMessage());
            abort(503, __('Service temporairement indisponible'));
        }
    }

    /**
     * Archive des épisodes par année/mois
     */
    public function archive(Request $request, $year = null, $month = null)
    {
        try {
            $query = Episode::published();

            if ($year) {
                $query->whereYear('date_diffusion', $year);
                
                if ($month) {
                    $query->whereMonth('date_diffusion', $month);
                }
            }

            $episodes = $query->with('media')
                ->recent()
                ->paginate(12);

            // Années et mois disponibles pour la navigation
            $archiveData = Cache::remember('episodes_archive_data', 3600, function () {
                return Episode::published()
                    ->selectRaw('YEAR(date_diffusion) as year, MONTH(date_diffusion) as month, COUNT(*) as count')
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->get()
                    ->groupBy('year');
            });

            return view('pages.episodes.archive', compact(
                'episodes',
                'archiveData',
                'year',
                'month'
            ));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'affichage des archives: ' . $e->getMessage());
            
            return redirect(\App\Helpers\LocalizationHelper::getLocalizedRoute('episodes.index'))
                ->with('error', __('Une erreur est survenue lors du chargement des archives.'));
        }
    }

    /**
     * Recherche avancée d'épisodes
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|string|in:all,episode,coulisse,bonus',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'duration_min' => 'sometimes|integer|min:1',
            'duration_max' => 'sometimes|integer|gte:duration_min'
        ]);

        try {
            $query = Episode::published()->with('media');
            
            // Recherche textuelle
            $searchTerm = $request->get('q');
            $query->search($searchTerm);

            // Filtres avancés
            if ($request->filled('type') && $request->get('type') !== 'all') {
                $type = $request->get('type');
                switch ($type) {
                    case 'episode':
                        $query->episodes();
                        break;
                    case 'coulisse':
                        $query->coulisses();
                        break;
                    case 'bonus':
                        $query->bonus();
                        break;
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('date_diffusion', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('date_diffusion', '<=', $request->get('date_to'));
            }

            if ($request->filled('duration_min')) {
                $query->where('duree', '>=', $request->get('duration_min'));
            }

            if ($request->filled('duration_max')) {
                $query->where('duree', '<=', $request->get('duration_max'));
            }

            $episodes = $query->recent()->paginate(12)->withQueryString();

            return view('pages.episodes.search', compact('episodes', 'searchTerm'));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche d\'épisodes: ' . $e->getMessage());
            
            return redirect(\App\Helpers\LocalizationHelper::getLocalizedRoute('episodes.index'))
                ->with('error', __('Une erreur est survenue lors de la recherche.'));
        }
    }

    /**
     * Playlist d'épisodes (pour écoute continue)
     */
    public function playlist(Request $request, $type = 'all')
    {
        try {
            $query = Episode::published()
                ->whereNotNull('youtube_url')
                ->with('media');

            switch ($type) {
                case 'episode':
                    $query->episodes();
                    break;
                case 'coulisse':
                    $query->coulisses();
                    break;
                case 'bonus':
                    $query->bonus();
                    break;
                case 'recent':
                    $query->where('date_diffusion', '>=', now()->subMonths(3));
                    break;
                case 'popular':
                    $query->orderBy('vues', 'desc');
                    break;
                default:
                    // Tous les épisodes
                    break;
            }

            if ($type !== 'popular') {
                $query->recent();
            }

            $episodes = $query->get();

            return view('pages.episodes.playlist', compact('episodes', 'type'));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération de la playlist: ' . $e->getMessage());
            
            return redirect(\App\Helpers\LocalizationHelper::getLocalizedRoute('episodes.index'))
                ->with('error', __('Une erreur est survenue lors de la génération de la playlist.'));
        }
    }
} 