<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Story S8.4 — Rejet d'une astuce soumise.
 * Le commentaire admin est OBLIGATOIRE au rejet : il est renvoyé au
 * soumettant via la page de suivi (et plus tard par email en v1.1).
 */
class RejectAstuceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'commentaire_admin' => ['required', 'string', 'min:10', 'max:5000'],
            'send_notification' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire_admin.required' => 'Un commentaire est requis pour expliquer le rejet à l\'auteur.',
            'commentaire_admin.min' => 'Merci de détailler la raison du rejet (au moins 10 caractères).',
        ];
    }
}
