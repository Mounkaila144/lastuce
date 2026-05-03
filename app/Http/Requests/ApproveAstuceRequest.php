<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Story S8.4 — Approbation d'une astuce soumise.
 * Le commentaire admin est optionnel à l'approbation.
 */
class ApproveAstuceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'commentaire_admin' => ['nullable', 'string', 'max:5000'],
            'send_notification' => ['nullable', 'boolean'],
        ];
    }
}
