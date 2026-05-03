<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogArticleResource;
use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Blog public — Epic E7 (stories S7.1, S7.2, S7.3).
 *
 * Toutes les vues publiques passent par Inertia. Le flux RSS reste en XML
 * (réponse non Inertia, cf. story S3.6).
 */
class BlogController extends Controller
{
    private const PER_PAGE = 9;

    /**
     * S7.1 — Liste paginée. Le front rend Pages/Blog/Index.vue.
     */
    public function index(Request $request, $locale): Response
    {
        return $this->renderIndex($request);
    }

    /**
     * Implémentation partagée par index/archive/search. On garde le binding
     * route -> contrôleur séparé de la signature interne pour éviter que
     * Laravel n'injecte {locale} dans un argument typé ?int.
     */
    private function renderIndex(Request $request, ?int $year = null, ?int $month = null): Response
    {
        $sort = $request->string('sort', 'recent')->toString();
        $categorie = $request->string('categorie')->toString();
        $search = trim($request->string('search')->toString());
        $year = $year ?? ($request->filled('year') ? (int) $request->input('year') : null);
        $month = $month ?? ($request->filled('month') ? (int) $request->input('month') : null);

        $query = BlogArticle::publishedAndVisible();

        if ($search !== '') {
            $query->search($search);
        }
        if ($categorie !== '') {
            $query->where('categorie', $categorie);
        }
        if ($year) {
            $query->whereYear('date_publication', $year);
            if ($month) {
                $query->whereMonth('date_publication', $month);
            }
        }

        match ($sort) {
            'oldest' => $query->orderBy('date_publication', 'asc'),
            'popular' => $query->orderBy('vues', 'desc'),
            default => $query->orderBy('date_publication', 'desc'),
        };

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('Blog/Index', [
            'articles' => BlogArticleResource::collection($paginator),
            'filters' => [
                'sort' => $sort,
                'categorie' => $categorie,
                'search' => $search,
                'year' => $year,
                'month' => $month,
            ],
            'options' => [
                'sorts' => [
                    ['value' => 'recent', 'label' => 'Plus récents'],
                    ['value' => 'oldest', 'label' => 'Plus anciens'],
                    ['value' => 'popular', 'label' => 'Plus populaires'],
                ],
                'categories' => $this->availableCategories(),
                'archives' => $this->availableArchives(),
            ],
            'sidebar' => $this->sidebar(),
        ]);
    }

    /**
     * Alias propre pour /blog/archive/{year?}/{month?}. On renvoie sur la
     * même page Inertia avec les paramètres positionnels copiés en filtres.
     * Le segment {locale} doit être déclaré pour que Laravel injecte les
     * bons paramètres positionnels (cf. show/category/tag).
     */
    public function archive(Request $request, $locale, $year = null, $month = null): Response
    {
        return $this->renderIndex(
            $request,
            $year !== null ? (int) $year : null,
            $month !== null ? (int) $month : null,
        );
    }

    /**
     * Conservé pour compat URL — délègue à index() avec ?search=... validé
     * mollement (la valid stricte legacy renvoyait 422 sur recherche vide,
     * ce qui n'a pas de sens pour une page publique).
     */
    public function search(Request $request, $locale): Response
    {
        if ($request->filled('q') && ! $request->filled('search')) {
            $request->merge(['search' => $request->input('q')]);
        }

        return $this->renderIndex($request);
    }

    /**
     * S7.2 — Fiche article.
     *
     * Compteur de vues : session (1 fois par visiteur) + cache IP fallback,
     * même mécanisme que EpisodeController::show pour rester cohérent.
     */
    public function show(Request $request, $locale, $slug): Response
    {
        $article = BlogArticle::where('slug', $slug)
            ->publishedAndVisible()
            ->firstOrFail();

        $sessionKey = 'blog_seen.' . $article->id;
        if (! $request->session()->has($sessionKey)) {
            $cacheKey = 'blog_view.' . $article->id . '.' . $request->ip();
            if (! Cache::has($cacheKey)) {
                $article->increment('vues');
                Cache::put($cacheKey, true, 3600);
            }
            $request->session()->put($sessionKey, true);
        }

        $relatedQuery = BlogArticle::publishedAndVisible()
            ->where('id', '!=', $article->id);
        if ($article->categorie) {
            $relatedQuery->where('categorie', $article->categorie);
        }
        $related = $relatedQuery->recent()->limit(3)->get();

        $previous = BlogArticle::publishedAndVisible()
            ->where('date_publication', '<', $article->date_publication)
            ->orderBy('date_publication', 'desc')
            ->first();

        $next = BlogArticle::publishedAndVisible()
            ->where('date_publication', '>', $article->date_publication)
            ->orderBy('date_publication', 'asc')
            ->first();

        $resource = (new BlogArticleResource($article))->toArray($request);

        return Inertia::render('Blog/Show', [
            'article' => array_merge($resource, [
                'contenu' => $article->contenu,
                'meta_description' => $article->meta_description,
            ]),
            'related' => BlogArticleResource::collection($related),
            'previous' => $previous ? [
                'slug' => $previous->slug,
                'titre' => $previous->titre,
            ] : null,
            'next' => $next ? [
                'slug' => $next->slug,
                'titre' => $next->titre,
            ] : null,
            'seo' => [
                'title' => $article->getSeoTitle(),
                'description' => $article->getSeoDescription(),
                'image' => $article->image,
                'published_time' => optional($article->date_publication)->toIso8601String(),
                'modified_time' => $article->updated_at?->toIso8601String(),
                'canonical' => $resource['url'],
            ],
            'sidebar' => $this->sidebar(),
        ]);
    }

    /**
     * S7.3 — Articles d'une catégorie. La taxonomie blog reste une simple
     * string en v1 (cf. ADR différé en S9.4 : tables categories/tags
     * partagées avec les épisodes).
     */
    public function category(Request $request, $locale, $category): Response
    {
        $articles = BlogArticle::publishedAndVisible()
            ->where('categorie', $category)
            ->recent()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Blog/Category', [
            'articles' => BlogArticleResource::collection($articles),
            'category' => [
                'slug' => $category,
                'name' => $this->categoryLabel($category),
                'description' => $this->categoryDescription($category),
            ],
            'sidebar' => $this->sidebar(),
        ]);
    }

    /**
     * S7.3 — Articles taggés. On filtre via JSON_CONTAINS (MySQL) ; en
     * SQLite (tests) Eloquent retombe sur LIKE, suffisant ici.
     */
    public function tag(Request $request, $locale, $tag): Response
    {
        $articles = BlogArticle::publishedAndVisible()
            ->whereJsonContains('mots_cles', $tag)
            ->recent()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Blog/Tag', [
            'articles' => BlogArticleResource::collection($articles),
            'tag' => [
                'slug' => $tag,
                'name' => $tag,
            ],
            'sidebar' => $this->sidebar(),
        ]);
    }

    /**
     * Sidebar partagée entre Index, Show, Category, Tag.
     * Cachée 10 min — bornes raisonnables avant qu'un nouvel article apparaisse.
     */
    private function sidebar(): array
    {
        return Cache::remember('blog.sidebar', 600, function () {
            $recent = BlogArticle::publishedAndVisible()
                ->recent()
                ->limit(5)
                ->get(['id', 'slug', 'titre', 'date_publication']);

            $popular = BlogArticle::publishedAndVisible()
                ->orderBy('vues', 'desc')
                ->limit(5)
                ->get(['id', 'slug', 'titre', 'vues']);

            return [
                'recent' => $recent->map(fn ($a) => [
                    'slug' => $a->slug,
                    'titre' => $a->titre,
                    'date_publication' => optional($a->date_publication)->toIso8601String(),
                ])->all(),
                'popular' => $popular->map(fn ($a) => [
                    'slug' => $a->slug,
                    'titre' => $a->titre,
                    'vues' => (int) ($a->vues ?? 0),
                ])->all(),
                'categories' => $this->availableCategories(),
                'archives' => $this->availableArchives(),
            ];
        });
    }

    private function availableCategories(): array
    {
        return Cache::remember('blog.categories', 3600, function () {
            return BlogArticle::publishedAndVisible()
                ->whereNotNull('categorie')
                ->selectRaw('categorie, COUNT(*) as count')
                ->groupBy('categorie')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($r) => [
                    'slug' => $r->categorie,
                    'name' => $this->categoryLabel($r->categorie),
                    'count' => (int) $r->count,
                ])
                ->all();
        });
    }

    /**
     * Liste de "YYYY-MM" pour le widget archives. Groupé côté PHP pour rester
     * portable MySQL/SQLite.
     */
    private function availableArchives(): array
    {
        return Cache::remember('blog.archives', 3600, function () {
            return BlogArticle::publishedAndVisible()
                ->whereNotNull('date_publication')
                ->orderBy('date_publication', 'desc')
                ->pluck('date_publication')
                ->map(fn ($d) => optional($d)->format('Y-m'))
                ->filter()
                ->countBy()
                ->map(fn ($count, $ym) => [
                    'value' => $ym,
                    'year' => (int) Str::before($ym, '-'),
                    'month' => (int) Str::after($ym, '-'),
                    'count' => $count,
                ])
                ->values()
                ->all();
        });
    }

    private function categoryLabel(string $category): string
    {
        $labels = [
            'coulisses' => __('Coulisses'),
            'conseils' => __('Conseils'),
            'actualites' => __('Actualités'),
            'interviews' => __('Interviews'),
            'tendances' => __('Tendances'),
            'lifestyle' => __('Lifestyle'),
        ];

        return $labels[$category] ?? Str::ucfirst($category);
    }

    private function categoryDescription(string $category): string
    {
        $descriptions = [
            'coulisses' => __('Découvrez les secrets de fabrication de nos épisodes et l\'envers du décor.'),
            'conseils' => __('Nos meilleurs conseils et astuces pour améliorer votre quotidien.'),
            'actualites' => __('Toute l\'actualité de L\'Astuce et les dernières nouvelles.'),
            'interviews' => __('Rencontres et interviews avec nos invités et partenaires.'),
            'tendances' => __('Les dernières tendances et nouveautés dans notre domaine.'),
            'lifestyle' => __('Inspiration lifestyle et bien-être pour une vie plus belle.'),
        ];

        return $descriptions[$category] ?? '';
    }
}
