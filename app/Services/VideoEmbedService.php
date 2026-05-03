<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Détecte un fournisseur vidéo (YouTube ou Facebook) depuis une URL et
 * expose ID, miniature et URL d'embed. Story S2.4.
 *
 * Le service est sans état : toutes les méthodes acceptent l'URL en entrée
 * et renvoient null pour toute URL non reconnue, ce qui permet aux callers
 * (modèle Episode, composant VideoPlayer côté front) de retomber sur un
 * placeholder sans essayer un parsing maison.
 */
class VideoEmbedService
{
    public const PROVIDER_YOUTUBE = 'youtube';
    public const PROVIDER_FACEBOOK = 'facebook';

    /**
     * Détecte le provider d'une URL vidéo. Renvoie null si non supporté.
     */
    public function detectProvider(?string $url): ?string
    {
        $url = $this->normalize($url);

        if ($url === null) {
            return null;
        }

        if ($this->isYoutubeHost($url)) {
            return self::PROVIDER_YOUTUBE;
        }

        if ($this->isFacebookHost($url)) {
            return self::PROVIDER_FACEBOOK;
        }

        return null;
    }

    /**
     * Extrait l'identifiant canonique de la vidéo (vidéo YouTube : 11 chars ;
     * Facebook : ID numérique ou hash fb.watch). Renvoie null si introuvable.
     */
    public function extractId(?string $url): ?string
    {
        $provider = $this->detectProvider($url);

        if ($provider === null) {
            return null;
        }

        $url = $this->normalize($url);

        return match ($provider) {
            self::PROVIDER_YOUTUBE => $this->extractYoutubeId($url),
            self::PROVIDER_FACEBOOK => $this->extractFacebookId($url),
            default => null,
        };
    }

    /**
     * URL d'une miniature exploitable. YouTube → `hqdefault`. Facebook →
     * scraping de la balise og:image (cachée 6h car les URLs CDN FB
     * expirent en ~24h).
     */
    public function thumbnail(?string $url): ?string
    {
        $provider = $this->detectProvider($url);
        $id = $this->extractId($url);

        if ($provider === null || $id === null) {
            return null;
        }

        if ($provider === self::PROVIDER_YOUTUBE) {
            return "https://i.ytimg.com/vi/{$id}/hqdefault.jpg";
        }

        return $this->fetchFacebookOgImage($this->normalize($url));
    }

    private function fetchFacebookOgImage(string $url): ?string
    {
        return Cache::remember('video.fb_thumb.'.md5($url), 21600, function () use ($url) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LastuceBot/1.0)',
                ])->timeout(5)->get($url);
            } catch (\Throwable $e) {
                return null;
            }

            if (!$response->successful()) {
                return null;
            }

            if (preg_match('#<meta\s+property="og:image"\s+content="([^"]+)"#i', $response->body(), $m)) {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            return null;
        });
    }

    /**
     * URL d'embed prête à être placée dans un iframe. Retourne null si
     * l'URL d'origine n'est pas supportée.
     */
    public function embedUrl(?string $url): ?string
    {
        $provider = $this->detectProvider($url);
        $id = $this->extractId($url);

        if ($provider === null || $id === null) {
            return null;
        }

        if ($provider === self::PROVIDER_YOUTUBE) {
            return "https://www.youtube-nocookie.com/embed/{$id}";
        }

        $sourceUrl = $this->facebookSourceUrl($url, $id);

        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($sourceUrl) . '&show_text=false';
    }

    private function normalize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }

    private function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        return strtolower(preg_replace('/^www\./i', '', $host));
    }

    private function isYoutubeHost(string $url): bool
    {
        $host = $this->host($url);

        return in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], true);
    }

    private function isFacebookHost(string $url): bool
    {
        $host = $this->host($url);

        return in_array($host, ['facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.watch'], true);
    }

    private function extractYoutubeId(string $url): ?string
    {
        $host = $this->host($url) ?? '';
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($host === 'youtu.be') {
            $candidate = ltrim($path, '/');
            return $this->validYoutubeId($candidate);
        }

        if (str_starts_with($path, '/embed/') || str_starts_with($path, '/shorts/') || str_starts_with($path, '/live/')) {
            $segments = explode('/', trim($path, '/'));
            return $this->validYoutubeId($segments[1] ?? null);
        }

        $query = parse_url($url, PHP_URL_QUERY);

        if (is_string($query) && $query !== '') {
            parse_str($query, $params);
            return $this->validYoutubeId($params['v'] ?? null);
        }

        return null;
    }

    private function validYoutubeId(?string $candidate): ?string
    {
        if (!is_string($candidate) || $candidate === '') {
            return null;
        }

        $candidate = explode('?', $candidate)[0];
        $candidate = explode('&', $candidate)[0];

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1 ? $candidate : null;
    }

    private function extractFacebookId(string $url): ?string
    {
        $host = $this->host($url) ?? '';
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($host === 'fb.watch') {
            $candidate = trim($path, '/');
            return $candidate !== '' ? $candidate : null;
        }

        // .../{page}/videos/{id} ou .../videos/{id}
        if (preg_match('#/videos/(\d+)#', $path, $matches)) {
            return $matches[1];
        }

        // .../share/v/{hash} ou .../share/r/{hash} (URL de partage moderne)
        if (preg_match('#/share/[vr]/([A-Za-z0-9_-]+)#', $path, $matches)) {
            return $matches[1];
        }

        // .../watch/?v=123
        if (str_contains($path, '/watch')) {
            $query = parse_url($url, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                parse_str($query, $params);
                $id = $params['v'] ?? null;
                if (is_string($id) && preg_match('/^\d+$/', $id) === 1) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function facebookSourceUrl(string $url, string $id): string
    {
        $host = $this->host($url);
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($host === 'fb.watch') {
            return "https://fb.watch/{$id}";
        }

        // Les URLs /share/v/{hash} n'ont pas de forme canonique avec ID numérique.
        // Le plugin video.php de Facebook accepte directement l'URL de partage.
        if (preg_match('#/share/[vr]/#', $path)) {
            return $url;
        }

        return "https://www.facebook.com/watch/?v={$id}";
    }
}
