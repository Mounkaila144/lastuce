<?php

use App\Models\AdminLog;
use App\Models\AstucesSoumise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function asAdminUser(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);
}

it('rend la liste de modération en Inertia', function () {
    AstucesSoumise::factory()->count(2)->create(['status' => 'en_attente']);
    AstucesSoumise::factory()->count(1)->create(['status' => 'approuve']);

    $this->actingAs(asAdminUser())
        ->get('/admin/astuces')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Astuces/Index')
            ->has('astuces.data')
            ->where('stats.en_attente', 2)
            ->where('stats.approuve', 1)
        );
});

it('rend le détail d\'une astuce avec tous les champs métier', function () {
    $astuce = AstucesSoumise::factory()->create([
        'titre_astuce' => 'Recette éclair',
        'description' => 'Description longue...',
        'etapes' => ['Étape A', 'Étape B'],
        'status' => 'en_attente',
    ]);

    $this->actingAs(asAdminUser())
        ->get("/admin/astuces/{$astuce->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Astuces/Show')
            ->where('astuce.id', $astuce->id)
            ->where('astuce.titre_astuce', 'Recette éclair')
            ->has('astuce.etapes', 2)
        );
});

it('approuve une astuce et logue l\'action', function () {
    $astuce = AstucesSoumise::factory()->create(['status' => 'en_attente']);

    $this->actingAs(asAdminUser())
        ->post("/admin/astuces/{$astuce->id}/approve", [
            'commentaire_admin' => 'Top !',
        ])
        ->assertRedirect('/admin/astuces');

    expect($astuce->fresh()->status)->toBe('approuve');
    expect(AdminLog::where('action', 'astuce.approve')->exists())->toBeTrue();
});

it('refuse de rejeter une astuce sans commentaire', function () {
    $astuce = AstucesSoumise::factory()->create(['status' => 'en_attente']);

    $this->actingAs(asAdminUser())
        ->post("/admin/astuces/{$astuce->id}/reject", [
            'commentaire_admin' => '',
        ])
        ->assertSessionHasErrors('commentaire_admin');

    expect($astuce->fresh()->status)->toBe('en_attente');
});

it('rejette une astuce avec un commentaire et logue en warning', function () {
    $astuce = AstucesSoumise::factory()->create(['status' => 'en_attente']);

    $this->actingAs(asAdminUser())
        ->post("/admin/astuces/{$astuce->id}/reject", [
            'commentaire_admin' => 'Hors sujet, merci de proposer une astuce du quotidien.',
        ])
        ->assertRedirect('/admin/astuces');

    expect($astuce->fresh()->status)->toBe('rejete');
    expect(AdminLog::where('action', 'astuce.reject')->where('severity', 'warning')->exists())->toBeTrue();
});

it('exécute une bulk action approve sur plusieurs astuces', function () {
    $astuces = AstucesSoumise::factory()->count(3)->create(['status' => 'en_attente']);

    $this->actingAs(asAdminUser())
        ->post('/admin/astuces/bulk-action', [
            'action' => 'approve',
            'astuces' => $astuces->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect(AstucesSoumise::where('status', 'approuve')->count())->toBe(3);
});

it('refuse une bulk reject sans commentaire', function () {
    $astuces = AstucesSoumise::factory()->count(2)->create(['status' => 'en_attente']);

    $this->actingAs(asAdminUser())
        ->post('/admin/astuces/bulk-action', [
            'action' => 'reject',
            'astuces' => $astuces->pluck('id')->all(),
            'commentaire_admin' => '   ',
        ])
        ->assertSessionHasErrors('commentaire_admin');

    expect(AstucesSoumise::where('status', 'rejete')->count())->toBe(0);
});

it('export CSV répond avec text/csv', function () {
    AstucesSoumise::factory()->count(2)->create();

    $response = $this->actingAs(asAdminUser())->get('/admin/astuces/export');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});
