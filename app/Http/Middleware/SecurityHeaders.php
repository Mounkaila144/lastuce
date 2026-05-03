<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers de sécurité globaux (story S8.11).
 *
 * Configuré via config/security.php. Pour valider une nouvelle policy CSP,
 * démarrer en mode 'report-only' puis basculer en 'enforce' une fois les
 * rapports stables. Les routes RSS / sitemap ne sont pas concernées par
 * la CSP (réponses XML), mais reçoivent quand même les headers fixes.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        // CSP — appliqué uniquement aux réponses HTML pour ne pas pourrir
        // les XML/JSON (Inertia renvoie aussi du JSON sur les visites
        // partielles, mais on garde la policy pour cohérence des rapports).
        $cspMode = (string) config('security.csp_mode', 'off');
        if ($cspMode !== 'off' && $this->shouldApplyCsp($response)) {
            $cspHeader = $cspMode === 'report-only'
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $headers->set($cspHeader, $this->buildCspValue());
        }

        // Headers fixes appliqués partout (sauf si déjà posés en amont).
        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', (string) config('security.frame_options', 'DENY'));
        }
        if (! $headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', (string) config('security.content_type_options', 'nosniff'));
        }
        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', (string) config('security.referrer_policy', 'strict-origin-when-cross-origin'));
        }
        $permissionsPolicy = (string) config('security.permissions_policy', '');
        if ($permissionsPolicy !== '' && ! $headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', $permissionsPolicy);
        }

        // HSTS — seulement sur HTTPS, sinon le header est inutile et
        // certains scanners s'en plaignent.
        if (config('security.hsts_enabled') && $request->isSecure()) {
            $value = 'max-age=' . (int) config('security.hsts_max_age', 63072000);
            if (config('security.hsts_include_subdomains')) {
                $value .= '; includeSubDomains';
            }
            if (config('security.hsts_preload')) {
                $value .= '; preload';
            }
            $headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }

    private function shouldApplyCsp(Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        // Inertia visites partielles → application/json. On laisse passer
        // pour que le navigateur applique la même policy à la page parente
        // (la dernière directive CSP émise gagne, mais ça reste cohérent).
        return str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/json');
    }

    private function buildCspValue(): string
    {
        $directives = (array) config('security.csp_directives', []);
        $parts = [];

        foreach ($directives as $directive => $sources) {
            $sources = array_filter(array_map('trim', (array) $sources));
            if (empty($sources)) {
                continue;
            }
            $parts[] = $directive . ' ' . implode(' ', $sources);
        }

        $reportUri = (string) config('security.csp_report_uri', '');
        if ($reportUri !== '') {
            $parts[] = 'report-uri ' . $reportUri;
        }

        return implode('; ', $parts);
    }
}
