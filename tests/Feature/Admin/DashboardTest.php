<?php

use App\Models\AstucesSoumise;
use App\Models\Episode;
use App\Models\NewsletterAbonne;
use App\Models\Partenariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

function adminUser(): User
{
    return User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);
}

it('redirige vers /admin/login si non authentifié', function () {
    get('/admin/dashboard')->assertRedirect('/admin/login');
});

it('renvoie 403 pour un user non admin', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it('rend le dashboard en Inertia avec les agrégats attendus', function () {
    Episode::factory()->count(2)->create(['statut' => 'published']);
    Episode::factory()->count(1)->create(['statut' => 'draft']);
    AstucesSoumise::factory()->count(3)->create(['status' => 'en_attente']);
    Partenariat::factory()->count(1)->create(['status' => 'nouveau']);
    NewsletterAbonne::factory()->count(4)->create(['status' => 'actif']);

    $this->actingAs(adminUser())
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('stats.episodes')
            ->where('stats.episodes.published', 2)
            ->where('stats.astuces.pending', 3)
            ->where('stats.newsletter.active', 4)
            ->has('chartData.labels', 30)
            ->has('chartData.episodes', 30)
            ->has('chartData.astuces', 30)
            ->has('recentActivity')
            ->has('alerts')
        );
});

it('partage admin.user et admin.pending_counts via Inertia sur les pages admin', function () {
    AstucesSoumise::factory()->count(2)->create(['status' => 'en_attente']);
    $admin = adminUser();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.user.email', $admin->email)
            ->where('admin.pending_counts.astuces', 2)
        );
});

it('ne partage pas admin sur les pages publiques', function () {
    get('/fr/blog')
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin', null)
        );
});
