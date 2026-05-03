# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

L'Astuce — site officiel de l'émission Facebook *LightOn* (Niamey). Backend Laravel 10 + frontend Vue 3 + TypeScript via Inertia.js. The codebase is mid-migration from a legacy Blade/jQuery stack to the Inertia stack — you will see both layers coexist; new public pages should be Inertia. See `docs/03-architecture.md` for the full architecture rationale (ADRs, schema evolutions, phasing).

## Common commands

PHP / Laravel:
```bash
composer install
php artisan migrate                       # MySQL in dev (lastuce DB), SQLite :memory: in tests
php artisan key:generate
vendor/bin/pest                           # full test suite (Pest is the runner — phpunit.xml is config only)
vendor/bin/pest tests/Feature/Episodes    # one directory
vendor/bin/pest --filter='it loads home'  # one test by description
vendor/bin/pint                           # PHP formatter
```

Frontend (Vite + Vue):
```bash
npm install
npm run dev                  # Vite dev server with HMR
npm run build                # type-check (vue-tsc) THEN production build — fails on TS errors
npm run build:fast           # build without type-check (use sparingly)
npm run type-check           # vue-tsc --noEmit only
npm test                     # Vitest run (jsdom, files: resources/js/**/*.{test,spec}.{ts,js})
npm run test:watch
npx vitest run path/to/file.test.ts   # single test file
```

CI (`.github/workflows/ci.yml`) runs `vendor/bin/pest`, `npm run type-check`, and `npm test` on PRs to `main`. Both must pass.

## Architecture — what requires reading multiple files to grasp

### Inertia is the seam, not an API

There is no public REST API. `routes/web.php` returns `Inertia::render('PageName', $props)`; `resources/js/Pages/PageName.vue` consumes those props as typed `defineProps<{...}>()`. `routes/api.php` is reserved for tiny internal endpoints (search suggestions, admin notifications) — do not add public JSON endpoints there.

When adding a page: create the Vue component under `resources/js/Pages/`, return `Inertia::render` from a controller, type the props in the Vue file. Server-side validation via FormRequests; Inertia surfaces errors back to the form automatically.

### Locale-prefixed routing

All public routes live under `/{locale}` where `locale` is `[a-zA-Z]{2}` (`fr` default, `en` available). The `setlocale` middleware (`App\Http\Middleware\SetLocale`) reads the prefix; `/` redirects to `/{session-or-default-locale}`. When generating links in Vue, read the current locale from `useUiStore().locale` (hydrated from Inertia shared props) — see `resources/js/Pages/Home.vue` for the canonical `:href="`/${ui.locale}/episodes`"` pattern. Admin routes (`/admin/*`) and syndication routes (`/sitemap.xml`, `/episodes/rss`, `/blog/rss`) are NOT locale-prefixed.

### Shared Inertia props

`App\Http\Middleware\HandleInertiaRequests::share()` injects `locale`, `availableLocales`, `translations` (lazy: full Laravel `lang/{locale}/*.php` files merged into one nested array), `flash`, and `appName` on every request. Frontend `resources/js/i18n/index.ts` merges those Laravel translations under the `laravel.*` namespace inside vue-i18n, so:
- `$t('nav.home')` → `resources/js/i18n/{fr,en}.json` (frontend strings)
- `$t('laravel.app.welcome')` → `resources/lang/{fr,en}/app.php` (backend strings)

Don't duplicate translations across the two — pick the side that owns the string.

### State

Pinia, intentionally minimal. Currently only `useUiStore` (`resources/js/stores/ui.ts`). Keep stores small and feature-specific; do not centralize all server data in stores — Inertia props are the source of truth for page data.

### Layouts

Default layout is auto-injected in `resources/js/app.ts`: any Page component without an explicit `layout` property gets wrapped in `DefaultLayout.vue`. To opt out, set `layout: null` on the page.

### Two JS entrypoints (legacy + new)

`vite.config.js` builds both `resources/js/app.js` (legacy vanilla JS — YouTubePlayer, FormHandler, Navigation, etc., used by remaining Blade pages) and `resources/js/app.ts` (Inertia entrypoint). New work goes in `app.ts` / `Pages` / `components/{ui,domain}`. Do not import legacy files from Vue components.

### Admin back-office

Admin uses Blade views (`resources/views/admin/`) + custom middleware stack: `admin` (auth + role check, `App\Http\Middleware\AdminMiddleware`) and `log.admin.actions` (audit log to `admin_logs` table). Per-route fine-grained authorization via `can:admin:<resource>.<action>` (e.g. `can:admin:episodes.manage`). The `User` model has `is_admin`, `role`, `permissions` (JSON), 2FA fields, and account lockout. See `ADMIN_IMPLEMENTATION_SUMMARY.md` for the full surface.

Per the architecture doc, the admin back-office is slated to migrate to Inertia (Phase 2). Until then, do not assume admin routes return Inertia responses.

### Security middleware aliases

`security:<context>` (e.g. `security:login`, `security:upload`, `security:contact`, `security:newsletter`) maps to `App\Http\Middleware\SecurityMiddleware` and applies rate limiting + IP-based blocking per context. The newsletter throttle is intentionally enforced inside the FormRequest, not the middleware, so the Inertia flow doesn't get a JSON 429 — keep that pattern when adding new public forms.

### Media

Episodes (and any model with images) use **Spatie Media Library** (`HasMedia`). Conversions are declared on the model: `thumb` 320×180, `card` 640×360, `hero` 1280×720, all WebP, `nonQueued` (synchronous on upload). Storage disk is `media` (env `MEDIA_DISK`, default `local` in dev → `s3` planned in prod). Tests force `MEDIA_DISK=media` / `MEDIA_DISK_DRIVER=local` via `phpunit.xml`.

### Video embeds

`App\Services\VideoEmbedService` is the single source of truth for YouTube/Facebook URL parsing → `(provider, id, embed_url, thumbnail_url)`. Episodes can have `youtube_url` and/or `facebook_url`; at least one must be set (enforced in the FormRequest). The `<VideoPlayer>` Vue component picks the iframe based on `provider` and falls back to a thumbnail + external link if the embed is blocked.

### Syndication

`/sitemap.xml`, `/episodes/rss`, `/blog/rss` are served by `SyndicationController` (single entrypoint, not locale-prefixed). The legacy `/rss.xml` is a 301 redirect — keep it.

## Conventions

- **Vue components**: PascalCase, `<script setup lang="ts">`. Page components in `resources/js/Pages/`; reusable primitives in `components/ui/`; feature-bound widgets in `components/domain/`.
- **TypeScript types**: shared types in `resources/js/types/` (e.g. `inertia.ts` extends `@inertiajs/core` `PageProps`, `domain.ts` for Episode/Astuce/etc.).
- **Tailwind 4**: design tokens (`brand.*`, `surface.*`, `accent.*`) defined in `resources/css/app.css`. Compose utilities; reach for `@apply` only on truly repeated primitives. v4 uses the new oxide engine — do not add a `tailwind.config.js`, configuration is CSS-side.
- **Tests**: Pest for PHP. Feature tests should assert Inertia component name + key props (use `Inertia::assertComponent(...)` / `assertHas(...)`). Unit tests for Services. Vitest for composables and critical components (`<VideoPlayer>`, `<NewsletterForm>`).
- **Migrations**: this is a brownfield schema. Multiple `fix_*_table_columns` migrations exist patching prior mistakes; check `database/migrations/` for the latest column shape before assuming the create-migration is authoritative. The `2026_05_03_*` batch added Facebook URL, categories, tags, double-opt-in newsletter, and partnership form fields.

## Project docs

`docs/01-project-brief.md` (product context), `docs/02-prd.md` (requirements), `docs/03-architecture.md` (this stack's reasoning, ADRs, schema migration plan), `docs/04-stories.md` (work breakdown — story IDs like S3.5, S4.1 are referenced in route comments and commit messages). When a route comment says "Story S3.6", that doc is the spec.

`MODELS_DOCUMENTATION.md`, `ADMIN_IMPLEMENTATION_SUMMARY.md`, `LOCALIZATION_GUIDE.md`, `JAVASCRIPT_GUIDE.md` exist but predate the Inertia migration — treat them as reference for the legacy Blade/JS layer, not as guidance for new code.
