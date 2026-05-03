<?php

namespace App\Http\Requests;

use App\Models\AstucesSoumise;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Story S4.1 — Validation côté serveur du formulaire multi-étapes.
 *
 * On miroir la validation Vee-Validate côté client : c'est le même schéma,
 * appliqué deux fois (browser pour UX, serveur pour la sécurité).
 *
 * Honeypot : le champ `website` n'est pas affiché à l'utilisateur. Si un bot
 * le remplit, on lève une ValidationException silencieuse.
 */
class StoreAstuceRequest extends FormRequest
{
    public const CATEGORIES = [
        'cuisine', 'menage', 'bricolage', 'beaute', 'organisation',
        'jardinage', 'economie', 'technologie', 'sante', 'autre',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'titre_astuce' => ['required', 'string', 'min:5', 'max:200'],
            'categorie' => ['required', Rule::in(self::CATEGORIES)],
            'difficulte' => ['required', Rule::in(['facile', 'moyen', 'difficile'])],
            'temps_estime' => ['nullable', 'integer', 'min:1', 'max:600'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'materiel_requis' => ['nullable', 'string', 'max:1000'],
            'etapes' => ['required', 'array', 'min:1', 'max:20'],
            'etapes.*' => ['required', 'string', 'min:3', 'max:500'],
            'conseils' => ['nullable', 'string', 'max:1000'],
            'fichier_joint' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cgv' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.max' => 'Erreur de soumission.',
            'cgv.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Limite anti-spam : 3 soumissions / heure / IP. On compte avant
            // la création — si dépassé, on se calque sur le comportement du
            // middleware security:upload (réponse 429).
            if (! $this->ip()) {
                return;
            }
            $count = AstucesSoumise::where('ip_soumetteur', $this->ip())
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($count >= 3) {
                $v->errors()->add('throttle', 'Trop de soumissions depuis votre adresse — réessayez plus tard.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        // Honeypot : si seul `website` a échoué, on renvoie une réponse
        // "succès" silencieux pour ne pas signaler au bot la détection.
        $errors = $validator->errors();
        if ($errors->count() === 1 && $errors->has('website')) {
            throw ValidationException::withMessages([
                'website' => ['__honeypot__'],
            ])->status(422);
        }

        parent::failedValidation($validator);
    }
}
