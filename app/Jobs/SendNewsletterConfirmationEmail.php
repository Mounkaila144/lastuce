<?php

namespace App\Jobs;

use App\Models\NewsletterAbonne;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Story S5.1 — envoi du mail de double opt-in. On reste sur Mail::raw pour
 * la v1 (pas de template HTML lourd) ; v1.1 introduira un Mailable + Brevo.
 */
class SendNewsletterConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $abonneId)
    {
    }

    public function handle(): void
    {
        $abonne = NewsletterAbonne::find($this->abonneId);
        if (! $abonne || ! $abonne->email) {
            return;
        }

        $confirmUrl = url("/fr/newsletter/confirm/{$abonne->token_desabonnement}");
        $unsubUrl = url("/fr/newsletter/unsubscribe/{$abonne->token_desabonnement}");

        $body = "Bonjour {$abonne->prenom_complet},\n\n"
            . "Merci de votre inscription à la newsletter L'Astuce.\n\n"
            . "Pour confirmer votre abonnement, cliquez sur ce lien :\n{$confirmUrl}\n\n"
            . "Si vous n'êtes pas à l'origine de cette inscription, vous pouvez l'ignorer ou cliquer ici pour la révoquer :\n{$unsubUrl}\n\n"
            . "L'équipe L'Astuce";

        Mail::raw($body, function ($message) use ($abonne) {
            $message->to($abonne->email, $abonne->prenom_complet)
                ->subject('Confirmez votre inscription à la newsletter L\'Astuce');
        });
    }
}
