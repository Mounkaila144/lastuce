<?php

use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('rend la page catégorie filtrée', function () {
    BlogArticle::factory()->count(2)->published()->create(['categorie' => 'coulisses']);
    BlogArticle::factory()->published()->create(['categorie' => 'lifestyle']);

    get('/fr/blog/category/coulisses')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Category')
            ->where('articles.meta.total', 2)
            ->where('category.slug', 'coulisses')
            ->where('category.name', 'Coulisses')
        );
});

it('rend la page tag filtrée via mots_cles', function () {
    BlogArticle::factory()->published()->create([
        'titre' => 'Article taggé',
        'mots_cles' => ['cuisine', 'rapide'],
    ]);
    BlogArticle::factory()->published()->create([
        'titre' => 'Article non taggé',
        'mots_cles' => ['voyage'],
    ]);

    get('/fr/blog/tag/cuisine')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Tag')
            ->where('articles.meta.total', 1)
            ->where('articles.data.0.titre', 'Article taggé')
            ->where('tag.slug', 'cuisine')
        );
});

it('renvoie une page vide si la catégorie ne contient aucun article', function () {
    BlogArticle::factory()->published()->create(['categorie' => 'lifestyle']);

    get('/fr/blog/category/inconnue')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.meta.total', 0)
            ->where('category.slug', 'inconnue')
        );
});
