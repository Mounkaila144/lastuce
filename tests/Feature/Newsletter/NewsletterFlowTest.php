<?php

use App\Jobs\SendNewsletterConfirmationEmail;
use App\Models\NewsletterAbonne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('crée un abonné inactif et dispatch le job de confirmation', function () {
    Bus::fake();

    $response = $this->post('/fr/newsletter', [
        'email' => 'eve@example.com',
        'prenom' => 'Eve',
        'cgv' => true,
        'frequence_envoi' => 'hebdomadaire',
    ]);

    $response->assertRedirect('/fr/newsletter/success');
    $abonne = NewsletterAbonne::firstOrFail();

    expect($abonne->status)->toBe(NewsletterAbonne::STATUS_INACTIF)
        ->and($abonne->confirme)->toBeFalse()
        ->and($abonne->token_desabonnement)->not->toBeEmpty()
        ->and($abonne->source_inscription)->toBe('site_web');

    Bus::assertDispatched(
        SendNewsletterConfirmationEmail::class,
        fn ($job) => $job->abonneId === $abonne->id,
    );
});

it('refuse une inscription sans acceptation des CGV', function () {
    $response = $this->post('/fr/newsletter', [
        'email' => 'noopt@example.com',
        'cgv' => false,
        'frequence_envoi' => 'hebdomadaire',
    ]);

    $response->assertSessionHasErrors('cgv');
    expect(NewsletterAbonne::count())->toBe(0);
});

it('refuse une inscription en doublon', function () {
    NewsletterAbonne::create([
        'email' => 'dup@example.com',
        'status' => NewsletterAbonne::STATUS_ACTIF,
        'confirme' => true,
    ]);

    $response = $this->post('/fr/newsletter', [
        'email' => 'dup@example.com',
        'cgv' => true,
        'frequence_envoi' => 'hebdomadaire',
    ]);

    $response->assertSessionHasErrors('email');
    expect(NewsletterAbonne::count())->toBe(1);
});

it('signale silencieusement les inscriptions honeypot', function () {
    $response = $this->post('/fr/newsletter', [
        'email' => 'bot@example.com',
        'cgv' => true,
        'frequence_envoi' => 'hebdomadaire',
        'website' => 'http://spam.com',
    ]);

    $response->assertSessionHasErrors('website');
    expect(NewsletterAbonne::count())->toBe(0);
});

it('confirme un abonné via le token et passe son statut à actif', function () {
    $abonne = NewsletterAbonne::create([
        'email' => 'pending@example.com',
        'status' => NewsletterAbonne::STATUS_INACTIF,
        'confirme' => false,
    ]);

    $this->get("/fr/newsletter/confirm/{$abonne->token_desabonnement}")->assertOk();

    $abonne->refresh();
    expect($abonne->confirme)->toBeTrue()
        ->and($abonne->status)->toBe(NewsletterAbonne::STATUS_ACTIF)
        ->and($abonne->date_confirmation)->not->toBeNull();
});

it('renvoie une page d\'erreur si le token de confirmation est inconnu', function () {
    $this->get('/fr/newsletter/confirm/inconnu')->assertOk();
    // Inertia rend la page Error sans 404, on s'assure juste qu'aucun abonné n'a été muté.
    expect(NewsletterAbonne::count())->toBe(0);
});

it('désabonne en POST avec raison/commentaire', function () {
    $abonne = NewsletterAbonne::create([
        'email' => 'leave@example.com',
        'status' => NewsletterAbonne::STATUS_ACTIF,
        'confirme' => true,
    ]);

    $response = $this->post(
        "/fr/newsletter/unsubscribe/{$abonne->token_desabonnement}",
        ['raison' => 'trop_emails', 'commentaire' => 'Trop de fréquence'],
    );
    $response->assertOk();

    $abonne->refresh();
    expect($abonne->status)->toBe(NewsletterAbonne::STATUS_DESABONNE)
        ->and($abonne->raison_desinscription)->toBe('trop_emails')
        ->and($abonne->date_desinscription)->not->toBeNull();
});

it('met à jour les préférences via le token', function () {
    $abonne = NewsletterAbonne::create([
        'email' => 'pref@example.com',
        'status' => NewsletterAbonne::STATUS_ACTIF,
        'confirme' => true,
        'frequence_envoi' => 'hebdomadaire',
    ]);

    $response = $this->post(
        "/fr/newsletter/preferences/{$abonne->token_desabonnement}",
        [
            'prenom' => 'Pref',
            'frequence_envoi' => 'mensuel',
            'interets' => ['cuisine', 'organisation'],
        ],
    );
    $response->assertRedirect();

    $abonne->refresh();
    expect($abonne->prenom)->toBe('Pref')
        ->and($abonne->frequence_envoi)->toBe('mensuel')
        ->and($abonne->interets)->toBe(['cuisine', 'organisation']);
});

it('régénère le jeton quand l\'utilisateur le demande', function () {
    $abonne = NewsletterAbonne::create([
        'email' => 'rotate@example.com',
        'status' => NewsletterAbonne::STATUS_ACTIF,
        'confirme' => true,
        'frequence_envoi' => 'hebdomadaire',
    ]);
    $oldToken = $abonne->token_desabonnement;

    $response = $this->post(
        "/fr/newsletter/preferences/{$oldToken}",
        [
            'frequence_envoi' => 'hebdomadaire',
            'regenerate_token' => 1,
        ],
    );

    $abonne->refresh();
    expect($abonne->token_desabonnement)->not->toBe($oldToken);
    $response->assertRedirect("/fr/newsletter/preferences/{$abonne->token_desabonnement}");
});

it('quick-subscribe enregistre la source footer et dispatch le job', function () {
    Bus::fake();

    $response = $this->post('/fr/newsletter/quick-subscribe', [
        'email' => 'foot@example.com',
        'source' => 'footer',
    ]);

    $response->assertRedirect();
    $abonne = NewsletterAbonne::firstOrFail();
    expect($abonne->source_inscription)->toBe('footer')
        ->and($abonne->status)->toBe(NewsletterAbonne::STATUS_INACTIF);

    Bus::assertDispatched(SendNewsletterConfirmationEmail::class);
});
