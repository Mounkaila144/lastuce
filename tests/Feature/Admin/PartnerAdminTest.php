<?php

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function asPartnerAdmin(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);
}

it('rend la liste des partenaires en Inertia avec stats', function () {
    Partner::factory()->count(2)->create();
    Partner::factory()->hidden()->create();

    $this->actingAs(asPartnerAdmin())
        ->get('/admin/partners')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Partners/Index')
            ->has('partners.data', 3)
            ->where('stats.total', 3)
            ->where('stats.visible', 2)
            ->where('stats.hidden', 1)
        );
});

it('crée un partenaire avec logo et redirige avec flash success', function () {
    $this->actingAs(asPartnerAdmin())
        ->post('/admin/partners', [
            'nom' => 'Orange Niger',
            'site_web' => 'https://orange.ne',
            'is_visible' => true,
            'ordre' => 0,
            'logo' => UploadedFile::fake()->image('logo.png', 320, 160),
        ])
        ->assertRedirect('/admin/partners')
        ->assertSessionHas('success');

    $partner = Partner::first();
    expect($partner)->not->toBeNull()
        ->and($partner->nom)->toBe('Orange Niger')
        ->and($partner->getFirstMedia('logo'))->not->toBeNull();
});

it('refuse la création sans logo ni nom', function () {
    $this->actingAs(asPartnerAdmin())
        ->post('/admin/partners', [
            'is_visible' => true,
        ])
        ->assertSessionHasErrors(['nom', 'logo']);
});

it('met à jour un partenaire', function () {
    $partner = Partner::factory()->create(['nom' => 'Ancien']);

    $this->actingAs(asPartnerAdmin())
        ->put("/admin/partners/{$partner->id}", [
            'nom' => 'Nouveau nom',
            'is_visible' => false,
            'ordre' => 3,
        ])
        ->assertRedirect('/admin/partners')
        ->assertSessionHas('success');

    $partner->refresh();
    expect($partner->nom)->toBe('Nouveau nom')
        ->and($partner->is_visible)->toBeFalse();
});

it('supprime un partenaire', function () {
    $partner = Partner::factory()->create();

    $this->actingAs(asPartnerAdmin())
        ->delete("/admin/partners/{$partner->id}")
        ->assertRedirect('/admin/partners');

    expect(Partner::find($partner->id))->toBeNull();
});

it('expose les logos de partenaires visibles sur la page d\'accueil', function () {
    $visible = Partner::factory()->create(['nom' => 'Visible Co']);
    $visible->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
    Partner::factory()->hidden()->create();

    $this->get('/fr')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('partners', 1)
            ->where('partners.0.nom', 'Visible Co')
        );
});
