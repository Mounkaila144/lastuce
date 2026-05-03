# Architecture — L'Astuce

**Version** : 1.0  
**Date** : 2026-05-02  
**Statut** : Stack confirmée le 2026-05-02 — **Vue 3 + TypeScript + Inertia.js + Vite + Tailwind 4**  
**Documents parents** : [01-project-brief.md](./01-project-brief.md), [02-prd.md](./02-prd.md)

---

## 1. Décision stack — confirmée

**Décision (2026-05-02)** : la refonte utilise **Vue 3 + TypeScript + Inertia.js + Vite + Tailwind 4** sur le backend Laravel 10 existant. Pas de SPA séparée, pas d'API publique à maintenir.

### 1.1 Stack finale

```
┌──────────────────────────────────────────────────────────┐
│                   NAVIGATEUR                             │
│  Vue 3 components (.vue + <script setup lang="ts">)      │
│  Tailwind CSS 4 — design tokens custom                   │
│  Pinia (state management léger)                          │
│  GSAP (animations existantes conservées)                 │
│  Swiper (carrousels existants conservés)                 │
└──────────────┬───────────────────────────────────────────┘
               │  Inertia HTTP (props JSON, pas d'API REST)
               ▼
┌──────────────────────────────────────────────────────────┐
│                   LARAVEL 10 (PHP 8.2)                   │
│                                                          │
│  Inertia adapter ─ retourne Inertia::render('Page', …)   │
│  Controllers (existants, légèrement adaptés)             │
│  Models Eloquent (tels quels)                            │
│  Spatie Media Library (à activer)                        │
│  Sanctum (auth admin, conservé)                          │
│  Queues (Redis — pour emails newsletter, jobs lourds)    │
└──────────────┬───────────────────────────────────────────┘
               │
       ┌───────┼─────────┐
       ▼       ▼         ▼
   ┌──────┐ ┌──────┐ ┌────────────┐
   │MySQL │ │Redis │ │ S3 / disk  │
   │  8   │ │cache │ │ (médias)   │
   └──────┘ └──────┘ └────────────┘
```

## 2. Couches applicatives

### 2.1 Présentation (front)

- **Vue 3** avec `<script setup lang="ts">`.
- **Inertia.js** : le serveur pousse des props JSON typées, le client rend un composant Vue. Pas d'API REST publique à maintenir.
- **Pinia** : 2 stores seulement en v1 — `useUiStore` (langue, modale ouverte, menu mobile) et `useNewsletterStore` (état du widget d'inscription).
- **Tailwind CSS 4** : repartir d'une configuration neuve avec design tokens (`colors.brand.*`, `colors.surface.*`, typographie, spacings).
- **Bibliothèque de composants** : on construit nous-mêmes (pas de UI kit lourd type Element Plus). Quelques primitives Radix-vue ou Headless UI Vue pour modales/dropdowns/tabs accessibles.
- **Animations** : on garde GSAP pour les transitions fines (déjà installé, déjà maîtrisé) ; Swiper pour les carrousels.
- **i18n** : `vue-i18n` pour les chaînes côté client + Inertia pousse aussi les traductions du backend Laravel à chaque page.

### 2.2 Application (Laravel)

- **Routing** : `routes/web.php` rendra des réponses Inertia. `routes/api.php` reste minimal (uniquement endpoints internes type recherche live, notifications admin).
- **Controllers** : on conserve la structure actuelle, on remplace `return view(...)` par `return Inertia::render(...)` avec props sérialisables.
- **Form Requests** : centralisent la validation pour chaque formulaire.
- **Policies & Gates** : un Policy par modèle admin (EpisodePolicy, AstucesSoumisePolicy, etc.).
- **Services** : extraire la logique non-CRUD dans `app/Services` (ex : `VideoEmbedService` pour parser URL FB/YT, `NewsletterTokenService`).
- **Events & Listeners** : activer ceux déjà esquissés (`NewAstuceSubmitted`, `AstuceStatusChanged`, `NewPartnershipRequested`) → branchés sur des Listeners qui dispatchent emails et notifications admin via la queue.
- **Jobs (queue)** : envoi d'emails, génération de variantes d'images Spatie, export CSV.

### 2.3 Données

#### 2.3.1 Évolutions de schéma à prévoir

| Action | Table | Détail |
|---|---|---|
| Ajouter colonne | `episodes` | `facebook_url` (string nullable), `invite_nom` (string nullable), `invite_bio` (text nullable), `transcript` (longtext nullable) |
| Migration | `episodes` | rendre `youtube_url` et `facebook_url` non simultanément null (au moins une) — contrainte applicative dans le Form Request |
| Créer table | `categories` | id, slug, nom_fr, nom_en, description_fr, description_en, icone, ordre, timestamps |
| Créer table | `tags` | id, slug, nom, timestamps |
| Créer table pivot | `episode_tag` | episode_id, tag_id |
| Modifier | `episodes` | `category_id` FK nullable + retirer le tags array (migration douce) |
| Activer | tous modèles avec médias | `HasMedia` Spatie + collections `thumbnail`, `gallery` |

#### 2.3.2 Schéma logique simplifié

```
categories ─┐
            │ 1
            │
            *
       episodes ─── episode_tag ─── tags
            │
            │ 1
            *
   astuces_soumises (ref optionnelle à un episode où elle sera diffusée)

users (admin/moderator) 1 ─── * admin_logs
                       1 ─── * admin_notifications

newsletter_abonnes (indépendant)
partenariats        (indépendant)
blog_articles (catégorisé via categories)
```

### 2.4 Sécurité

- **Auth admin** : sessions web Laravel + middleware `admin` + middleware `log.admin.actions` (tous deux existants).
- **2FA** : structure déjà en place dans `users` ; activer via package `pragmarx/google2fa-laravel`.
- **Rate limiting** : conserver les limiteurs existants (`security:login`, `contact`, `newsletter`, `upload`).
- **Headers** : ajouter middleware global `SecurityHeaders` posant CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.
- **CSRF** : géré par Inertia automatiquement.
- **Uploads** : MIME sniffing serveur + scan extension + Spatie media library pour stockage isolé.
- **Logs** : `AdminLog` existant ; ajouter monitoring externe (Sentry recommandé).

## 3. Intégration Facebook & YouTube

### 3.1 Service d'embed

Créer `app/Services/VideoEmbedService.php` :

```php
class VideoEmbedService
{
    public function detectProvider(string $url): ?string  // 'youtube'|'facebook'
    public function extractId(string $url): ?string
    public function thumbnail(string $url): ?string
    public function embedUrl(string $url): ?string
}
```

- **YouTube** : regex existante (Episode.php), retourne `https://www.youtube.com/embed/{id}?rel=0`.
- **Facebook** : regex sur `facebook.com/{page}/videos/{id}` ou `fb.watch/{id}`, retourne `https://www.facebook.com/plugins/video.php?href={urlencodé}&show_text=false`.

### 3.2 Composant Vue `<VideoPlayer>`

Reçoit `:url` en prop, choisit l'iframe approprié, gère le fallback (miniature + bouton « Voir sur Facebook/YouTube ») si l'embed est bloqué (détection via `onerror`).

### 3.3 Hors scope v1 mais préparé pour v2

- `app/Services/Importers/FacebookImporter.php` (Graph API, page access token long-lived).
- `app/Services/Importers/YoutubeImporter.php` (YouTube Data API v3, channel ID).
- Commande `php artisan import:facebook --since=YYYY-MM-DD` qui crée des Episodes en statut `draft`.

## 4. Organisation du code

### 4.1 Arborescence ciblée

```
app/
  Console/Commands/         (existants conservés + import:* en v2)
  Events/
  Http/
    Controllers/            (existants, return Inertia::render)
    Middleware/
    Requests/               (Form Requests)
  Jobs/
  Listeners/
  Models/                   (existants + Categorie, Tag)
  Policies/
  Services/
    VideoEmbedService.php
    NewsletterTokenService.php
    SearchService.php
    Importers/              (v2)
resources/
  js/
    app.ts                  (entry Inertia)
    Pages/                  (1 fichier .vue par route Inertia)
      Home.vue
      Episodes/
        Index.vue
        Show.vue
      Astuces/
        Index.vue
        Create.vue
        Show.vue
      Partenariats/
      Newsletter/
      Blog/
      Admin/                (mêmes pages côté admin)
    Components/
      ui/                   (Button, Modal, Tabs, Dropdown, …)
      domain/               (EpisodeCard, AstuceCard, VideoPlayer, …)
    composables/            (useDebounce, useMediaQuery, …)
    stores/                 (Pinia)
    i18n/
    types/                  (TypeScript : Episode, Astuce, …)
  css/
    app.css                 (Tailwind directives + tokens)
  views/
    app.blade.php           (layout root Inertia minimal)
docs/                       (ce document et les autres)
tests/
  Feature/                  (HTTP + Inertia)
  Unit/                     (Services, Helpers)
```

### 4.2 Conventions

- **Composants Vue** : PascalCase, suffixés par leur rôle (`EpisodeCard.vue`, `NewsletterForm.vue`).
- **TypeScript** : types métier dans `resources/js/types/`, partagés avec Inertia via `@inertiajs/vue3` typings.
- **Tailwind** : préférer composer des classes utilitaires ; extraire en `@apply` seulement pour les patterns vraiment répétés (boutons primitifs).
- **Tests** : Pest (cohérent avec écosystème Laravel actuel) — Feature tests pour chaque page Inertia (vérifier props), Unit tests pour les Services.

## 5. Performance

| Mesure | Mécanisme |
|---|---|
| Lazy-load images | `loading="lazy"` natif + `vanilla-lazyload` pour iframes vidéo |
| Lazy-load routes | Inertia + dynamic `import()` par page Vue |
| Cache HTTP | header `Cache-Control: public, max-age=3600` sur pages anonymes |
| Cache applicatif | Redis pour stats dashboard, agrégats `getStatistiques()` |
| CDN assets | Vite build + servir `/build/*` via CDN (Bunny.net ou Cloudflare) |
| Compression | Brotli + Gzip côté serveur web (Caddy/Nginx) |
| Images responsives | Spatie conversions : `thumb` (320), `card` (640), `hero` (1280) |
| DB | index composites (`type, date_publication`, `status, date_inscription`), pas de N+1 (Eloquent eager loading) |

## 6. Déploiement & infra

### 6.1 Cible recommandée v1

- **Hébergement** : VPS unique (Hetzner / OVH / Infomaniak) — Caddy + PHP-FPM 8.2 + MySQL 8 + Redis. Suffisant jusqu'à ~50k visites/mois.
- **CI/CD** : GitHub Actions — sur push `main` : tests Pest + build Vite + SSH deploy via `deployer/deployer` ou `laravel-zero/envoy`.
- **Médias** : démarrer en disque local, prévoir migration S3 (Backblaze B2 économique) quand volume > 5 Go.
- **Backups** : `spatie/laravel-backup` quotidien, stockage S3 distant, rétention 30 jours.
- **Monitoring** : Sentry (free tier) + uptime Robot.

### 6.2 Environnements

| Env | URL | Branche | Données |
|---|---|---|---|
| local | `lastuce.test` (Laragon) | `main` ou feature | seeders dev |
| staging | `staging.lastuce.tld` | `main` | snapshot anonymisé |
| production | `lastuce.tld` | tag `vX.Y.Z` | réelle |

## 7. Tests & qualité

- **Pest** côté PHP : Feature tests sur chaque page publique (status 200, props clés présentes), tests des Form Requests, tests unitaires des Services.
- **Vitest** côté Vue : tests des composables et des composants critiques (`<VideoPlayer>`, `<NewsletterForm>`).
- **Playwright** (optionnel v1, recommandé) : 1 parcours bout-en-bout par flow critique (publier épisode admin, soumettre astuce, s'inscrire newsletter, désabonnement).
- **PHP-CS-Fixer** + **Pint** côté PHP, **ESLint + Prettier** côté Vue/TS.
- **Lighthouse CI** sur staging à chaque PR : seuil 85 perf / 95 a11y / 100 SEO.

## 8. Migration depuis l'existant

L'existant Laravel est conservé. Le travail de bascule en 4 phases :

1. **Phase 0 — Préparation** (1 semaine)
   - Installer Inertia + Vue 3 + TypeScript + Tailwind 4 en parallèle (sans casser le Blade existant).
   - Définir les design tokens, créer les composants UI primitifs.

2. **Phase 1 — Bascule du public** (2 semaines)
   - Migrer chaque route publique vers Inertia (1 par 1, avec test Feature).
   - Supprimer les vues Blade publiques au fur et à mesure.

3. **Phase 2 — Bascule de l'admin** (2 semaines)
   - Migrer le back-office vers Inertia.
   - Conserver les rate limiters et middlewares de sécurité.

4. **Phase 3 — Stabilisation** (1 semaine)
   - Tests de bout en bout, audit perf/a11y/SEO, doc utilisateur admin.

## 9. Décisions architecturales (ADR-style)

### ADR-01 : Inertia.js plutôt que SPA + API REST
**Statut** : proposé.  
**Contexte** : on veut Vue 3 + TS pour le design moderne, mais l'API publique n'a pas besoin d'être exposée à des tiers.  
**Décision** : Inertia.js — un seul codebase, validation et auth Laravel inchangés.  
**Conséquence** : pas de mobile native facile sans réintroduire une API ; acceptable car non visé.

### ADR-02 : Tailwind 4 plutôt que CSS-in-JS / Sass
**Statut** : proposé.  
**Contexte** : déjà sur Tailwind 3, l'équipe maîtrise.  
**Décision** : passer à Tailwind 4 (oxide engine, plus rapide, design tokens natifs).  
**Conséquence** : refonte des classes éventuelles si v4 introduit des breaking changes mineurs.

### ADR-03 : Catégories et tags en tables dédiées
**Statut** : proposé.  
**Contexte** : aujourd'hui c'est un enum/array dans le modèle Episode.  
**Décision** : tables `categories` et `tags` + pivot.  
**Conséquence** : migration de données nécessaire, mais permet des pages catégorie SEO et un back-office simple.

### ADR-04 : Pas d'import auto Facebook/YouTube en v1
**Statut** : proposé.  
**Contexte** : Graph API Facebook nécessite App + Review long ; YouTube Data API v3 nécessite quota.  
**Décision** : v1 = saisie manuelle (l'admin colle l'URL). Préparer le code pour v2.  
**Conséquence** : effort de saisie initial pour rattraper le catalogue (minimisé via une commande Artisan d'import depuis CSV).

### ADR-05 : Auth admin reste en sessions web (pas de SPA token)
**Statut** : accepté (existant).  
**Contexte** : le back-office est utilisé par 2-3 personnes, depuis un navigateur.  
**Décision** : sessions web Laravel + Sanctum réservé à un éventuel besoin futur d'API.

## 10. Risques techniques

| Risque | Sévérité | Mitigation |
|---|---|---|
| Embed Facebook bloqué par CSP / cookie tiers | Moyen | Fallback miniature + bouton externe ; envisager scraper miniature côté serveur |
| Volumétrie image admin sur disque local | Moyen | Spatie + migration S3 prête dès J1 (driver `s3` configurable via .env) |
| Charge YouTube iframe lourde | Faible | Lazy-load iframe ; afficher d'abord miniature + bouton play |
| Ré-écriture front mal cadrée | Élevé | Phasage strict (1 route à la fois), tests Feature à chaque bascule |
| 2FA mal implémentée | Moyen | Utiliser package éprouvé `pragmarx/google2fa-laravel` |
| Pas de tests aujourd'hui | Élevé | Démarrer la v1 par écrire des tests sur l'existant critique avant de toucher au code |

## 11. Questions ouvertes pour l'architecte / le porteur

1. **Confirmer la stack** (interprétation de « VTJS »).
2. **Hébergeur cible** (impacte Spatie media-library config et CI/CD).
3. **Service email** transactionnel choisi (Brevo, MailerSend, SES, Postmark).
4. **Faut-il du SSR** Vue (vue-ssr via Inertia adapter) pour le SEO, ou les meta-tags Inertia côté serveur suffisent-ils ? (Recommandation : meta côté serveur suffisent pour la v1.)
5. **Versioning de l'API admin** si on en expose une plus tard (Sanctum prêt).
