<?php

use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.supported_locales' => ['fr' => 'Français', 'en' => 'English']]);
});

it('rend un sitemap.xml valide avec les épisodes publiés', function () {
    Episode::create([
        'titre' => 'Astuce du sitemap',
        'slug' => 'astuce-sitemap',
        'type' => 'episode',
        'statut' => 'published',
        'date_publication' => now()->subDay(),
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ]);

    cache()->forget('sitemap.xml');

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    expect($response->getContent())
        ->toStartWith('<?xml')
        ->toContain('<urlset')
        ->toContain('/fr/episodes/astuce-sitemap');
});

it('rend un flux RSS épisodes valide', function () {
    Episode::create([
        'titre' => 'Astuce du RSS',
        'slug' => 'astuce-rss',
        'type' => 'episode',
        'statut' => 'published',
        'date_publication' => now()->subDay(),
        'description' => 'Description courte',
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ]);

    cache()->forget('rss.episodes.xml');

    $response = $this->get('/episodes/rss');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    expect($response->getContent())
        ->toContain('<rss version="2.0"')
        ->toContain('<title>Astuce du RSS</title>');
});
