<?php

use App\Models\BlogArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('rend la fiche article en Inertia', function () {
    $article = BlogArticle::factory()->published()->create([
        'titre' => 'Comment réussir sa pâte à pain',
        'slug' => 'pate-a-pain',
        'contenu' => '<p>Étape 1...</p>',
        'extrait' => 'Une recette simple.',
        'categorie' => 'conseils',
        'mots_cles' => ['pain', 'cuisine'],
    ]);

    get("/fr/blog/{$article->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Show')
            ->where('article.slug', 'pate-a-pain')
            ->where('article.titre', 'Comment réussir sa pâte à pain')
            ->where('article.contenu', '<p>Étape 1...</p>')
            ->where('article.categorie', 'conseils')
            ->has('seo.title')
            ->has('seo.canonical')
            ->has('sidebar')
        );
});

it('renvoie 404 sur un article non publié', function () {
    $article = BlogArticle::factory()->draft()->create(['slug' => 'cache']);

    get("/fr/blog/{$article->slug}")->assertNotFound();
});

it('renvoie 404 sur un article programmé dans le futur', function () {
    $article = BlogArticle::factory()->scheduled()->create([
        'slug' => 'futur',
        'date_publication' => now()->addDays(3),
    ]);

    get("/fr/blog/{$article->slug}")->assertNotFound();
});

it('incrémente le compteur de vues une seule fois par session', function () {
    $article = BlogArticle::factory()->published()->create(['vues' => 0]);

    $this->withSession([])->get("/fr/blog/{$article->slug}")->assertOk();
    expect($article->fresh()->vues)->toBe(1);

    // Deuxième hit dans la même session : pas d'incrément.
    $this->get("/fr/blog/{$article->slug}");
    expect($article->fresh()->vues)->toBe(1);
});

it('expose la navigation prev/next sur les dates de publication', function () {
    BlogArticle::factory()->published()->create([
        'titre' => 'Précédent',
        'slug' => 'prev',
        'date_publication' => now()->subDays(2),
    ]);
    $current = BlogArticle::factory()->published()->create([
        'titre' => 'Courant',
        'slug' => 'current',
        'date_publication' => now()->subDay(),
    ]);
    BlogArticle::factory()->published()->create([
        'titre' => 'Suivant',
        'slug' => 'next',
        'date_publication' => now(),
    ]);

    get("/fr/blog/{$current->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('previous.slug', 'prev')
            ->where('next.slug', 'next')
        );
});
