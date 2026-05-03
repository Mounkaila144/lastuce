<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Story S6.3 — formulaire de contact public. Rate-limit applicatif 3/h/IP
 * (en plus du middleware HTTP `security:contact`). Honeypot inclus.
 */
class StoreContactRequest extends FormRequest
{
    public const SUJETS = [
        'general' => 'Question générale',
        'technique' => 'Problème technique',
        'partenariat' => 'Proposition de partenariat',
        'presse' => 'Demande presse / média',
        'astuce' => 'À propos d\'une astuce',
        'feedback' => 'Commentaire / suggestion',
        'autre' => 'Autre sujet',
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
            'sujet' => ['required', Rule::in(array_keys(self::SUJETS))],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'cgv' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cgv.accepted' => 'Vous devez accepter notre politique de confidentialité.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
            'website.max' => 'Erreur de soumission.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $ip = $this->ip();
            if (! $ip) {
                return;
            }

            $key = "contact-form:{$ip}";
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                $minutes = max(1, (int) ceil($seconds / 60));
                $v->errors()->add(
                    'throttle',
                    "Trop de messages envoyés depuis votre adresse — réessayez dans {$minutes} min.",
                );
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        if ($errors->count() === 1 && $errors->has('website')) {
            throw ValidationException::withMessages([
                'website' => ['__honeypot__'],
            ])->status(422);
        }

        parent::failedValidation($validator);
    }
}
