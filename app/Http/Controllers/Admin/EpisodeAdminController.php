<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEpisodeRequest;
use App\Http\Resources\EpisodeResource;
use App\Models\AdminLog;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Story S8.3 — CRUD admin Épisodes (Inertia).
 *
 * Le legacy renvoyait du JSON pour une SPA jQuery ; on bascule sur des
 * réponses Inertia (Index/Create/Edit/Show) + redirect+flash pour les
 * mutations. Toute action significative est loggée via AdminLog.
 */
class EpisodeAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->string('sort_by', 'created_at')->toString();
        $direction = $request->string('sort_order', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'titre', 'date_publication', 'vues', 'statut'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = Episode::query()->with(['categoryRelation', 'media']);

        if ($request->filled('status')) {
            $query->where('statut', $request->string('status')->toString());
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('invite_nom', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return Inertia::render('Admin/Episodes/Index', [
            'episodes' => $paginator->through(fn (Episode $e) => $this->listRow($e)),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'type' => $request->string('type')->toString(),
                'sort_by' => $sort,
                'sort_order' => $direction,
            ],
            'options' => [
                'statuses' => collect(Episode::STATUTS)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'types' => collect(Episode::TYPES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
            'stats' => [
                'total' => Episode::count(),
                'published' => Episode::where('statut', 'published')->count(),
                'draft' => Episode::where('statut', 'draft')->count(),
                'scheduled' => Episode::where('statut', 'scheduled')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Episodes/Form', [
            'episode' => null,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreEpisodeRequest $request)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags'], $data['thumbnail'], $data['remove_thumbnail']);

        if (($data['statut'] ?? null) === Episode::STATUT_PUBLIE && empty($data['date_publication'])) {
            $data['date_publication'] = now();
        }

        $episode = Episode::create($data);
        $episode->tagsRelation()->sync($tags);

        if ($request->hasFile('thumbnail')) {
            $episode->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        $this->logAction('episode.create', $episode, "a créé l'épisode « {$episode->titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.episodes.index')
            ->with('success', 'Épisode créé avec succès.');
    }

    public function show(Episode $episode): Response
    {
        $episode->load(['categoryRelation', 'tagsRelation', 'media']);

        return Inertia::render('Admin/Episodes/Show', [
            'episode' => $this->detailPayload($episode),
        ]);
    }

    public function edit(Episode $episode): Response
    {
        $episode->load(['categoryRelation', 'tagsRelation', 'media']);

        return Inertia::render('Admin/Episodes/Form', [
            'episode' => $this->detailPayload($episode),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(StoreEpisodeRequest $request, Episode $episode)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        $removeThumbnail = (bool) ($data['remove_thumbnail'] ?? false);
        unset($data['tags'], $data['thumbnail'], $data['remove_thumbnail']);

        if (($data['statut'] ?? null) === Episode::STATUT_PUBLIE && empty($data['date_publication']) && empty($episode->date_publication)) {
            $data['date_publication'] = now();
        }

        $episode->update($data);
        $episode->tagsRelation()->sync($tags);

        if ($removeThumbnail) {
            $episode->clearMediaCollection('thumbnail');
        }
        if ($request->hasFile('thumbnail')) {
            $episode->clearMediaCollection('thumbnail');
            $episode->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        $this->logAction('episode.update', $episode, "a modifié l'épisode « {$episode->titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.episodes.index')
            ->with('success', 'Épisode mis à jour avec succès.');
    }

    public function destroy(Episode $episode)
    {
        $titre = $episode->titre;
        $episode->delete();

        $this->logAction('episode.delete', null, "a supprimé l'épisode « {$titre} »");
        $this->forgetCaches();

        return redirect()
            ->route('admin.episodes.index')
            ->with('success', 'Épisode supprimé.');
    }

    public function publish(Episode $episode)
    {
        $episode->update([
            'statut' => Episode::STATUT_PUBLIE,
            'date_publication' => $episode->date_publication ?? now(),
        ]);

        $this->logAction('episode.publish', $episode, "a publié l'épisode « {$episode->titre} »");
        $this->forgetCaches();

        return back()->with('success', 'Épisode publié.');
    }

    public function unpublish(Episode $episode)
    {
        $episode->update(['statut' => Episode::STATUT_BROUILLON]);

        $this->logAction('episode.unpublish', $episode, "a dépublié l'épisode « {$episode->titre} »");
        $this->forgetCaches();

        return back()->with('success', 'Épisode dépublié.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:publish,draft,archive,delete'],
            'episodes' => ['required', 'array', 'min:1'],
            'episodes.*' => ['integer', 'exists:episodes,id'],
        ]);

        $episodes = Episode::whereIn('id', $validated['episodes']);
        $count = $episodes->count();

        match ($validated['action']) {
            'publish' => $episodes->update(['statut' => Episode::STATUT_PUBLIE]),
            'draft' => $episodes->update(['statut' => Episode::STATUT_BROUILLON]),
            'archive' => $episodes->update(['statut' => Episode::STATUT_ARCHIVE]),
            'delete' => $episodes->delete(),
        };

        $this->logAction(
            "episode.bulk.{$validated['action']}",
            null,
            "a appliqué l'action « {$validated['action']} » à {$count} épisode(s)",
        );
        $this->forgetCaches();

        return back()->with('success', "Action appliquée à {$count} épisode(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Episode::query();
        if ($request->filled('status')) {
            $query->where('statut', $request->string('status')->toString());
        }
        $episodes = $query->with('categoryRelation')->get();

        $filename = 'episodes_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($episodes) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'ID', 'Titre', 'Slug', 'Type', 'Statut', 'Catégorie',
                'Date publication', 'Vues', 'YouTube URL', 'Facebook URL',
            ]);
            foreach ($episodes as $e) {
                fputcsv($out, [
                    $e->id,
                    $e->titre,
                    $e->slug,
                    $e->type,
                    $e->statut,
                    optional($e->categoryRelation)->nom,
                    optional($e->date_publication)->format('Y-m-d H:i'),
                    $e->vues ?? 0,
                    $e->youtube_url,
                    $e->facebook_url,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function listRow(Episode $episode): array
    {
        return [
            'id' => $episode->id,
            'titre' => $episode->titre,
            'slug' => $episode->slug,
            'type' => $episode->type,
            'type_label' => Episode::TYPES[$episode->type] ?? $episode->type,
            'statut' => $episode->statut,
            'statut_label' => Episode::STATUTS[$episode->statut] ?? $episode->statut,
            'categorie' => optional($episode->categoryRelation)->nom,
            'date_publication' => optional($episode->date_publication)->toIso8601String(),
            'vues' => (int) ($episode->vues ?? 0),
            'thumbnail_url' => $episode->getMedia('thumbnail')->first()?->getUrl('thumb')
                ?? $episode->thumbnail_url,
            'edit_url' => route('admin.episodes.edit', $episode),
            'show_url' => route('admin.episodes.show', $episode),
        ];
    }

    private function detailPayload(Episode $episode): array
    {
        $thumbnailMedia = $episode->getMedia('thumbnail')->first();

        return [
            'id' => $episode->id,
            'titre' => $episode->titre,
            'slug' => $episode->slug,
            'description' => $episode->description,
            'contenu' => $episode->contenu,
            'transcript' => $episode->transcript,
            'invite_nom' => $episode->invite_nom,
            'invite_bio' => $episode->invite_bio,
            'youtube_url' => $episode->youtube_url,
            'facebook_url' => $episode->facebook_url,
            'audio_url' => $episode->audio_url,
            'duree' => $episode->duree,
            'type' => $episode->type,
            'statut' => $episode->statut,
            'date_publication' => optional($episode->date_publication)->format('Y-m-d\TH:i'),
            'category_id' => $episode->category_id,
            'tags' => $episode->tagsRelation->pluck('id')->values()->all(),
            'tags_detail' => $episode->tagsRelation->map(fn ($t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'nom' => $t->nom,
            ])->values()->all(),
            'thumbnail_url' => $thumbnailMedia?->getUrl('card') ?? $episode->thumbnail_url,
            'thumbnail_thumb_url' => $thumbnailMedia?->getUrl('thumb') ?? $episode->thumbnail_url,
            'has_uploaded_thumbnail' => $thumbnailMedia !== null,
            'vues' => (int) ($episode->vues ?? 0),
        ];
    }

    private function formOptions(): array
    {
        return [
            'types' => collect(Episode::TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'statuses' => collect(Episode::STATUTS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'categories' => Category::orderBy('nom')->get()->map(fn (Category $c) => [
                'value' => $c->id,
                'label' => $c->nom,
            ])->values(),
            'tags' => Tag::orderBy('nom')->get()->map(fn (Tag $t) => [
                'value' => $t->id,
                'label' => $t->nom,
            ])->values(),
        ];
    }

    private function logAction(string $action, ?Episode $episode, string $description): void
    {
        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $episode ? Episode::class : null,
            'model_id' => $episode?->id,
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
        Cache::forget('admin.pending_counts');
        Cache::forget('episodes.available_months');
    }
}
