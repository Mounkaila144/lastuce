<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('applique les headers fixes sur toutes les réponses HTML', function () {
    Config::set('security.csp_mode', 'enforce');

    $response = get('/fr');

    $response->assertOk();
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->get('Permissions-Policy'))->toContain('geolocation=()');
});

it('applique Content-Security-Policy en mode enforce', function () {
    Config::set('security.csp_mode', 'enforce');

    $response = get('/fr');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toBeNull()
        ->and($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain('https://www.youtube.com')
        ->and($csp)->toContain('https://www.facebook.com')
        ->and($csp)->toContain("frame-ancestors 'none'");

    expect($response->headers->get('Content-Security-Policy-Report-Only'))->toBeNull();
});

it('applique Content-Security-Policy-Report-Only en mode report-only', function () {
    Config::set('security.csp_mode', 'report-only');

    $response = get('/fr');

    expect($response->headers->get('Content-Security-Policy-Report-Only'))->not->toBeNull();
    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
});

it('omet la CSP quand le mode est off mais garde les headers fixes', function () {
    Config::set('security.csp_mode', 'off');

    $response = get('/fr');

    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
    expect($response->headers->get('Content-Security-Policy-Report-Only'))->toBeNull();
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

it('n\'émet pas HSTS en HTTP', function () {
    Config::set('security.hsts_enabled', true);

    $response = get('/fr');

    // En tests, requêtes en HTTP → header non émis (sécurité Symfony).
    expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
});

it('inclut report-uri dans la policy quand SECURITY_CSP_REPORT_URI est défini', function () {
    Config::set('security.csp_mode', 'enforce');
    Config::set('security.csp_report_uri', 'https://csp.lastuce.test/report');

    $response = get('/fr');
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain('report-uri https://csp.lastuce.test/report');
});
