<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\Episode;
use App\Services\VideoEmbedService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Story S3.6 — Sitemap et flux RSS.
 *
 * Sitemap rendu en XML pur (pas de Blade) pour rester strict sur l'encodage
 * et éviter qu'un includes mal échappé ne casse la validation Google.
 */
class SyndicationController extends Controller
{
    public function __construct(private readonly VideoEmbedService $videoEmbedService)
    {
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $base = rtrim(config('app.url', ''), '/');
            $locales = array_keys(config('app.supported_locales', ['fr' => 'Français', 'en' => 'English']));

            $items = [];

            foreach ($locales as $locale) {
                foreach (['', 'episodes', 'astuces', 'blog', 'partenariats', 'contact'] as $path) {
                    $items[] = [
                        'loc' => $base . '/' . $locale . ($path ? '/' . $path : ''),
                        'lastmod' => now()->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => $path === '' ? '1.0' : '0.8',
                    ];
                }
            }

            Episode::published()
                ->orderBy('date_publication', 'desc')
                ->cursor()
                ->each(function ($episode) use (&$items, $base, $locales) {
                    foreach ($locales as $locale) {
                        $items[] = [
                            'loc' => "{$base}/{$locale}/episodes/{$episode->slug}",
                            'lastmod' => optional($episode->updated_at ?? $episode->date_publication)->toIso8601String(),
                            'changefreq' => 'monthly',
                            'priority' => '0.7',
                        ];
                    }
                });

            BlogArticle::publishedAndVisible()
                ->orderBy('date_publication', 'desc')
                ->cursor()
                ->each(function ($article) use (&$items, $base, $locales) {
                    foreach ($locales as $locale) {
                        $items[] = [
                            'loc' => "{$base}/{$locale}/blog/{$article->slug}",
                            'lastmod' => optional($article->updated_at ?? $article->date_publication)->toIso8601String(),
                            'changefreq' => 'monthly',
                            'priority' => '0.6',
                        ];
                    }
                });

            return $this->renderSitemap($items);
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function episodesRss(): Response
    {
        $xml = Cache::remember('rss.episodes.xml', 1800, function () {
            $episodes = Episode::published()->recent()->limit(20)->get();
            return $this->renderRss(
                title: "L'Astuce — Épisodes",
                description: "Les derniers épisodes de l'émission L'Astuce.",
                link: rtrim(config('app.url'), '/') . '/fr/episodes',
                items: $episodes->map(function ($e) {
                    $videoUrl = $e->facebook_url ?: $e->youtube_url;
                    return [
                        'title' => $e->titre,
                        'link' => rtrim(config('app.url'), '/') . "/fr/episodes/{$e->slug}",
                        'guid' => "episode-{$e->id}",
                        'description' => (string) ($e->description ?? ''),
                        'pubDate' => optional($e->date_publication)->toRfc822String() ?? now()->toRfc822String(),
                        'enclosure' => $this->videoEmbedService->thumbnail($videoUrl),
                    ];
                })->all(),
            );
        });

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    public function blogRss(): Response
    {
        $xml = Cache::remember('rss.blog.xml', 1800, function () {
            $articles = BlogArticle::publishedAndVisible()->recent()->limit(20)->get();
            return $this->renderRss(
                title: "L'Astuce — Blog",
                description: "Les derniers articles du blog L'Astuce.",
                link: rtrim(config('app.url'), '/') . '/fr/blog',
                items: $articles->map(fn ($a) => [
                    'title' => $a->titre,
                    'link' => rtrim(config('app.url'), '/') . "/fr/blog/{$a->slug}",
                    'guid' => "article-{$a->id}",
                    'description' => (string) ($a->extrait ?? ''),
                    'pubDate' => optional($a->date_publication)->toRfc822String() ?? now()->toRfc822String(),
                    'enclosure' => $a->image,
                ])->all(),
            );
        });

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    /**
     * @param array<int, array{loc:string,lastmod:?string,changefreq:string,priority:string}> $items
     */
    private function renderSitemap(array $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($items as $item) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $this->escape($item['loc']) . '</loc>' . "\n";
            if ($item['lastmod']) {
                $xml .= '    <lastmod>' . $this->escape($item['lastmod']) . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>' . $item['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $item['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";
        return $xml;
    }

    /**
     * @param array<int, array{title:string,link:string,guid:string,description:string,pubDate:string,enclosure:?string}> $items
     */
    private function renderRss(string $title, string $description, string $link, array $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . $this->escape($title) . '</title>' . "\n";
        $xml .= '    <link>' . $this->escape($link) . '</link>' . "\n";
        $xml .= '    <description>' . $this->escape($description) . '</description>' . "\n";
        $xml .= '    <language>fr-FR</language>' . "\n";
        $xml .= '    <lastBuildDate>' . now()->toRfc822String() . '</lastBuildDate>' . "\n";
        $xml .= '    <atom:link href="' . $this->escape($link) . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($items as $item) {
            $xml .= '    <item>' . "\n";
            $xml .= '      <title>' . $this->escape($item['title']) . '</title>' . "\n";
            $xml .= '      <link>' . $this->escape($item['link']) . '</link>' . "\n";
            $xml .= '      <guid isPermaLink="false">' . $this->escape($item['guid']) . '</guid>' . "\n";
            $xml .= '      <description>' . $this->escape($item['description']) . '</description>' . "\n";
            $xml .= '      <pubDate>' . $item['pubDate'] . '</pubDate>' . "\n";
            if ($item['enclosure']) {
                $xml .= '      <enclosure url="' . $this->escape($item['enclosure']) . '" type="image/jpeg" />' . "\n";
            }
            $xml .= '    </item>' . "\n";
        }
        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>' . "\n";
        return $xml;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
