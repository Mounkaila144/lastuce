<?php

use App\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('rend la galerie publique en Inertia', function () {
    $image = GalleryImage::factory()->create();
    $image->addMedia(UploadedFile::fake()->image('shoot.jpg', 800, 600))
        ->toMediaCollection('image');

    $this->get('/fr/galerie')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Index')
            ->has('images', 1)
            ->where('images.0.titre', $image->titre)
        );
});

it('masque les images non visibles et celles sans média', function () {
    // Visible + média => affichée.
    $visible = GalleryImage::factory()->create();
    $visible->addMedia(UploadedFile::fake()->image('ok.jpg'))->toMediaCollection('image');

    // Visible mais sans média => filtrée (full_url null).
    GalleryImage::factory()->create();

    // Masquée => filtrée par le scope visible().
    $hidden = GalleryImage::factory()->hidden()->create();
    $hidden->addMedia(UploadedFile::fake()->image('hidden.jpg'))->toMediaCollection('image');

    $this->get('/fr/galerie')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Index')
            ->has('images', 1)
        );
});
