<?php

use App\Jobs\SendAstuceConfirmationEmail;
use App\Models\AstucesSoumise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function validPayload(array $overrides = []): array
{
    return array_merge([
        'nom' => 'Camille Dupont',
        'email' => 'camille@example.com',
        'titre_astuce' => 'Nettoyer une planche en bois au citron',
        'categorie' => 'menage',
        'difficulte' => 'facile',
        'temps_estime' => 5,
        'description' => 'Une astuce naturelle pour entretenir vos planches en bois sans produit chimique.',
        'materiel_requis' => 'Un demi-citron, gros sel, eau tiède',
        'etapes' => [
            'Saupoudrer du gros sel sur la planche',
            'Frotter avec le demi-citron',
            'Rincer puis sécher',
        ],
        'conseils' => 'À répéter une fois par mois.',
        'cgv' => true,
    ], $overrides);
}

it('crée une astuce et dispatch le job email', function () {
    Bus::fake();

    $response = $this->post('/fr/astuces', validPayload());

    $response->assertRedirect();
    $astuce = AstucesSoumise::firstOrFail();
    expect($astuce->status)->toBe(AstucesSoumise::STATUS_EN_ATTENTE)
        ->and($astuce->etapes)->toHaveCount(3);

    Bus::assertDispatched(SendAstuceConfirmationEmail::class, fn ($job) => $job->astuceId === $astuce->id);
});

it('rejette une soumission sans étape', function () {
    $response = $this->post('/fr/astuces', validPayload(['etapes' => []]));
    $response->assertSessionHasErrors('etapes');
    expect(AstucesSoumise::count())->toBe(0);
});

it('rejette une soumission sans CGV', function () {
    $response = $this->post('/fr/astuces', validPayload(['cgv' => false]));
    $response->assertSessionHasErrors('cgv');
});

it('rejette quand le honeypot est rempli', function () {
    $response = $this->post('/fr/astuces', validPayload(['website' => 'http://spam.com']));
    $response->assertSessionHasErrors('website');
    expect(AstucesSoumise::count())->toBe(0);
});

it('renvoie 404 sur la page show si l\'astuce n\'est pas approuvée', function () {
    $astuce = AstucesSoumise::create(array_merge(validPayload(), [
        'status' => AstucesSoumise::STATUS_EN_ATTENTE,
    ]));

    $this->get("/fr/astuces/{$astuce->id}")->assertNotFound();
});

it('affiche la page show pour une astuce approuvée', function () {
    $astuce = AstucesSoumise::create(array_merge(validPayload(), [
        'status' => AstucesSoumise::STATUS_APPROUVE,
    ]));

    $this->get("/fr/astuces/{$astuce->id}")->assertOk();
});

it('autorise le suivi sans authentification', function () {
    $astuce = AstucesSoumise::create(array_merge(validPayload(), [
        'status' => AstucesSoumise::STATUS_APPROUVE,
        'commentaire_admin' => 'Bravo !',
    ]));

    $this->get("/fr/astuces/track/{$astuce->id}")->assertOk();
});
