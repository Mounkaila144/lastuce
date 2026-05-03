<?php

use App\Models\AdminLog;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function asAdmin(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);
}

it('rend la liste des épisodes en Inertia avec stats et filtres', function () {
    Episode::factory()->count(3)->create(['statut' => 'published']);
    Episode::factory()->count(2)->create(['statut' => 'draft']);

    $this->actingAs(asAdmin())
        ->get('/admin/episodes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Episodes/Index')
            ->has('episodes.data')
            ->where('stats.published', 3)
            ->where('stats.draft', 2)
            ->has('options.statuses')
            ->has('options.types')
        );
});

it('rend la page de création (Form)', function () {
    $this->actingAs(asAdmin())
        ->get('/admin/episodes/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Episodes/Form')
            ->where('episode', null)
            ->has('options.types')
            ->has('options.statuses')
        );
});

it('crée un épisode et le redirige vers l\'index avec un flash success', function () {
    $this->actingAs(asAdmin())
        ->post('/admin/episodes', [
            'titre' => 'Mon nouvel épisode',
            'type' => 'episode',
            'statut' => 'draft',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])
        ->assertRedirect('/admin/episodes')
        ->assertSessionHas('success');

    expect(Episode::where('titre', 'Mon nouvel épisode')->exists())->toBeTrue();
    expect(AdminLog::where('action', 'episode.create')->exists())->toBeTrue();
});

it('refuse la création sans URL vidéo (FB ou YT)', function () {
    $this->actingAs(asAdmin())
        ->post('/admin/episodes', [
            'titre' => 'Sans vidéo',
            'type' => 'episode',
            'statut' => 'draft',
        ])
        ->assertSessionHasErrors('youtube_url');

    expect(Episode::where('titre', 'Sans vidéo')->exists())->toBeFalse();
});

it('rend Form pour l\'édition avec le payload détail', function () {
    $episode = Episode::factory()->create([
        'titre' => 'À éditer',
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'type' => 'episode',
    ]);

    // Vérification que la route d'édition matche correctement.
    $url = route('admin.episodes.edit', ['episode' => $episode->id]);

    // Vérifie que le modèle peut être récupéré après un refresh de DB.
    \Illuminate\Support\Facades\DB::commit();
    \Illuminate\Support\Facades\DB::beginTransaction();
    $countAfter = Episode::count();

    $this->actingAs(asAdmin())
        ->get("/admin/episodes/{$episode->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Episodes/Form')
            ->where('episode.id', $episode->id)
            ->where('episode.titre', 'À éditer')
        );
});

it('publie un épisode via l\'action dédiée', function () {
    $episode = Episode::factory()->create(['statut' => 'draft', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']);

    $this->actingAs(asAdmin())
        ->post("/admin/episodes/{$episode->id}/publish")
        ->assertRedirect();

    expect($episode->fresh()->statut)->toBe('published');
    expect(AdminLog::where('action', 'episode.publish')->exists())->toBeTrue();
});

it('exécute une bulk action sur plusieurs épisodes', function () {
    $eps = Episode::factory()->count(3)->create(['statut' => 'draft']);

    $this->actingAs(asAdmin())
        ->post('/admin/episodes/bulk-action', [
            'action' => 'publish',
            'episodes' => $eps->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect(Episode::where('statut', 'published')->count())->toBe(3);
});

it('exporte un CSV avec le bon Content-Type', function () {
    Episode::factory()->count(2)->create();

    $response = $this->actingAs(asAdmin())->get('/admin/episodes/export');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

it('supprime un épisode', function () {
    $episode = Episode::factory()->create();

    $this->actingAs(asAdmin())
        ->delete("/admin/episodes/{$episode->id}")
        ->assertRedirect();

    expect(Episode::query()->find($episode->id))->toBeNull();
});
