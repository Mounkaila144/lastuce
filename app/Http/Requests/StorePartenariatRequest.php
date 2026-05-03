<?php

namespace App\Http\Requests;

use App\Models\Partenariat;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Story S6.2 — validation du formulaire partenariat. Honeypot + rate-limit
 * applicatif (le middleware HTTP `security:contact` couvre déjà le DDoS).
 */
class StorePartenariatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_entreprise' => ['required', 'string', 'min:2', 'max:200'],
            'contact' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'telephone' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]{6,20}$/'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'type_partenariat' => ['required', Rule::in(array_keys(Partenariat::TYPES))],
            'budget_envisage' => ['required', Rule::in(array_keys(Partenariat::BUDGETS))],
            'message' => ['required', 'string', 'min:30', 'max:3000'],
            'cgv' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cgv.accepted' => 'Vous devez accepter d\'être contacté.',
            'message.min' => 'Décrivez votre projet en au moins 30 caractères.',
            'website.max' => 'Erreur de soumission.',
            'telephone.regex' => 'Numéro de téléphone invalide.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $ip = $this->ip();
            if (! $ip) {
                return;
            }
            $recent = Partenariat::where('ip_demandeur', $ip)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($recent >= 3) {
                $v->errors()->add('throttle', 'Vous avez déjà soumis plusieurs demandes — patientez 24 h.');
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
