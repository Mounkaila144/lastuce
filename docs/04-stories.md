# Epics & Stories — L'Astuce

**Version** : 1.0  
**Date** : 2026-05-02  
**Statut** : Brouillon  
**Documents parents** : [01-project-brief.md](./01-project-brief.md), [02-prd.md](./02-prd.md), [03-architecture.md](./03-architecture.md)

---

## Vue d'ensemble

8 Epics regroupant les stories de la v1 + jalons v1.1.

| # | Epic | Effort | Phase |
|---|---|---|---|
| E1 | Fondations techniques (Inertia + Vue 3 + TS + Tailwind 4) | 1 sprint | v1 — préalable |
| E2 | Refonte design système & composants UI | 1 sprint | v1 |
| E3 | Catalogue d'épisodes (Facebook + YouTube) | 2 sprints | v1 |
| E4 | Soumission d'astuces communautaires | 1 sprint | v1 |
| E5 | Newsletter & inscription double opt-in | 0,5 sprint | v1 |
| E6 | Partenariats & contact | 0,5 sprint | v1 |
| E7 | Blog éditorial | 1 sprint | v1 |
| E8 | Back-office unifié & sécurité | 2 sprints | v1 |
| E9 | Automatisation & croissance | 1,5 sprint | v1.1 |

Sprint = 2 semaines.

---

## E1 — Fondations techniques

**Objectif** : poser la stack moderne sans casser l'existant ni perdre de fonctionnalité.

### S1.1 — Installer Inertia.js + Vue 3 + TypeScript
**En tant que** dev  
**Je veux** un projet Laravel 10 avec Inertia + Vue 3 + TS configuré  
**Afin de** pouvoir migrer les pages une par une.  
**Critères d'acceptation**
- `composer require inertiajs/inertia-laravel` installé.
- `npm install @inertiajs/vue3 vue@latest typescript vue-tsc` installé.
- `tsconfig.json` créé, alias `@/` → `resources/js/`.
- `resources/js/app.ts` initialise Inertia avec layout par défaut.
- `resources/views/app.blade.php` minimal contient `@inertiaHead` et `@inertia`.
- Une route de démo `/__inertia-test` rend une page Vue « Hello » accessible en navigateur.
- `npm run build` passe sans erreur, type-check inclus.

### S1.2 — Migrer vers Tailwind 4 avec design tokens
**En tant que** dev front  
**Je veux** Tailwind 4 configuré avec les couleurs de la marque  
**Afin de** baser tous les composants sur un design system cohérent.  
**CA**
- `tailwindcss@4` installé (engine oxide).
- `app.css` définit les tokens : `--color-brand-500`, `--color-accent-500`, `--color-surface-*`, typographie.
- Dark mode prêt (variable CSS, pas opt-in v1).
- Police principale chargée via `@font-face` (woff2, subset latin) sans dépendre de Google Fonts (privacy + perf).

### S1.3 — Configurer Pinia + vue-i18n
**CA**
- Pinia installé, store `useUiStore` créé (locale, mobileMenuOpen).
- `vue-i18n` installé, traductions FR/EN initiales chargées depuis fichiers `resources/js/i18n/{fr,en}.json`.
- Les traductions backend Laravel sont aussi exposées via Inertia shared props (clé `translations`).

### S1.4 — Mettre en place les tests Pest + Vitest
**CA**
- Pest installé, 1 test smoke `it('home returns 200')` passe.
- Vitest installé, 1 test smoke sur un composable `useDebounce` passe.
- GitHub Actions workflow `ci.yml` exécute les deux suites + `vue-tsc` à chaque PR.

### S1.5 — Activer Spatie Media Library
**CA**
- `spatie/laravel-medialibrary` activé (déjà dans composer.json mais commenté dans modèles).
- Modèle `Episode` implémente `HasMedia`, collections `thumbnail` et `gallery` avec conversions `thumb` (320), `card` (640), `hero` (1280).
- Modèle `BlogArticle` implémente `HasMedia`, collection `featured`.
- Disk `media` configuré (local en dev, S3-ready en prod via `.env`).
- Migration `media` table appliquée.

---

## E2 — Refonte design système & composants UI

**Objectif** : produire les briques UI réutilisables à partir desquelles toutes les pages seront assemblées.

### S2.1 — Layout public (Header, Footer, MobileMenu)
**CA**
- `<AppHeader>` : logo, nav principale, recherche globale, sélecteur langue, bouton « Proposer une astuce ».
- `<AppFooter>` : nav secondaire, mention légale, newsletter mini-form, réseaux sociaux.
- `<MobileMenu>` : drawer accessible, fermeture ESC + clic extérieur, focus trap.
- Responsive 320–1920 px vérifié dans Chrome devtools (3 breakpoints).
- Accessibilité : navigation clavier complète, ARIA labels, contrastes AA.

### S2.2 — Composants primitifs UI
**CA**
- `<Button>` : variants `primary | secondary | ghost | danger`, sizes `sm | md | lg`, loading state, icon slot.
- `<Input>`, `<Textarea>`, `<Select>` : avec label, helper, error state.
- `<Modal>` : Radix-vue ou Headless UI, accessible, animation fade+scale.
- `<Tabs>`, `<Dropdown>`, `<Tooltip>` : accessibles.
- `<Toast>` global via composable `useToast`.
- Story de chaque primitive dans Storybook (optionnel) ou page interne `/__ui-kit` accessible en dev.

### S2.3 — Composants domaine
**CA**
- `<EpisodeCard>` : props `episode`, affiche thumbnail, titre, type badge, durée, date, hover GSAP.
- `<EpisodeListItem>` : variante horizontale.
- `<VideoPlayer>` : props `:url`, détecte FB/YT, affiche miniature + bouton play, charge l'iframe au clic (perf).
- `<NewsletterForm>` : email + submit, états idle/loading/success/error, double opt-in friendly.
- `<ShareButtons>` : WhatsApp, Facebook, Twitter, copier le lien.

### S2.4 — Service côté Laravel `VideoEmbedService`
**CA**
- Classe `app/Services/VideoEmbedService.php` avec méthodes `detectProvider`, `extractId`, `thumbnail`, `embedUrl`.
- Couvre YouTube (`youtube.com/watch?v=`, `youtu.be/`, `youtube.com/embed/`) et Facebook (`facebook.com/{page}/videos/{id}`, `fb.watch/{id}`).
- Tests unitaires Pest 100 % de couverture sur ce service.
- Le modèle Episode utilise ce service via accessors.

---

## E3 — Catalogue d'épisodes

**Objectif** : la fonction cœur du site.

### S3.1 — Migration data : ajouter facebook_url et catégories/tags en tables
**CA**
- Migration : `episodes` ajoute `facebook_url` (string nullable), `invite_nom`, `invite_bio`, `transcript`.
- Migration : créer table `categories` et `tags` + pivot `episode_tag`.
- Migration de données : convertir l'enum/array tags actuel en lignes `tags` + pivot.
- Form Request `StoreEpisodeRequest` exige au moins une URL (FB ou YT) avec validation regex.

### S3.2 — Page d'accueil refondée (Inertia + Vue)
**CA**
- Route `/` (et `/{locale}/`) rend `Pages/Home.vue` via Inertia.
- Sections : héros featured episode (le plus récent publié, vidéo embed), 6 derniers épisodes en grille, 3 derniers articles blog, bloc partenaires acceptés, bloc newsletter, bloc témoignages.
- Lazy-load images et iframe.
- Lighthouse perf ≥ 85 sur 4G simulée.
- L'épisode featured est dynamique (plus de URL hardcodée).

### S3.3 — Page liste épisodes
**CA**
- Route `/episodes` rend `Pages/Episodes/Index.vue`.
- 12 épisodes par page, pagination Inertia.
- Filtres : type (chips), catégorie (select), date (mois/année), tri (récent / ancien / populaire / titre).
- Recherche live via composable `useDebounce(300ms)` qui repousse la query Inertia avec preserveState.
- État vide géré.

### S3.4 — Page fiche épisode
**CA**
- Route `/episodes/{slug}` rend `Pages/Episodes/Show.vue`.
- Affiche : `<VideoPlayer>`, titre, type, catégorie, tags, invité, description, date, vues.
- Navigation prev/next.
- 3 épisodes similaires (même catégorie, exclure courant).
- `<ShareButtons>` actifs.
- Méta SEO + Open Graph + schéma `VideoObject`.
- Compteur vues : incrément côté serveur 1 fois par session (cookie ou IP).

### S3.5 — Recherche globale (header)
**CA**
- Endpoint `GET /api/search/suggestions?q=...` (route api légère, sans Inertia).
- Suggestions live (5 résultats) sur titres, invités, catégories.
- Composant `<GlobalSearch>` dans `<AppHeader>` avec autocomplete clavier.
- Submit → redirige vers `/search?q=...` qui rend une page de résultats agrégés (épisodes + astuces + articles).

### S3.6 — Sitemap et RSS
**CA**
- `/sitemap.xml` lit toutes les routes publiques + tous les épisodes/articles publiés.
- `/episodes/rss` valide W3C avec 20 derniers épisodes.
- `/blog/rss` idem côté blog.

---

## E4 — Soumission d'astuces

### S4.1 — Formulaire public multi-étapes
**CA**
- Route `/astuces/create` rend `Pages/Astuces/Create.vue`.
- 3 étapes : Identité (nom, email) → Astuce (titre, catégorie, difficulté, temps, description, étapes dynamiques) → Médias (fichier + 0–3 images).
- Validation Vee-Validate + zod côté client, mirror Form Request côté serveur.
- Honeypot anti-bot + rate limit `security:upload`.
- Submit → redirection vers `/astuces/success?id={id}` avec ID de suivi affiché.
- L'utilisateur reçoit un email de confirmation (queue job).

### S4.2 — Page de suivi
**CA**
- Route `/astuces/track/{id}` rend `Pages/Astuces/Track.vue`.
- Affiche : ID, statut visuel (en attente / approuvée / rejetée), commentaire admin si rejeté.
- Pas d'auth requise (l'ID fait office de jeton — on accepte le risque acceptable car non-sensible).

### S4.3 — Liste publique des astuces approuvées
**CA**
- Route `/astuces` rend `Pages/Astuces/Index.vue`.
- Pagination 20 / page, filtre catégorie + difficulté.
- Carte `<AstuceCard>` cliquable.

### S4.4 — Page astuce
**CA**
- Route `/astuces/{id}` rend `Pages/Astuces/Show.vue` uniquement si statut = `approuvee`.
- Affiche tous les champs structurés (étapes en liste numérotée, matériel, conseils).
- Boutons partage.

---

## E5 — Newsletter

### S5.1 — Inscription double opt-in
**CA**
- `<NewsletterForm>` POST sur `/newsletter` : crée enregistrement statut `inactif` + token, envoie mail confirmation (job queue).
- Route `/newsletter/confirm/{token}` valide et passe au statut `actif`, affiche page de confirmation.
- Email de confirmation contient lien désabonnement (politique RGPD-friendly).

### S5.2 — Désabonnement et préférences
**CA**
- Route `/newsletter/unsubscribe/{token}` désabonne en 1 clic, page de confirmation.
- Route `/newsletter/preferences/{token}` permet de mettre à jour des préférences (placeholder v1, structure prête v1.1).
- Token rotatif si l'utilisateur clique « regenerate token ».

### S5.3 — Quick subscribe footer
**CA**
- Le mini-form du footer poste sur `/newsletter/quick-subscribe` (même endpoint, marqué `source=footer`).
- Toast de succès sans changement de page (Inertia partial reload).

---

## E6 — Partenariats & contact

### S6.1 — Page info partenariat
**CA**
- Route `/partenariats` rend `Pages/Partenariats/Index.vue` avec offre, audience, exemples (data statique en v1).
- Bouton CTA vers le formulaire.

### S6.2 — Formulaire partenariat
**CA**
- Route `/partenariats/create` rend `Pages/Partenariats/Create.vue`.
- Validation, honeypot, rate limit.
- Submit → `/partenariats/success?id=...` + notification admin créée + (v1.1) email aux admins.

### S6.3 — Page contact
**CA**
- Route `/contact` rend `Pages/Contact.vue` avec formulaire simple (nom, email, sujet, message).
- Validation + rate limit `contact` (3/h/IP).
- Email envoyé à l'adresse de contact configurée dans `.env`.

---

## E7 — Blog

### S7.1 — Liste articles
**CA**
- Route `/blog` rend `Pages/Blog/Index.vue` avec articles publiés.
- Pagination 9 / page.
- Filtres archives (mois/année), recherche.

### S7.2 — Page article
**CA**
- Route `/blog/{slug}` rend `Pages/Blog/Show.vue`.
- Contenu riche, image hero, méta SEO, articles populaires en sidebar.
- Schéma `Article`.

### S7.3 — Pages catégorie / tag
**CA**
- `/blog/category/{slug}` et `/blog/tag/{slug}` listent les articles correspondants.
- Méta SEO spécifique (title incluant le nom de catégorie/tag).

---

## E8 — Back-office unifié & sécurité

### S8.1 — Auth admin Inertia
**CA**
- Route `/admin/login` rend `Pages/Admin/Auth/Login.vue`.
- Form action POST `/admin/login` avec rate limit `security:login` (5/15 min).
- Verrouillage compte après 10 échecs (déjà existant, à conserver).
- Sur succès, redirige `/admin/dashboard`.

### S8.2 — Dashboard
**CA**
- `Pages/Admin/Dashboard.vue` affiche : 4 cartes stats (épisodes publiés, soumissions en attente, abonnés newsletter, partenariats en cours) + 2 graphiques (vues 30j, soumissions 30j) + 10 derniers logs + alertes auto.
- Cache Redis 5 min sur les agrégats.
- Bouton « Nouvel épisode » accessible en 1 clic.

### S8.3 — CRUD Épisodes (admin)
**CA**
- Pages `Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue` sous `Pages/Admin/Episodes/`.
- Form unique avec WYSIWYG description (Tiptap), upload thumbnail (Spatie), slug auto.
- Aperçu temps réel de l'embed dès qu'une URL FB ou YT valide est saisie (composant `<VideoPlayer>` réutilisé).
- Bulk actions : publier, archiver, supprimer.
- Export CSV des épisodes.

### S8.4 — CRUD Astuces (modération)
**CA**
- Liste filtrable par statut (en_attente / approuve / rejete).
- Fiche détail : tous les champs, fichiers téléchargeables.
- Actions : Approuver (avec commentaire optionnel), Rejeter (commentaire obligatoire).
- Action loggée dans `admin_logs`.
- Notification au soumettant par email si configuré (v1.1).

### S8.5 — CRUD Partenariats
**CA**
- Liste filtrable par statut (nouveau / en_cours / accepte / refuse).
- Workflow buttons : Marquer en cours → Accepter / Refuser.
- Champ notes internes éditable.
- Bulk actions et export.

### S8.6 — Newsletter admin
**CA**
- Liste abonnés filtrable par statut.
- Stats : taux d'actifs, nouveaux ce mois, courbe de croissance.
- Actions : activer, désactiver, désabonner manuellement.
- Export CSV (pour import Brevo/Mailchimp v1).
- v1.1 : envoi de campagne intégré.

### S8.7 — Blog admin
**CA**
- CRUD articles avec WYSIWYG, image featured Spatie, statuts brouillon/programmé/publié.
- Slug auto, méta description, extrait.

### S8.8 — Gestion des admins
**CA**
- Route admin uniquement (`role=admin`) : créer, éditer, supprimer un user admin/moderator.
- Verrouillage / déverrouillage manuel.
- Activation 2FA (TOTP, package `pragmarx/google2fa-laravel`).

### S8.9 — Sécurité & logs
**CA**
- Page `/admin/security/logs` : journal admin paginé, filtre par sévérité/action/user.
- Page `/admin/security/failed-attempts` : liste tentatives login échouées + bouton bloquer/débloquer IP.
- Page `/admin/security/blocked-ips` : liste IPs bloquées + déblocage manuel.

### S8.10 — Settings & maintenance
**CA**
- Page `/admin/settings` : paramètres généraux (nom site, email contact, GA ID, réseaux sociaux).
- Toggle mode maintenance.
- Bouton « Lancer un backup » (queue job spatie/laravel-backup).

### S8.11 — Headers de sécurité globaux
**CA**
- Middleware `SecurityHeaders` ajouté au kernel : CSP, HSTS, X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy strict-origin-when-cross-origin.
- CSP autorise embed Facebook + YouTube uniquement.
- Scan via securityheaders.com → grade A.

---

## E9 — Automatisation & croissance (v1.1)

### S9.1 — Notifications email aux admins
**CA**
- À chaque création AstucesSoumise, Partenariat, NewsletterAbonne → email aux admins ayant opté pour les notifications (via préférence en BDD).
- Job queue, retry 3x.

### S9.2 — Backup quotidien automatisé
**CA**
- `spatie/laravel-backup` planifié dans `app/Console/Kernel.php` à 3h du matin.
- Stockage S3 distant.
- Notification email en cas d'échec.

### S9.3 — Schémas structurés enrichis
**CA**
- `VideoObject` complet sur fiche épisode (uploadDate, duration, thumbnailUrl, contentUrl).
- `Article` enrichi sur fiche blog (author, datePublished, image).
- Validation via Google Rich Results Test → pass.

### S9.4 — Catégories et tags en back-office
**CA**
- CRUD admin sur `categories` et `tags`.
- Réordonnancement drag & drop pour les catégories.
- Page publique `/categorie/{slug}` listant épisodes + articles.

### S9.5 — Import en lot d'épisodes existants depuis CSV
**CA**
- Commande `php artisan episodes:import {csv}` qui parse un fichier CSV (titre, type, fb_url, yt_url, date, description).
- Crée les épisodes en statut `draft` pour relecture.
- Rapport d'erreurs en sortie.

---

## Backlog non priorisé (v2+)

- Import auto Facebook (Graph API) et YouTube (Data API).
- Envoi automatique newsletter à chaque nouvel épisode (intégration Brevo/Mailchimp transactionnel).
- Système de commentaires natif (ou Disqus).
- Espace contributeur connecté.
- App mobile (PWA dans un premier temps).
- Système de recommandations personnalisé (cookie-based).
- A/B testing sur la home.

---

## Définition of Done — applicable à toute story

- Code mergé sur `main` après revue PR.
- Tests Pest et/ou Vitest verts.
- `vue-tsc` sans erreur.
- ESLint, Pint, Prettier sans warning.
- Lighthouse mobile ≥ 85 perf / 95 a11y / 100 SEO sur la page concernée (si page publique).
- Doc utilisateur admin mise à jour si la story touche au back-office.
- Aucun TODO restant dans le code livré.
