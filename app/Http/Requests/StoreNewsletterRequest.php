<?php

namespace App\Http\Requests;

use App\Models\NewsletterAbonne;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Story S5.1 — validation de l'inscription newsletter (full form ou rapide).
 *
 * Le honeypot `website` doit toujours être vide. La quick-subscribe ne
 * remplit que `email`/`source` ; les autres champs restent optionnels.
 */
class StoreNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('newsletter_abonnes', 'email'),
            ],
            'prenom' => ['nullable', 'string', 'max:100'],
            'nom' => ['nullable', 'string', 'max:100'],
            'frequence_envoi' => ['nullable', Rule::in(NewsletterAbonne::FREQUENCES)],
            'interets' => ['nullable', 'array', 'max:6'],
            'interets.*' => [Rule::in(NewsletterAbonne::INTERETS)],
            'source' => ['nullable', 'string', 'max:80'],
            'cgv' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse est déjà inscrite à notre newsletter.',
            'cgv.accepted' => 'Vous devez accepter notre politique de confidentialité.',
            'website.max' => 'Erreur de soumission.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Anti-spam : 5 inscriptions max / IP / heure (stories S5.x +
            // protection brute-force). Voir SecurityMiddleware pour le
            // rate-limit HTTP, ici on contrôle aussi la persistance.
            $ip = $this->ip();
            if (! $ip) {
                return;
            }
            $recent = NewsletterAbonne::where('ip_inscription', $ip)
                ->where('date_inscription', '>=', now()->subHour())
                ->count();
            if ($recent >= 5) {
                $v->errors()->add('throttle', 'Trop d\'inscriptions depuis cette adresse — réessayez plus tard.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        if ($errors->count() === 1 && $errors->has('website')) {
            // Honeypot : on renvoie une erreur silencieuse pour ne pas
            // signaler la détection au bot.
            throw ValidationException::withMessages([
                'website' => ['__honeypot__'],
            ])->status(422);
        }

        parent::failedValidation($validator);
    }
}
