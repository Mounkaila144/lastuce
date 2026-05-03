<?php

namespace App\Jobs;

use App\Models\AstucesSoumise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Story S4.1 — confirmation par email après soumission. Délégué à la queue
 * pour ne pas bloquer la réponse HTTP. Le template Blade est volontairement
 * minimal (1 mail, 1 vue) — l'évolution v1.1 viendra ajouter Brevo.
 */
class SendAstuceConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $astuceId)
    {
    }

    public function handle(): void
    {
        $astuce = AstucesSoumise::find($this->astuceId);
        if (! $astuce || ! $astuce->email) {
            return;
        }

        Mail::raw(
            "Merci {$astuce->nom},\n\nNous avons bien reçu votre astuce \"{$astuce->titre_astuce}\".\n"
            . "Vous pouvez suivre son traitement à l'adresse : "
            . url("/fr/astuces/track/{$astuce->id}")
            . "\n\nNotre équipe vous répondra sous quelques jours ouvrés.\n\nL'équipe L'Astuce",
            function ($message) use ($astuce) {
                $message->to($astuce->email, $astuce->nom)
                    ->subject('Confirmation de réception — L\'Astuce');
            },
        );
    }
}
