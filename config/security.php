<?php

/**
 * Configuration des headers de sécurité (story S8.11).
 *
 * Le middleware App\Http\Middleware\SecurityHeaders lit cette config pour
 * appliquer CSP, HSTS, X-Frame-Options, X-Content-Type-Options et
 * Referrer-Policy à toutes les réponses HTML.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Mode CSP
    |--------------------------------------------------------------------------
    | 'enforce'      — header Content-Security-Policy (bloque les violations)
    | 'report-only'  — header Content-Security-Policy-Report-Only (logue mais
    |                  ne bloque pas, idéal pour valider la policy avant prod)
    | 'off'          — pas de CSP du tout
    |
    | Par défaut on est en report-only hors prod pour pouvoir valider la
    | policy sans casser l'embed Facebook ou YouTube si l'allowlist est
    | incomplète. En prod on bascule en enforce.
    */
    'csp_mode' => env(
        'SECURITY_CSP_MODE',
        env('APP_ENV') === 'production' ? 'enforce' : 'report-only',
    ),

    /*
    |--------------------------------------------------------------------------
    | Directives CSP
    |--------------------------------------------------------------------------
    | Dictionnaire directive => liste de sources. La valeur 'self' est
    | automatiquement enrobée en 'self'. Ajouter une nouvelle directive ici
    | revient à l'inclure telle quelle dans le header.
    |
    | frame-src autorise explicitement YouTube + Facebook pour permettre les
    | embeds vidéo (story S2.3, S3.4). Ajouter d'autres lecteurs ici si
    | nécessaire.
    */
    'csp_directives' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'object-src' => ["'none'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'style-src' => ["'self'", "'unsafe-inline'"],
        // 'unsafe-inline' nécessaire pour le bootstrap Inertia (script du
        // payload page) + les balises ld+json générées dans <Head>.
        // Ré-évaluer en v1.1 avec un système de nonce si on veut durcir.
        'script-src' => ["'self'", "'unsafe-inline'"],
        'connect-src' => ["'self'"],
        'frame-src' => [
            "'self'",
            'https://www.youtube.com',
            'https://www.youtube-nocookie.com',
            'https://www.facebook.com',
        ],
        'frame-ancestors' => ["'none'"],
        'form-action' => ["'self'"],
    ],

    /*
    |--------------------------------------------------------------------------
    | URI où les violations CSP sont reportées
    |--------------------------------------------------------------------------
    | Optionnel. Si défini, ajouté en tant que `report-uri` à la policy.
    */
    'csp_report_uri' => env('SECURITY_CSP_REPORT_URI'),

    /*
    |--------------------------------------------------------------------------
    | HSTS
    |--------------------------------------------------------------------------
    | Activé uniquement en prod (les navigateurs ignoreront en HTTP de toute
    | façon, mais on évite de pourrir le header en dev).
    */
    'hsts_enabled' => env('SECURITY_HSTS_ENABLED', env('APP_ENV') === 'production'),
    'hsts_max_age' => env('SECURITY_HSTS_MAX_AGE', 63072000), // 2 ans
    'hsts_include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
    'hsts_preload' => env('SECURITY_HSTS_PRELOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Autres headers fixes
    |--------------------------------------------------------------------------
    */
    'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),
    'content_type_options' => env('SECURITY_CONTENT_TYPE_OPTIONS', 'nosniff'),
    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    'permissions_policy' => env(
        'SECURITY_PERMISSIONS_POLICY',
        'geolocation=(), microphone=(), camera=()',
    ),
];
