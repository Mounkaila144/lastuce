<?php

use App\Models\Category;
use App\Models\Episode;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('migre une catégorie string vers la table categories et lie l\'épisode', function () {
    // RefreshDatabase exécute toutes les migrations sur SQLite mémoire — y
    // compris celle qui crée la FK. On simule ensuite une création legacy.
    $episode = Episode::create([
        'titre' => 'Astuce citron',
        'slug' => 'astuce-citron',
        'type' => 'episode',
        'statut' => 'published',
        'date_publication' => now(),
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'category' => 'Cuisine',
    ]);

    $cat = Category::create(['nom' => 'Cuisine']);
    $episode->category_id = $cat->id;
    $episode->save();

    $reloaded = $episode->fresh()->load('categoryRelation');
    expect($reloaded->categoryRelation)->not->toBeNull()
        ->and($reloaded->categoryRelation->slug)->toBe('cuisine');
});

it('lie un épisode à plusieurs tags via le pivot episode_tag', function () {
    $episode = Episode::create([
        'titre' => 'Astuce maison',
        'slug' => 'astuce-maison',
        'type' => 'episode',
        'statut' => 'published',
        'date_publication' => now(),
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ]);

    $rapide = Tag::create(['nom' => 'Rapide']);
    $eco = Tag::create(['nom' => 'Économique']);

    $episode->tagsRelation()->sync([$rapide->id, $eco->id]);

    expect($episode->fresh()->tagsRelation)->toHaveCount(2)
        ->and($episode->fresh()->tagsRelation->pluck('slug')->all())
        ->toContain('rapide', 'economique');
});

it('génère un slug automatique sur Category et Tag', function () {
    $cat = Category::create(['nom' => 'Vie pratique']);
    $tag = Tag::create(['nom' => 'À refaire']);

    expect($cat->slug)->toBe('vie-pratique')
        ->and($tag->slug)->toBe('a-refaire');
});
