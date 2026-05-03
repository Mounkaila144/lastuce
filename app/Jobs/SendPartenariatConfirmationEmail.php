<?php

namespace App\Jobs;

use App\Models\Partenariat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Story S6.2 — accusé de réception d'une demande de partenariat.
 */
class SendPartenariatConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $partenariatId)
    {
    }

    public function handle(): void
    {
        $p = Partenariat::find($this->partenariatId);
        if (! $p || ! $p->email) {
            return;
        }

        $body = "Bonjour {$p->contact},\n\n"
            . "Merci pour votre demande de partenariat avec L'Astuce.\n"
            . "Nous étudions votre proposition (« {$p->nom_entreprise} ») et reviendrons vers vous sous 5 jours ouvrés.\n\n"
            . "Référence : #{$p->id}\n\n"
            . "L'équipe L'Astuce";

        Mail::raw($body, function ($message) use ($p) {
            $message->to($p->email, $p->contact)
                ->subject('Confirmation de réception — Demande de partenariat L\'Astuce');
        });
    }
}
