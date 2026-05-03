<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects the bare root to a localized home', function () {
    $response = get('/');

    expect($response->status())->toBe(302)
        ->and($response->headers->get('Location'))->toMatch('#/(fr|en)$#');
});

it('home returns 200', function () {
    get('/fr')->assertOk();
});

it('renders the inertia smoke page', function () {
    get('/__inertia-test')
        ->assertOk()
        ->assertSee('InertiaTest', false);
});
