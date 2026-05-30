<?php

use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function asGalleryAdmin(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);
}

it('rend la liste de la galerie en Inertia avec stats', function () {
    GalleryImage::factory()->count(2)->create();
    GalleryImage::factory()->hidden()->create();

    $this->actingAs(asGalleryAdmin())
        ->get('/admin/gallery')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Gallery/Index')
            ->has('images.data', 3)
            ->where('stats.total', 3)
            ->where('stats.visible', 2)
            ->where('stats.hidden', 1)
        );
});

it('rend le formulaire de création', function () {
    $this->actingAs(asGalleryAdmin())
        ->get('/admin/gallery/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Gallery/Form')
            ->where('image', null)
        );
});

it('crée une image avec upload et redirige avec flash success', function () {
    $this->actingAs(asGalleryAdmin())
        ->post('/admin/gallery', [
            'titre' => 'Tournage épisode 12',
            'description' => 'Sur le plateau',
            'is_visible' => true,
            'ordre' => 0,
            'image' => UploadedFile::fake()->image('shoot.jpg', 800, 600),
        ])
        ->assertRedirect('/admin/gallery')
        ->assertSessionHas('success');

    $image = GalleryImage::first();
    expect($image)->not->toBeNull()
        ->and($image->titre)->toBe('Tournage épisode 12')
        ->and($image->getFirstMedia('image'))->not->toBeNull();
});

it('refuse la création sans image', function () {
    $this->actingAs(asGalleryAdmin())
        ->post('/admin/gallery', [
            'titre' => 'Sans image',
            'is_visible' => true,
        ])
        ->assertSessionHasErrors('image');
});

it('met à jour une image existante', function () {
    $image = GalleryImage::factory()->create(['titre' => 'Ancien titre']);

    $this->actingAs(asGalleryAdmin())
        ->put("/admin/gallery/{$image->id}", [
            'titre' => 'Nouveau titre',
            'is_visible' => false,
            'ordre' => 5,
        ])
        ->assertRedirect('/admin/gallery')
        ->assertSessionHas('success');

    $image->refresh();
    expect($image->titre)->toBe('Nouveau titre')
        ->and($image->is_visible)->toBeFalse()
        ->and($image->ordre)->toBe(5);
});

it('supprime une image', function () {
    $image = GalleryImage::factory()->create();

    $this->actingAs(asGalleryAdmin())
        ->delete("/admin/gallery/{$image->id}")
        ->assertRedirect('/admin/gallery');

    expect(GalleryImage::find($image->id))->toBeNull();
});

it('refuse l\'accès à un visiteur non authentifié', function () {
    $this->get('/admin/gallery')->assertRedirect();
});
