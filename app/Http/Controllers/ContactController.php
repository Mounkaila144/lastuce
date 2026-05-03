<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Jobs\SendContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Story S6.3 — formulaire contact.
 *
 * Email envoyé à `config('mail.contact_to')` (ou `mail.from.address` en
 * fallback) ; rate-limit applicatif 3 messages/h/IP.
 */
class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact/Create', [
            'options' => [
                'sujets' => collect(StoreContactRequest::SUJETS)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ]);
    }

    public function store(StoreContactRequest $request, $locale): RedirectResponse
    {
        $payload = $request->validated();
        $reference = $this->generateReference();

        SendContactMessage::dispatch(
            reference: $reference,
            nom: $payload['nom'],
            email: $payload['email'],
            sujet: $payload['sujet'],
            sujetLabel: StoreContactRequest::SUJETS[$payload['sujet']] ?? $payload['sujet'],
            message: $payload['message'],
        );

        // Compte la requête côté limiter (le FormRequest ne fait que vérifier).
        RateLimiter::hit("contact-form:{$request->ip()}", 3600);

        return redirect()
            ->route('contact.success', ['locale' => $locale])
            ->with('success', 'Votre message a bien été envoyé. Nous vous répondrons sous 24 à 48 h.')
            ->with('reference', $reference);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Contact/Success', [
            'reference' => $request->session()->get('reference'),
        ]);
    }

    /**
     * Compat ancien front legacy (non utilisé par les pages Inertia).
     */
    public function validateForm(Request $request)
    {
        return response()->json(['valid' => true]);
    }

    private function generateReference(): string
    {
        return 'CONT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
