<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\RateLimiter::clear('admin_login:127.0.0.1');
});

it('rend la page de login admin en Inertia', function () {
    get('/admin/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Auth/Login')
        );
});

it('redirige vers le dashboard si déjà connecté en admin', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
        'password' => Hash::make('secret-pass'),
    ]);

    $this->actingAs($admin)
        ->get('/admin/login')
        ->assertRedirect('/admin/dashboard');
});

it('connecte un admin avec des identifiants valides et redirige vers le dashboard', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
        'email' => 'admin@lastuce.test',
        'password' => Hash::make('secret-pass'),
    ]);

    post('/admin/login', [
        'email' => 'admin@lastuce.test',
        'password' => 'secret-pass',
    ])->assertRedirect('/admin/dashboard');

    expect(auth()->id())->toBe($admin->id);
});

it('refuse un mot de passe invalide avec une erreur de validation', function () {
    User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
        'email' => 'admin@lastuce.test',
        'password' => Hash::make('correct-pass'),
    ]);

    post('/admin/login', [
        'email' => 'admin@lastuce.test',
        'password' => 'wrong-pass',
    ])
        ->assertStatus(302)
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('refuse un utilisateur non admin même avec un mot de passe correct', function () {
    User::factory()->create([
        'is_admin' => false,
        'email' => 'user@lastuce.test',
        'password' => Hash::make('any-pass'),
    ]);

    post('/admin/login', [
        'email' => 'user@lastuce.test',
        'password' => 'any-pass',
    ])
        ->assertStatus(302)
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('déconnecte et redirige vers /admin/login', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->post('/admin/logout')
        ->assertRedirect('/admin/login');

    expect(auth()->check())->toBeFalse();
});
