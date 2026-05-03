<?php

use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('rend la page blog en Inertia avec ses articles paginés', function () {
    BlogArticle::factory()->count(11)->published()->create();

    get('/fr/blog')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index')
            ->has('articles.data', 9) // PER_PAGE = 9 (S7.1)
            ->has('articles.meta')
            ->where('articles.meta.total', 11)
            ->has('filters')
            ->has('options.sorts')
            ->has('sidebar.recent')
        );
});

it('exclut les articles non publiés', function () {
    BlogArticle::factory()->published()->create(['titre' => 'Visible']);
    BlogArticle::factory()->draft()->create(['titre' => 'Brouillon']);
    BlogArticle::factory()->scheduled()->create(['titre' => 'Programmé']);

    get('/fr/blog')
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.meta.total', 1)
            ->where('articles.data.0.titre', 'Visible')
        );
});

it('filtre par catégorie via la query string', function () {
    BlogArticle::factory()->published()->create(['titre' => 'A', 'categorie' => 'conseils']);
    BlogArticle::factory()->published()->create(['titre' => 'B', 'categorie' => 'lifestyle']);

    get('/fr/blog?categorie=conseils')
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.meta.total', 1)
            ->where('articles.data.0.titre', 'A')
            ->where('filters.categorie', 'conseils')
        );
});

it('filtre par recherche textuelle', function () {
    BlogArticle::factory()->published()->create(['titre' => 'Tomates au four']);
    BlogArticle::factory()->published()->create(['titre' => 'Banane plantain']);

    get('/fr/blog?search=tomate')
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.meta.total', 1)
            ->where('filters.search', 'tomate')
        );
});

it('expose la route archive en filtrant par année et mois', function () {
    BlogArticle::factory()->published()->create([
        'titre' => 'Avril',
        'date_publication' => '2026-04-15 10:00:00',
    ]);
    BlogArticle::factory()->published()->create([
        'titre' => 'Mai',
        'date_publication' => '2026-05-15 10:00:00',
    ]);

    get('/fr/blog/archive/2026/4')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index')
            ->where('articles.meta.total', 1)
            ->where('articles.data.0.titre', 'Avril')
            ->where('filters.year', 2026)
            ->where('filters.month', 4)
        );
});

it('alias /blog/search délègue à index avec ?q= mappé sur search', function () {
    BlogArticle::factory()->published()->create(['titre' => 'Recette gombo']);
    BlogArticle::factory()->published()->create(['titre' => 'Astuce four']);

    get('/fr/blog/search?q=gombo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Index')
            ->where('filters.search', 'gombo')
            ->where('articles.meta.total', 1)
        );
});
