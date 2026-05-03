<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartenariatRequest;
use App\Jobs\SendPartenariatConfirmationEmail;
use App\Models\AdminNotification;
use App\Models\Partenariat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Epic 6 — Partenariats.
 *
 * - S6.1 : `/partenariats` (info statique).
 * - S6.2 : `/partenariats/create` (formulaire) + POST + page success.
 *          Notification AdminNotification créée à chaque soumission ;
 *          email aux admins viendra en v1.1 via {@see AdminNotification}.
 */
class PartenaritController extends Controller
{
    /**
     * Story S6.1 — page d'information.
     */
    public function info($locale): Response
    {
        return Inertia::render('Partenariats/Index', [
            'offre' => $this->offreData(),
            'audience' => $this->audienceData(),
            'examples' => $this->examplesData(),
            'ctaUrl' => "/{$locale}/partenariats/create",
        ]);
    }

    /**
     * Story S6.2 — formulaire.
     */
    public function create(): Response
    {
        return Inertia::render('Partenariats/Create', [
            'options' => [
                'types' => collect(Partenariat::TYPES)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'budgets' => collect(Partenariat::BUDGETS)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ]);
    }

    /**
     * Story S6.2 — création + notification admin + email accusé réception.
     */
    public function store(StorePartenariatRequest $request, $locale): RedirectResponse
    {
        $payload = $request->validated();

        $partenariat = Partenariat::create([
            'nom_entreprise' => $payload['nom_entreprise'],
            'contact' => $payload['contact'],
            'email' => $payload['email'],
            'telephone' => $payload['telephone'] ?? null,
            'site_web' => $payload['site_web'] ?? null,
            'type_partenariat' => $payload['type_partenariat'],
            'budget_envisage' => $payload['budget_envisage'],
            'message' => $payload['message'],
            'status' => Partenariat::STATUS_NOUVEAU,
            'ip_demandeur' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        AdminNotification::createForAllAdmins([
            'type' => 'new_partenariat',
            'title' => "Nouvelle demande de partenariat — {$partenariat->nom_entreprise}",
            'message' => "{$partenariat->contact} ({$partenariat->email}) — {$partenariat->type_label}",
            'data' => ['partenariat_id' => $partenariat->id],
            'action_url' => route('admin.partenariats.show', $partenariat->id),
            'action_text' => 'Voir la demande',
            'priority' => 'normal',
            'is_read' => false,
        ]);

        SendPartenariatConfirmationEmail::dispatch($partenariat->id);

        return redirect()
            ->route('partenariats.success', ['locale' => $locale, 'id' => $partenariat->id])
            ->with('success', 'Votre demande a bien été envoyée. Nous reviendrons vers vous sous 5 jours ouvrés.');
    }

    /**
     * Story S6.2 — page de confirmation.
     */
    public function success(Request $request, $locale): Response
    {
        $id = (int) $request->query('id');
        $exists = $id > 0 && Partenariat::whereKey($id)->exists();

        return Inertia::render('Partenariats/Success', [
            'partenariatId' => $exists ? $id : null,
        ]);
    }

    /**
     * Suivi simple par ID + email — non documenté dans la story mais conservé
     * pour compatibilité avec la route `/partenariats/track/{id}`.
     */
    public function track(Request $request, $locale, $id): Response
    {
        $partenariat = Partenariat::findOrFail($id);

        return Inertia::render('Partenariats/Track', [
            'partenariat' => [
                'id' => $partenariat->id,
                'nom_entreprise' => $partenariat->nom_entreprise,
                'status' => $partenariat->status,
                'status_label' => $partenariat->status_label,
                'created_at' => optional($partenariat->created_at)->toIso8601String(),
                'updated_at' => optional($partenariat->updated_at)->toIso8601String(),
            ],
        ]);
    }

    private function offreData(): array
    {
        return [
            [
                'key' => 'sponsoring',
                'title' => 'Sponsoring d\'épisode',
                'description' => 'Votre marque mise en avant dans un épisode dédié, avec un placement naturel.',
                'bullets' => [
                    'Mention de votre marque en début et fin d\'épisode',
                    'Intégration éditoriale de vos produits',
                    'Article dédié sur le blog',
                    'Partage sur les réseaux sociaux',
                ],
            ],
            [
                'key' => 'collaboration',
                'title' => 'Collaboration de contenu',
                'description' => 'Co-création de contenu adapté à votre secteur.',
                'bullets' => [
                    'Contenu original et authentique',
                    'Audience qualifiée et engagée',
                    'Réutilisable sur vos propres canaux',
                ],
            ],
            [
                'key' => 'affiliation',
                'title' => 'Programme d\'affiliation',
                'description' => 'Recommandations sincères avec suivi des conversions.',
                'bullets' => [
                    'Recommandations crédibles',
                    'Suivi précis des conversions',
                    'Rémunération à la performance',
                ],
            ],
        ];
    }

    private function audienceData(): array
    {
        return [
            ['label' => 'Vues mensuelles cumulées', 'value' => '250 K+'],
            ['label' => 'Engagement moyen', 'value' => '8,5 %'],
            ['label' => 'Croissance mensuelle', 'value' => '+15 %'],
            ['label' => 'Audience principale', 'value' => '25–44 ans'],
        ];
    }

    private function examplesData(): array
    {
        return [
            ['name' => 'Marque A', 'sector' => 'Bien-être', 'result' => 'Sponsoring d\'une saison de 6 épisodes.'],
            ['name' => 'Marque B', 'sector' => 'Cuisine', 'result' => 'Série co-créée — 120 K vues cumulées.'],
            ['name' => 'Marque C', 'sector' => 'Maison', 'result' => 'Programme d\'affiliation — 8 % de taux de conversion.'],
        ];
    }
}
