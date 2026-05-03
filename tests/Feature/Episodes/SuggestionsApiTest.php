<?php

use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renvoie des suggestions sur les épisodes publiés', function () {
    Episode::create([
        'titre' => 'Astuce citron magique',
        'slug' => 'astuce-citron-magique',
        'type' => 'episode',
        'statut' => 'published',
        'date_publication' => now(),
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
    ]);

    $response = $this->getJson('/api/search/suggestions?q=citron');

    $response->assertOk();
    $response->assertJsonStructure(['suggestions' => [['type', 'label', 'href']]]);
    expect($response->json('suggestions.0.type'))->toBe('episode')
        ->and($response->json('suggestions.0.label'))->toBe('Astuce citron magique');
});

it('refuse les requêtes trop courtes', function () {
    $this->getJson('/api/search/suggestions?q=a')->assertStatus(422);
});
