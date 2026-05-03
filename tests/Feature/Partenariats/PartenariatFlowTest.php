<?php

use App\Jobs\SendPartenariatConfirmationEmail;
use App\Models\AdminNotification;
use App\Models\Partenariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function partenariatPayload(array $overrides = []): array
{
    return array_merge([
        'nom_entreprise' => 'Acme Corp',
        'contact' => 'Jean Martin',
        'email' => 'jean@acme.test',
        'telephone' => '+33 1 23 45 67 89',
        'site_web' => 'https://acme.test',
        'type_partenariat' => 'sponsoring',
        'budget_envisage' => '5000_10000',
        'message' => 'Nous souhaitons sponsoriser une saison complète de votre émission, voici notre brief détaillé.',
        'cgv' => true,
    ], $overrides);
}

it('crée un partenariat, dispatch l\'email et notifie les admins', function () {
    Bus::fake();

    User::create([
        'name' => 'Admin',
        'email' => 'admin@lastuce.test',
        'password' => 'secret',
        'is_admin' => true,
        'role' => 'admin',
    ]);

    $response = $this->post('/fr/partenariats', partenariatPayload());

    $response->assertRedirect();
    $partenariat = Partenariat::firstOrFail();
    expect($partenariat->status)->toBe(Partenariat::STATUS_NOUVEAU)
        ->and($partenariat->nom_entreprise)->toBe('Acme Corp')
        ->and($partenariat->type_partenariat)->toBe('sponsoring');

    Bus::assertDispatched(
        SendPartenariatConfirmationEmail::class,
        fn ($job) => $job->partenariatId === $partenariat->id,
    );

    expect(AdminNotification::where('type', 'new_partenariat')->count())->toBe(1);
});

it('refuse une description trop courte', function () {
    $response = $this->post('/fr/partenariats', partenariatPayload(['message' => 'Trop court.']));
    $response->assertSessionHasErrors('message');
    expect(Partenariat::count())->toBe(0);
});

it('refuse une demande sans acceptation des CGV', function () {
    $response = $this->post('/fr/partenariats', partenariatPayload(['cgv' => false]));
    $response->assertSessionHasErrors('cgv');
});

it('rejette une soumission honeypot', function () {
    $response = $this->post('/fr/partenariats', partenariatPayload(['website' => 'http://spam.test']));
    $response->assertSessionHasErrors('website');
    expect(Partenariat::count())->toBe(0);
});

it('rend la page d\'info /partenariats', function () {
    $this->get('/fr/partenariats')->assertOk();
});
