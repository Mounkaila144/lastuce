<?php

namespace App\Http\Middleware;

use App\Models\AstucesSoumise;
use App\Models\NewsletterAbonne;
use App\Models\Partenariat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = App::getLocale();

        return [
            ...parent::share($request),
            'appName' => config('app.name', "L'Astuce"),
            'locale' => $locale,
            'availableLocales' => ['fr', 'en'],
            'translations' => fn () => $this->loadTranslations($locale),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            // Partagé uniquement quand on visite l'espace admin pour éviter
            // la requête SQL inutile sur les pages publiques.
            'admin' => fn () => $this->adminContext($request),
        ];
    }

    /**
     * Contexte injecté dans le AdminLayout (user courant + compteurs de
     * modération). Renvoie null hors espace admin.
     */
    protected function adminContext(Request $request): ?array
    {
        if (! str_starts_with($request->path(), 'admin')) {
            return null;
        }

        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return null;
        }

        $pending = Cache::remember('admin.pending_counts', 60, function () {
            return [
                'astuces' => AstucesSoumise::where('status', 'en_attente')->count(),
                // Partenariat : statuts nouveau / en_cours / accepte / refuse.
                'partenariats' => Partenariat::where('status', 'nouveau')->count(),
                'newsletter' => NewsletterAbonne::where('status', 'en_attente')->count(),
            ];
        });

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? null,
            ],
            'pending_counts' => $pending,
        ];
    }

    /**
     * Load all PHP translation files for the current locale and return them
     * as a nested array indexed by file name (without extension).
     *
     * Result is cached per-request to avoid repeated disk reads when multiple
     * Inertia partial reloads occur in the same request lifecycle.
     *
     * @return array<string, mixed>
     */
    protected function loadTranslations(string $locale): array
    {
        static $cache = [];

        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $path = lang_path($locale);
        $translations = [];

        if (! is_dir($path)) {
            return $cache[$locale] = $translations;
        }

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $key = $file->getFilenameWithoutExtension();
            $contents = require $file->getPathname();

            if (is_array($contents)) {
                $translations[$key] = $contents;
            }
        }

        return $cache[$locale] = $translations;
    }
}
