<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Story S8.7 — CRUD admin Blog (Inertia).
 */
class BlogAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort_by', 'created_at')->toString();
        $direction = $request->string('sort_order', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'titre', 'date_publication', 'vues'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = BlogArticle::query()->with('media');

        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'published' => $query->published()->where(function ($q) {
                    $q->whereNull('date_publication')->orWhere('date_publication', '<=', now());
                }),
                'draft'     => $query->draft(),
                'scheduled' => $query->scheduled(),
                default     => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('extrait', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return Inertia::render('Admin/Blog/Index', [
            'articles' => $paginator->through(fn (BlogArticle $a) => $this->listRow($a)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'sort_by' => $sort,
                'sort_order' => $direction,
            ],
            'stats' => [
                'total' => BlogArticle::count(),
                'published' => BlogArticle::published()->where(function ($q) {
                    $q->whereNull('date_publication')->orWhere('date_publication', '<=', now());
                })->count(),
                'draft' => BlogArticle::draft()->count(),
                'scheduled' => BlogArticle::scheduled()->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Form', [
            'article' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateArticle($request);
        $data['slug'] = $this->uniqueSlug($data['titre'], $data['slug'] ?? null);

        $article = BlogArticle::create($data);

        if ($request->hasFile('featured_image')) {
            $article->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured');
        }

        $this->logAction('blog.create', $article, "a créé l'article « {$article->titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function show(BlogArticle $blog): Response
    {
        $blog->load('media');

        return Inertia::render('Admin/Blog/Show', [
            'article' => $this->detailPayload($blog),
        ]);
    }

    public function edit(BlogArticle $blog): Response
    {
        $blog->load('media');

        return Inertia::render('Admin/Blog/Form', [
            'article' => $this->detailPayload($blog),
        ]);
    }

    public function update(Request $request, BlogArticle $blog)
    {
        $data = $this->validateArticle($request, $blog->id);
        $removeFeatured = (bool) ($data['remove_featured_image'] ?? false);
        unset($data['remove_featured_image']);

        if (! empty($data['slug']) && $data['slug'] !== $blog->slug) {
            $data['slug'] = $this->uniqueSlug($data['titre'], $data['slug'], $blog->id);
        }

        $blog->update($data);

        if ($removeFeatured) {
            $blog->clearMediaCollection('featured');
        }
        if ($request->hasFile('featured_image')) {
            $blog->clearMediaCollection('featured');
            $blog->addMediaFromRequest('featured_image')
                ->toMediaCollection('featured');
        }

        $this->logAction('blog.update', $blog, "a modifié l'article « {$blog->titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Article mis à jour avec succès.');
    }

    public function destroy(BlogArticle $blog)
    {
        $titre = $blog->titre;
        $blog->delete();

        $this->logAction('blog.delete', null, "a supprimé l'article « {$titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.blog.index')
            ->with('success', 'Article supprimé.');
    }

    public function publish(BlogArticle $blog)
    {
        $blog->publier($blog->date_publication ?? now());

        $this->logAction('blog.publish', $blog, "a publié l'article « {$blog->titre} »");
        $this->forgetCaches();

        return back()->with('success', 'Article publié.');
    }

    public function unpublish(BlogArticle $blog)
    {
        $blog->depublier();

        $this->logAction('blog.unpublish', $blog, "a dépublié l'article « {$blog->titre} »");
        $this->forgetCaches();

        return back()->with('success', 'Article dépublié.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:publish,draft,delete'],
            'articles' => ['required', 'array', 'min:1'],
            'articles.*' => ['integer', 'exists:blog_articles,id'],
        ]);

        $articles = BlogArticle::whereIn('id', $validated['articles']);
        $count = $articles->count();

        match ($validated['action']) {
            'publish' => $articles->update(['is_published' => true, 'date_publication' => now()]),
            'draft'   => $articles->update(['is_published' => false]),
            'delete'  => $articles->delete(),
        };

        $this->logAction(
            "blog.bulk.{$validated['action']}",
            null,
            "a appliqué l'action « {$validated['action']} » à {$count} article(s)",
        );
        $this->forgetCaches();

        return back()->with('success', "Action appliquée à {$count} article(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = BlogArticle::query();
        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'published' => $query->published(),
                'draft' => $query->draft(),
                'scheduled' => $query->scheduled(),
                default => null,
            };
        }
        $articles = $query->get();
        $filename = 'articles_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($articles) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Titre', 'Slug', 'Statut', 'Catégorie', 'Date publication', 'Vues', 'Mots de lecture']);
            foreach ($articles as $a) {
                fputcsv($out, [
                    $a->id,
                    $a->titre,
                    $a->slug,
                    $a->status,
                    $a->categorie,
                    optional($a->date_publication)->format('Y-m-d H:i'),
                    $a->vues ?? 0,
                    $a->reading_time,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function validateArticle(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string', 'min:10'],
            'extrait' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:255', "unique:blog_articles,slug,{$ignoreId}"],
            'categorie' => ['nullable', 'string', 'max:100'],
            'mots_cles' => ['nullable', 'array'],
            'mots_cles.*' => ['string', 'max:50'],
            'is_published' => ['boolean'],
            'date_publication' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'remove_featured_image' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $titre, ?string $slug, ?int $ignoreId = null): string
    {
        $base = $slug ? Str::slug($slug) : Str::slug($titre);
        $candidate = $base;
        $i = 2;
        while (BlogArticle::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    private function listRow(BlogArticle $article): array
    {
        return [
            'id' => $article->id,
            'titre' => $article->titre,
            'slug' => $article->slug,
            'statut' => $article->status,
            'statut_color' => $article->status_color,
            'categorie' => $article->categorie,
            'date_publication' => optional($article->date_publication)->toIso8601String(),
            'vues' => (int) ($article->vues ?? 0),
            'reading_time' => $article->reading_time,
            'featured_url' => $article->getMedia('featured')->first()?->getUrl('thumb')
                ?? $article->image,
            'edit_url' => route('admin.blog.edit', $article),
            'show_url' => route('admin.blog.show', $article),
        ];
    }

    private function detailPayload(BlogArticle $article): array
    {
        $featuredMedia = $article->getMedia('featured')->first();

        return [
            'id' => $article->id,
            'titre' => $article->titre,
            'slug' => $article->slug,
            'contenu' => $article->contenu,
            'extrait' => $article->extrait,
            'meta_description' => $article->meta_description,
            'categorie' => $article->categorie,
            'mots_cles' => $article->mots_cles ?? [],
            'is_published' => (bool) $article->is_published,
            'date_publication' => optional($article->date_publication)->format('Y-m-d\TH:i'),
            'statut' => $article->status,
            'statut_color' => $article->status_color,
            'vues' => (int) ($article->vues ?? 0),
            'reading_time' => $article->reading_time,
            'featured_url' => $featuredMedia?->getUrl('card') ?? $article->image,
            'featured_thumb_url' => $featuredMedia?->getUrl('thumb') ?? $article->image,
            'has_featured_image' => $featuredMedia !== null,
        ];
    }

    private function logAction(string $action, ?BlogArticle $article, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $article ? BlogArticle::class : null,
            'model_id' => $article?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'description' => (auth()->user()?->name ?? 'Système') . ' ' . $description,
            'severity' => 'info',
        ]);
    }

    private function forgetCaches(): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_dashboard_chart_30d');
        Cache::forget('blog.sidebar_data');
        Cache::forget('blog.categories');
    }
}
