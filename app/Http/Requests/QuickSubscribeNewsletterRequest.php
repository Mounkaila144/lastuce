<?php

namespace App\Http\Requests;

use App\Models\NewsletterAbonne;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Story S5.3 — variante allégée pour le mini-form du footer.
 * Email seul + source + honeypot, pas d'acceptation CGV (la mention est
 * affichée à côté du champ — le double opt-in fait office d'opt-in explicite).
 */
class QuickSubscribeNewsletterRequest extends FormRequest
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
            'source' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse est déjà inscrite à notre newsletter.',
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
            throw ValidationException::withMessages([
                'website' => ['__honeypot__'],
            ])->status(422);
        }

        parent::failedValidation($validator);
    }
}
