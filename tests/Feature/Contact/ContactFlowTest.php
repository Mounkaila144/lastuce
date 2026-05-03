<?php

use App\Jobs\SendContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('contact-form:127.0.0.1');
});

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'nom' => 'Sophie',
        'email' => 'sophie@example.com',
        'sujet' => 'general',
        'message' => 'Bonjour, j\'ai une question sur votre dernier épisode.',
        'cgv' => true,
    ], $overrides);
}

it('envoie le message, dispatch le job et redirige vers /contact/success', function () {
    Bus::fake();

    $response = $this->post('/fr/contact', contactPayload());

    $response->assertRedirect('/fr/contact/success');
    $response->assertSessionHas('success');
    $response->assertSessionHas('reference');

    Bus::assertDispatched(SendContactMessage::class, function ($job) {
        return $job->email === 'sophie@example.com'
            && $job->sujet === 'general';
    });
});

it('refuse un message trop court', function () {
    $response = $this->post('/fr/contact', contactPayload(['message' => 'salut']));
    $response->assertSessionHasErrors('message');
});

it('refuse un message sans CGV', function () {
    $response = $this->post('/fr/contact', contactPayload(['cgv' => false]));
    $response->assertSessionHasErrors('cgv');
});

it('rejette une soumission honeypot', function () {
    $response = $this->post('/fr/contact', contactPayload(['website' => 'http://spam.test']));
    $response->assertSessionHasErrors('website');
});

it('limite à 3 messages par heure et par IP', function () {
    Bus::fake();

    for ($i = 0; $i < 3; $i++) {
        $this->post('/fr/contact', contactPayload(['message' => "Message numéro {$i} sans soucis particulier."]))
            ->assertRedirect('/fr/contact/success');
    }

    $blocked = $this->post('/fr/contact', contactPayload(['message' => 'Quatrième message qui doit être bloqué.']));
    $blocked->assertSessionHasErrors('throttle');
});

it('rend la page /contact', function () {
    $this->get('/fr/contact')->assertOk();
});
