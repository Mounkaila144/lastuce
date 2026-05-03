<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickSubscribeNewsletterRequest;
use App\Http\Requests\StoreNewsletterRequest;
use App\Jobs\SendNewsletterConfirmationEmail;
use App\Models\NewsletterAbonne;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Epic 5 — Newsletter (double opt-in).
 *
 * - S5.1 : POST /newsletter crée un abonné `inactif/non confirmé`,
 *          puis dispatch un job d'email avec le lien `/newsletter/confirm/{token}`.
 * - S5.2 : `/newsletter/unsubscribe/{token}` (1 clic) et
 *          `/newsletter/preferences/{token}` (lecture/écriture).
 * - S5.3 : `/newsletter/quick-subscribe` partage le même flow,
 *          mais redirige back avec un flash (Inertia partial reload côté footer).
 */
class NewsletterController extends Controller
{
    /**
     * Story S5.1 — page d'inscription complète.
     */
    public function create(): Response
    {
        return Inertia::render('Newsletter/Subscribe', [
            'options' => $this->options(),
            'stats' => [
                'subscribers' => NewsletterAbonne::abonnesActifs()->count(),
            ],
        ]);
    }

    /**
     * Story S5.1 — création + dispatch email confirmation.
     */
    public function store(StoreNewsletterRequest $request, $locale): RedirectResponse
    {
        $payload = $request->validated();

        $abonne = NewsletterAbonne::create([
            'email' => $payload['email'],
            'prenom' => $payload['prenom'] ?? null,
            'nom' => $payload['nom'] ?? null,
            'frequence_envoi' => $payload['frequence_envoi'] ?? 'hebdomadaire',
            'interets' => $payload['interets'] ?? null,
            'source_inscription' => $payload['source'] ?? 'site_web',
            'status' => NewsletterAbonne::STATUS_INACTIF,
            'confirme' => false,
            'ip_inscription' => $request->ip(),
        ]);

        SendNewsletterConfirmationEmail::dispatch($abonne->id);

        return redirect()
            ->route('newsletter.success', ['locale' => $locale])
            ->with('success', 'Inscription enregistrée. Vérifiez votre boîte mail pour la confirmer.')
            ->with('email', $abonne->email);
    }

    /**
     * Story S5.1 — page de confirmation envoyée (entre POST et clic email).
     */
    public function success(Request $request): Response
    {
        return Inertia::render('Newsletter/Success', [
            'email' => $request->session()->get('email'),
        ]);
    }

    /**
     * Story S5.1 — confirme via token reçu par email.
     */
    public function confirm($locale, string $token): Response
    {
        $abonne = NewsletterAbonne::findByToken($token);
        if (! $abonne) {
            return Inertia::render('Newsletter/Error', [
                'reason' => 'invalid_token',
            ]);
        }

        if (! $abonne->confirme) {
            $abonne->confirmer();
        }

        return Inertia::render('Newsletter/Confirmed', [
            'email' => $abonne->email,
            'unsubscribeUrl' => "/{$locale}/newsletter/unsubscribe/{$abonne->token_desabonnement}",
            'preferencesUrl' => "/{$locale}/newsletter/preferences/{$abonne->token_desabonnement}",
        ]);
    }

    /**
     * Story S5.2 — désabonnement.
     * GET : page de confirmation (avec raison optionnelle).
     * POST : applique et affiche la page finale.
     */
    public function unsubscribe(Request $request, $locale, string $token): Response
    {
        $abonne = NewsletterAbonne::findByToken($token);
        if (! $abonne) {
            return Inertia::render('Newsletter/Error', [
                'reason' => 'invalid_token',
            ]);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'raison' => ['nullable', 'string', 'max:60'],
                'commentaire' => ['nullable', 'string', 'max:500'],
            ]);

            if (! $abonne->is_desabonne) {
                $abonne->desabonner($data['raison'] ?? null, $data['commentaire'] ?? null);
            }

            return Inertia::render('Newsletter/Unsubscribed', [
                'email' => $abonne->email,
            ]);
        }

        return Inertia::render('Newsletter/Unsubscribe', [
            'email' => $abonne->email,
            'token' => $abonne->token_desabonnement,
            'alreadyDone' => $abonne->is_desabonne,
        ]);
    }

    /**
     * Story S5.2 — préférences (lecture).
     */
    public function preferences($locale, string $token): Response
    {
        $abonne = NewsletterAbonne::findByToken($token);
        if (! $abonne) {
            return Inertia::render('Newsletter/Error', [
                'reason' => 'invalid_token',
            ]);
        }

        return Inertia::render('Newsletter/Preferences', [
            'abonne' => [
                'email' => $abonne->email,
                'prenom' => $abonne->prenom,
                'nom' => $abonne->nom,
                'frequence_envoi' => $abonne->frequence_envoi,
                'interets' => $abonne->interets ?? [],
                'token' => $abonne->token_desabonnement,
                'status' => $abonne->status,
            ],
            'options' => $this->options(),
        ]);
    }

    /**
     * Story S5.2 — préférences (écriture). `regenerate_token=1` rotée le jeton.
     */
    public function updatePreferences(Request $request, $locale, string $token): RedirectResponse
    {
        $abonne = NewsletterAbonne::findByToken($token);
        if (! $abonne) {
            return back()->with('error', 'Lien invalide.');
        }

        $data = $request->validate([
            'prenom' => ['nullable', 'string', 'max:100'],
            'nom' => ['nullable', 'string', 'max:100'],
            'frequence_envoi' => ['required', \Illuminate\Validation\Rule::in(NewsletterAbonne::FREQUENCES)],
            'interets' => ['nullable', 'array', 'max:6'],
            'interets.*' => [\Illuminate\Validation\Rule::in(NewsletterAbonne::INTERETS)],
            'regenerate_token' => ['nullable', 'boolean'],
        ]);

        $abonne->fill([
            'prenom' => $data['prenom'] ?? null,
            'nom' => $data['nom'] ?? null,
            'frequence_envoi' => $data['frequence_envoi'],
            'interets' => $data['interets'] ?? null,
        ])->save();

        if (! empty($data['regenerate_token'])) {
            $abonne->genererNouveauToken();
            return redirect()
                ->route('newsletter.preferences', ['locale' => $locale, 'token' => $abonne->token_desabonnement])
                ->with('success', 'Préférences mises à jour et nouveau lien généré.');
        }

        return back()->with('success', 'Préférences mises à jour.');
    }

    /**
     * Story S5.3 — inscription rapide (footer). Même back-end, simplement
     * tagué `source=footer` ; on redirige back pour préserver le scroll et
     * exposer le succès via flash + Inertia partial reload.
     */
    public function quickSubscribe(QuickSubscribeNewsletterRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $abonne = NewsletterAbonne::create([
            'email' => $payload['email'],
            'source_inscription' => $payload['source'] ?? 'footer',
            'status' => NewsletterAbonne::STATUS_INACTIF,
            'confirme' => false,
            'ip_inscription' => $request->ip(),
        ]);

        SendNewsletterConfirmationEmail::dispatch($abonne->id);

        return back()->with('success', 'Vérifiez votre boîte mail pour confirmer votre inscription.');
    }

    /**
     * Options affichées dans les pages Subscribe/Preferences (frequences +
     * intérêts), traduites en mémoire pour rester léger côté Vue.
     */
    private function options(): array
    {
        return [
            'frequences' => [
                ['value' => 'hebdomadaire', 'label' => 'Une fois par semaine'],
                ['value' => 'bihebdomadaire', 'label' => 'Toutes les deux semaines'],
                ['value' => 'mensuel', 'label' => 'Une fois par mois'],
            ],
            'interets' => [
                ['value' => 'cuisine', 'label' => 'Cuisine'],
                ['value' => 'menage', 'label' => 'Ménage'],
                ['value' => 'organisation', 'label' => 'Organisation'],
                ['value' => 'beaute', 'label' => 'Beauté'],
                ['value' => 'technologie', 'label' => 'Technologie'],
                ['value' => 'lifestyle', 'label' => 'Lifestyle'],
            ],
        ];
    }
}
