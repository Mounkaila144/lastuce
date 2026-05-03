<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Story S6.3 — délivre le message de contact à l'adresse configurée
 * (`config('mail.contact_to')` ou MAIL_CONTACT_TO côté .env), avec un
 * accusé de réception au demandeur.
 */
class SendContactMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $reference,
        public readonly string $nom,
        public readonly string $email,
        public readonly string $sujet,
        public readonly string $sujetLabel,
        public readonly string $message,
    ) {
    }

    public function handle(): void
    {
        $to = config('mail.contact_to') ?: config('mail.from.address');
        if (! $to) {
            return;
        }

        $teamBody = "Référence : {$this->reference}\n"
            . "Sujet : {$this->sujetLabel}\n"
            . "De : {$this->nom} <{$this->email}>\n"
            . str_repeat('-', 40) . "\n\n"
            . $this->message;

        Mail::raw($teamBody, function ($mail) use ($to) {
            $mail->to($to)
                ->replyTo($this->email, $this->nom)
                ->subject("[{$this->reference}] Contact — {$this->sujetLabel}");
        });

        $ackBody = "Bonjour {$this->nom},\n\n"
            . "Nous avons bien reçu votre message (référence {$this->reference}).\n"
            . "Notre équipe vous répondra sous 24 à 48 h ouvrées.\n\n"
            . "Rappel de votre message :\n" . str_repeat('-', 40) . "\n"
            . $this->message . "\n\n"
            . "L'équipe L'Astuce";

        Mail::raw($ackBody, function ($mail) {
            $mail->to($this->email, $this->nom)
                ->subject("Accusé de réception — L'Astuce [{$this->reference}]");
        });
    }
}
