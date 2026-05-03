<?php

use App\Services\VideoEmbedService;

beforeEach(function () {
    $this->service = new VideoEmbedService();
});

describe('detectProvider', function () {
    it('renvoie null pour des entrées vides ou non supportées', function () {
        expect($this->service->detectProvider(null))->toBeNull()
            ->and($this->service->detectProvider(''))->toBeNull()
            ->and($this->service->detectProvider('   '))->toBeNull()
            ->and($this->service->detectProvider('https://vimeo.com/123456'))->toBeNull()
            ->and($this->service->detectProvider('not a url'))->toBeNull();
    });

    it('détecte les variantes YouTube', function (string $url) {
        expect($this->service->detectProvider($url))->toBe(VideoEmbedService::PROVIDER_YOUTUBE);
    })->with([
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'http://youtube.com/watch?v=dQw4w9WgXcQ',
        'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtu.be/dQw4w9WgXcQ',
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        'youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    it('détecte les variantes Facebook', function (string $url) {
        expect($this->service->detectProvider($url))->toBe(VideoEmbedService::PROVIDER_FACEBOOK);
    })->with([
        'https://www.facebook.com/lastuce/videos/123456789012345',
        'https://m.facebook.com/lastuce/videos/123456789012345',
        'https://web.facebook.com/watch/?v=123456789012345',
        'https://fb.watch/abcDEF123/',
    ]);
});

describe('extractId', function () {
    it('extrait un ID YouTube valide quel que soit le format', function (string $url) {
        expect($this->service->extractId($url))->toBe('dQw4w9WgXcQ');
    })->with([
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtu.be',
        'https://youtu.be/dQw4w9WgXcQ',
        'https://youtu.be/dQw4w9WgXcQ?t=42',
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        'https://www.youtube.com/live/dQw4w9WgXcQ',
    ]);

    it('rejette les ID YouTube de longueur incorrecte', function () {
        expect($this->service->extractId('https://youtu.be/short'))->toBeNull()
            ->and($this->service->extractId('https://www.youtube.com/watch?v=tooooolong123'))->toBeNull()
            ->and($this->service->extractId('https://www.youtube.com/watch'))->toBeNull();
    });

    it('extrait un ID Facebook depuis /videos/{id}', function () {
        expect($this->service->extractId('https://www.facebook.com/lastuce/videos/987654321012345'))
            ->toBe('987654321012345');
    });

    it('extrait un ID Facebook depuis /watch/?v=', function () {
        expect($this->service->extractId('https://www.facebook.com/watch/?v=987654321012345'))
            ->toBe('987654321012345');
    });

    it('extrait le hash Facebook fb.watch', function () {
        expect($this->service->extractId('https://fb.watch/abcDEF123/'))
            ->toBe('abcDEF123');
    });

    it('renvoie null si Facebook ne contient pas de pattern reconnu', function () {
        expect($this->service->extractId('https://www.facebook.com/lastuce/'))->toBeNull()
            ->and($this->service->extractId('https://www.facebook.com/watch/'))->toBeNull();
    });
});

describe('thumbnail', function () {
    it('renvoie une URL hqdefault pour YouTube', function () {
        expect($this->service->thumbnail('https://youtu.be/dQw4w9WgXcQ'))
            ->toBe('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
    });

    it('renvoie null pour Facebook (pas de miniature publique sans token)', function () {
        expect($this->service->thumbnail('https://www.facebook.com/lastuce/videos/123456789012345'))
            ->toBeNull();
    });

    it('renvoie null pour les URLs non supportées', function () {
        expect($this->service->thumbnail('https://vimeo.com/1'))->toBeNull()
            ->and($this->service->thumbnail(null))->toBeNull();
    });
});

describe('embedUrl', function () {
    it('génère un embed YouTube nocookie', function () {
        expect($this->service->embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
            ->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    });

    it('génère un embed Facebook avec source watch', function () {
        $embed = $this->service->embedUrl('https://www.facebook.com/lastuce/videos/123456789012345');

        expect($embed)
            ->toStartWith('https://www.facebook.com/plugins/video.php?href=')
            ->toContain(rawurlencode('https://www.facebook.com/watch/?v=123456789012345'))
            ->toContain('show_text=false');
    });

    it('génère un embed Facebook fb.watch', function () {
        $embed = $this->service->embedUrl('https://fb.watch/abcDEF123/');

        expect($embed)
            ->toStartWith('https://www.facebook.com/plugins/video.php?href=')
            ->toContain(rawurlencode('https://fb.watch/abcDEF123'));
    });

    it('renvoie null pour les URLs non supportées', function () {
        expect($this->service->embedUrl('https://vimeo.com/1'))->toBeNull()
            ->and($this->service->embedUrl(null))->toBeNull();
    });
});
