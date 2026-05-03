<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAstuceRequest;
use App\Http\Resources\AstuceCardResource;
use App\Jobs\SendAstuceConfirmationEmail;
use App\Models\AstucesSoumise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Epic 4 — Soumission communautaire d'astuces.
 *
 * Toutes les vues sont rendues par Inertia. Les anciennes vues Blade
 * (`pages.astuces.*`) restent dans resources/views mais ne sont plus
 * référencées : on pourra les supprimer en parallèle de l'Epic 8 (admin).
 */
class AstuceController extends Controller
{
    public const CATEGORIES = [
        'cuisine' => 'Cuisine',
        'menage' => 'Ménage',
        'bricolage' => 'Bricolage',
        'beaute' => 'Beauté',
        'organisation' => 'Organisation',
        'jardinage' => 'Jardinage',
        'economie' => 'Économies',
        'technologie' => 'Technologie',
        'sante' => 'Santé',
        'autre' => 'Autre',
    ];

    public const DIFFICULTES = [
        'facile' => 'Facile',
        'moyen' => 'Moyen',
        'difficile' => 'Difficile',
    ];

    /**
     * Story S4.3 — Liste publique paginée.
     */
    public function index(Request $request): Response
    {
        $category = trim($request->string('category')->toString());
        $difficulte = trim($request->string('difficulte')->toString());
        $search = trim($request->string('search')->toString());

        $query = AstucesSoumise::approuve();

        if ($category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $query->where('categorie', $category);
        }

        if ($difficulte !== '' && array_key_exists($difficulte, self::DIFFICULTES)) {
            $query->where('difficulte', $difficulte);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('titre_astuce', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginator = $query->recent()->paginate(20)->withQueryString();

        return Inertia::render('Astuces/Index', [
            'astuces' => AstuceCardResource::collection($paginator),
            'filters' => [
                'category' => $category,
                'difficulte' => $difficulte,
                'search' => $search,
            ],
            'options' => [
                'categories' => collect(self::CATEGORIES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'difficultes' => collect(self::DIFFICULTES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ]);
    }

    /**
     * Story S4.1 — Affichage du formulaire multi-étapes.
     */
    public function create(): Response
    {
        return Inertia::render('Astuces/Create', [
            'options' => [
                'categories' => collect(self::CATEGORIES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'difficultes' => collect(self::DIFFICULTES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ]);
    }

    /**
     * Story S4.1 — Création + email de confirmation en queue.
     */
    public function store(StoreAstuceRequest $request, $locale): RedirectResponse
    {
        $payload = $request->validated();

        $astuce = AstucesSoumise::create([
            'nom' => $payload['nom'],
            'email' => $payload['email'],
            'titre_astuce' => $payload['titre_astuce'],
            'categorie' => $payload['categorie'],
            'difficulte' => $payload['difficulte'],
            'temps_estime' => $payload['temps_estime'] ?? null,
            'description' => $payload['description'],
            'materiel_requis' => $payload['materiel_requis'] ?? null,
            'etapes' => array_values($payload['etapes']),
            'conseils' => $payload['conseils'] ?? null,
            'status' => AstucesSoumise::STATUS_EN_ATTENTE,
            'ip_soumetteur' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'source' => 'public_form',
        ]);

        if ($request->hasFile('fichier_joint')) {
            $astuce->fichier_joint = $request->file('fichier_joint')->store('astuces/files', 'public');
        }

        if ($request->hasFile('images')) {
            $stored = [];
            foreach ((array) $request->file('images') as $image) {
                $stored[] = $image->store('astuces/images', 'public');
            }
            $astuce->images = $stored;
        }

        $astuce->save();

        SendAstuceConfirmationEmail::dispatch($astuce->id);

        return redirect()
            ->route('astuces.success', ['locale' => $locale, 'id' => $astuce->id])
            ->with('success', 'Votre astuce a bien été soumise.');
    }

    public function success(Request $request, $locale): Response
    {
        $id = (int) $request->query('id');
        $exists = $id > 0 && AstucesSoumise::whereKey($id)->exists();

        return Inertia::render('Astuces/Success', [
            'astuceId' => $exists ? $id : null,
        ]);
    }

    /**
     * Story S4.2 — Page de suivi par ID. Pas d'auth : l'ID fait office de
     * jeton suffisant (pas de donnée sensible en plus de ce que l'utilisateur
     * a soumis lui-même).
     */
    public function track(Request $request, $locale, $id): Response
    {
        $astuce = AstucesSoumise::findOrFail($id);

        return Inertia::render('Astuces/Track', [
            'astuce' => [
                'id' => $astuce->id,
                'titre' => $astuce->titre_astuce,
                'status' => $astuce->status,
                'status_label' => AstucesSoumise::getStatuses()[$astuce->status] ?? $astuce->status,
                'commentaire_admin' => $astuce->commentaire_admin,
                'created_at' => optional($astuce->created_at)->toIso8601String(),
                'updated_at' => optional($astuce->updated_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Story S4.4 — Affichage public uniquement si statut approuvé.
     */
    public function show(Request $request, $locale, $id): Response
    {
        $astuce = AstucesSoumise::approuve()->findOrFail($id);

        $similaires = AstucesSoumise::approuve()
            ->where('id', '!=', $astuce->id)
            ->when($astuce->categorie, fn ($q) => $q->where('categorie', $astuce->categorie))
            ->recent()
            ->limit(3)
            ->get()
            ->map(fn ($a) => $this->cardPayload($a, $request));

        return Inertia::render('Astuces/Show', [
            'astuce' => array_merge($this->cardPayload($astuce, $request), [
                'description' => $astuce->description,
                'materiel_requis' => $astuce->materiel_requis,
                'etapes' => is_array($astuce->etapes) ? array_values($astuce->etapes) : [],
                'conseils' => $astuce->conseils,
                'images' => collect($astuce->images ?? [])
                    ->map(fn ($path) => Storage::disk('public')->url($path))
                    ->values()
                    ->all(),
            ]),
            'similaires' => $similaires,
        ]);
    }

    private function cardPayload(AstucesSoumise $astuce, Request $request): array
    {
        $locale = $request->route('locale') ?? app()->getLocale();
        return [
            'id' => $astuce->id,
            'titre' => $astuce->titre_astuce,
            'categorie' => $astuce->categorie,
            'categorie_label' => self::CATEGORIES[$astuce->categorie] ?? $astuce->categorie,
            'difficulte' => $astuce->difficulte,
            'difficulte_label' => self::DIFFICULTES[$astuce->difficulte] ?? $astuce->difficulte,
            'temps_estime' => $astuce->temps_estime,
            'extrait' => mb_substr(strip_tags((string) $astuce->description), 0, 160),
            'auteur' => $astuce->nom,
            'date' => optional($astuce->created_at)->toIso8601String(),
            'url' => "/{$locale}/astuces/{$astuce->id}",
        ];
    }
}
