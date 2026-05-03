# PRD — L'Astuce

**Version** : 1.0  
**Date** : 2026-05-02  
**Statut** : Brouillon — à valider  
**Document parent** : [01-project-brief.md](./01-project-brief.md)

---

## 1. Personas synthétiques

| Persona | Description | Besoins clés |
|---|---|---|
| **Aïcha — la spectatrice fidèle** | 32 ans, Niamey, smartphone Android entrée de gamme, réseau 3G/4G instable, suit la page Facebook depuis le début. | Retrouver vite un épisode déjà vu, en partager un par WhatsApp, recevoir un mail quand un nouvel épisode sort. |
| **Ibrahim — le contributeur** | 24 ans, étudiant, a une astuce de cuisine à proposer pour passer à l'antenne. | Soumettre son astuce avec une photo, suivre si elle est acceptée. |
| **Madame Diallo — la marque** | Responsable marketing d'une PME locale, cherche à sponsoriser un épisode. | Comprendre l'offre partenariat, prendre contact, suivre sa demande. |
| **Salif — l'animateur (admin)** | Porteur de l'émission, peu technique, publie 1 à 2 épisodes par semaine. | Ajouter un épisode en collant l'URL Facebook, voir les stats, modérer les soumissions. |
| **Awa — la modératrice (admin junior)** | Aide Salif à modérer les contributions et la newsletter. | Approuver/rejeter les soumissions avec un commentaire, sans toucher au reste. |

## 2. User flows clés

### 2.1 Flow « Découverte d'un épisode » (Aïcha)
1. Arrive sur la home (organique Google ou lien partagé).
2. Voit l'épisode mis en avant (vidéo + titre + invité).
3. Lance la lecture inline (pas de redirection vers Facebook/YouTube).
4. Scroll → voit les 6 derniers épisodes en grille.
5. Clique sur un épisode → page détail avec vidéo, description, astuces clés, épisodes similaires.
6. Bouton « Partager sur WhatsApp » en évidence.

### 2.2 Flow « Recherche d'un épisode » (Aïcha)
1. Tape dans la barre de recherche globale (présente dans le header).
2. Suggestions live (autocomplete sur titre, invité, catégorie).
3. Page résultats avec filtres latéraux (type : épisode / coulisses / bonus, date, catégorie).
4. Pagination + "load more" pour mobile.

### 2.3 Flow « Soumission d'astuce » (Ibrahim)
1. Clique sur « Proposer une astuce » dans le header / footer.
2. Formulaire en plusieurs étapes (mobile-friendly) : identité → astuce → médias.
3. Submit → page de confirmation avec un **ID de suivi**.
4. Reçoit un mail de confirmation (optionnel v1).
5. Plus tard, peut consulter `/astuces/track/{id}` pour voir si c'est en attente, approuvé, ou rejeté avec commentaire admin.

### 2.4 Flow « Demande de partenariat » (Madame Diallo)
1. Clique sur « Devenir partenaire » dans le footer.
2. Lit la page d'info (offres, audience, exemples).
3. Remplit le formulaire (entreprise, contact, message).
4. Confirmation + ID de suivi.
5. L'admin reçoit une notification dans le back-office.

### 2.5 Flow « Inscription newsletter » (Aïcha)
1. Voit le bloc newsletter (home, footer, fin d'article blog).
2. Saisit son email → submit.
3. Reçoit un mail de confirmation (double opt-in) avec lien.
4. Clique → email confirmé, statut passe à `actif`.
5. À tout moment, peut se désabonner via le lien `unsubscribe` (token unique) en pied de mail.

### 2.6 Flow « Publier un nouvel épisode » (Salif, admin)
1. Se connecte sur `/admin/login` (rate-limité, 2FA optionnelle).
2. Dashboard → bouton « Nouvel épisode ».
3. Formulaire : titre, type, catégorie, description, **URL Facebook OU URL YouTube** (au moins une), date de diffusion, slug auto, statut (brouillon / programmé / publié).
4. Le système extrait automatiquement : ID vidéo, miniature, durée si possible.
5. Aperçu de l'embed.
6. Clic « Publier ».
7. L'épisode apparaît immédiatement sur le site public.

### 2.7 Flow « Modérer une astuce soumise » (Awa, admin)
1. Notification dans le back-office « 3 nouvelles astuces en attente ».
2. Liste filtrable.
3. Clic sur une astuce → fiche complète.
4. Boutons « Approuver » / « Rejeter » + zone commentaire.
5. Action loggée (qui, quand, quelle décision).

## 3. Exigences fonctionnelles

### 3.1 Catalogue d'épisodes
- **F-EP-01** Un épisode a au moins une URL vidéo (Facebook OU YouTube), un titre, un slug unique, un type, une date de diffusion, un statut.
- **F-EP-02** L'embed vidéo doit fonctionner pour Facebook (oEmbed via `plugins/video.php?href=…`) et YouTube (iframe `youtube.com/embed/{id}`).
- **F-EP-03** Si l'embed Facebook échoue (politique du navigateur, vidéo supprimée), afficher un fallback : miniature + bouton « Voir sur Facebook ».
- **F-EP-04** Pagination : 12 épisodes / page (configurable).
- **F-EP-05** Filtres : type, catégorie, date (mois/année), tri (récent / ancien / populaire / titre).
- **F-EP-06** Recherche full-text sur titre, description, invité, tags.
- **F-EP-07** Page épisode affiche : vidéo, titre, invité, description complète, tags, catégorie, date, navigation prev/next, 3 épisodes similaires, boutons partage (WhatsApp, Facebook, Twitter, lien copié).
- **F-EP-08** Compteur de vues incrémenté à chaque consultation (1 par session pour éviter la triche).
- **F-EP-09** Statuts : brouillon (caché public), programmé (visible à partir de la date), publié, archivé.
- **F-EP-10** Flux RSS `/episodes/rss` exposant les 20 derniers épisodes.

### 3.2 Soumission d'astuces
- **F-AS-01** Formulaire public avec champs : nom, email (validés), titre, catégorie, difficulté, temps estimé, description, matériel, étapes (liste), conseils, fichier joint (PDF/DOC/JPG/PNG, max 5 Mo), images additionnelles (max 3).
- **F-AS-02** Validation côté client (JS) ET côté serveur (Laravel rules).
- **F-AS-03** Captcha ou honeypot anti-spam.
- **F-AS-04** Rate limiting : 3 soumissions / heure / IP.
- **F-AS-05** Statut initial : `en_attente`.
- **F-AS-06** ID de suivi communiqué et consultable sur `/astuces/track/{id}`.
- **F-AS-07** Affichage public uniquement des astuces `approuvées`.

### 3.3 Demande de partenariat
- **F-PA-01** Page d'info partenariat avec offres et exemples.
- **F-PA-02** Formulaire : nom entreprise, contact, email, téléphone (optionnel), message.
- **F-PA-03** Statut initial : `nouveau`. Workflow : nouveau → en_cours → accepte / refuse.
- **F-PA-04** Notification admin à la création.
- **F-PA-05** ID de suivi communiqué et consultable sur `/partenariats/track/{id}`.

### 3.4 Newsletter
- **F-NL-01** Inscription : email seul.
- **F-NL-02** Double opt-in : email envoyé avec lien de confirmation (token unique).
- **F-NL-03** Statut : actif / inactif / désabonné.
- **F-NL-04** Désabonnement 1-clic via `/newsletter/unsubscribe/{token}`.
- **F-NL-05** Page de préférences via `/newsletter/preferences/{token}`.
- **F-NL-06** Export CSV depuis le back-office (intégration Mailchimp/Brevo manuelle en v1).

### 3.5 Contact
- **F-CT-01** Formulaire : nom, email, sujet, message.
- **F-CT-02** Validation, rate limiting (3/h/IP).
- **F-CT-03** Email envoyé à l'adresse de contact configurée.

### 3.6 Blog
- **F-BL-01** CRUD admin, statuts brouillon / programmé / publié.
- **F-BL-02** Liste paginée, recherche, archives par mois/année, filtres catégorie/tag.
- **F-BL-03** Page article avec contenu riche, méta SEO, partage social, articles populaires.
- **F-BL-04** Flux RSS `/blog/rss`.

### 3.7 Multilingue
- **F-LG-01** Langues : `fr` (défaut), `en`.
- **F-LG-02** URL préfixée : `/fr/...` et `/en/...`.
- **F-LG-03** Sélecteur de langue dans le header.
- **F-LG-04** Détection automatique première visite (navigateur).
- **F-LG-05** Contenu admin : titre/description épisode/article peuvent être saisis dans les deux langues (champs dédiés ou Spatie Translatable).

### 3.8 SEO & partage
- **F-SE-01** Sitemap.xml dynamique (épisodes + articles + pages).
- **F-SE-02** Méta : title, description, og:image, og:type, twitter:card sur toutes les pages publiques.
- **F-SE-03** Schémas structurés : `VideoObject` sur fiche épisode, `Article` sur fiche blog, `Organization` sur la home.
- **F-SE-04** Canonical URL sur chaque page.
- **F-SE-05** Slugs lisibles (`/episodes/comment-conserver-tomates-plus-longtemps`).

### 3.9 Back-office admin
- **F-AD-01** Authentification : email + mot de passe, rate limiting 5 essais / 15 min.
- **F-AD-02** Verrouillage compte après 10 échecs (déverrouillage manuel ou délai).
- **F-AD-03** Rôles : `admin` (tout), `moderator` (astuces + commentaires + newsletter).
- **F-AD-04** Dashboard : compteurs (épisodes publiés, soumissions en attente, abonnés newsletter, partenariats en cours), graphiques 30 jours.
- **F-AD-05** CRUD : Episode, AstucesSoumise, Partenariat, NewsletterAbonne, BlogArticle, User (admins seulement).
- **F-AD-06** Bulk actions sur les listes (publier en lot, archiver, supprimer).
- **F-AD-07** Export CSV/JSON sur chaque section.
- **F-AD-08** Logs : chaque action admin loggée (acteur, action, cible, IP, sévérité).
- **F-AD-09** Notifications in-app (cloche dans le header) + paramétrage email par admin.
- **F-AD-10** Page Sécurité : logs login, IPs bloquées, déblocage manuel, déverrouillage compte.
- **F-AD-11** 2FA optionnelle par admin (TOTP, structure déjà en place).
- **F-AD-12** Mode maintenance toggleable depuis l'admin.
- **F-AD-13** Bouton « backup base de données » (ou job planifié, voir NFR).

## 4. Exigences non fonctionnelles

| Code | Exigence | Cible |
|---|---|---|
| NFR-PERF-01 | LCP page home | < 2,5 s sur 4G |
| NFR-PERF-02 | Time-to-Interactive page épisode | < 3 s sur 4G |
| NFR-PERF-03 | Poids initial JS | < 200 Ko gzip |
| NFR-PERF-04 | Lazy-load systématique des images et iframes | 100 % |
| NFR-AVAIL-01 | Disponibilité site public | 99,5 % / mois |
| NFR-SECU-01 | HTTPS obligatoire | 100 % |
| NFR-SECU-02 | Headers sécurité (CSP, X-Frame-Options, HSTS) | configurés |
| NFR-SECU-03 | Mots de passe hashés bcrypt cost ≥ 12 | conforme Laravel |
| NFR-SECU-04 | Rate limiting actif sur formulaires publics et login | en place |
| NFR-SECU-05 | Backup BDD | quotidien, rétention 30 j |
| NFR-A11Y-01 | Conformité WCAG 2.1 niveau AA | sur les pages clés (home, épisode, formulaires) |
| NFR-I18N-01 | FR + EN, contenus admin saisissables dans les 2 langues | en place |
| NFR-MOB-01 | Responsive 320 px → 1920 px | en place |
| NFR-COMPAT-01 | Supporte Chrome / Safari / Firefox / Edge dernières 2 versions, Android Chrome ≥ 90 | en place |
| NFR-TEST-01 | Couverture tests automatisés | ≥ 60 % sur le domaine métier (Episode, Soumission, Newsletter) |

## 5. Données — vue logique

(Voir doc Architecture pour le schéma SQL détaillé.)

Entités principales :
- **Episode** (id, titre[fr,en], slug, type, statut, description[fr,en], facebook_url, youtube_url, thumbnail, duree, date_publication, vues, category_id, tags[])
- **Categorie** (id, nom[fr,en], slug, ordre, icone)
- **Tag** (id, nom, slug)
- **AstucesSoumise** (id, nom, email, titre, categorie, difficulte, temps_estime, description, materiel, etapes[json], conseils, fichier_joint, images[json], status, commentaire_admin)
- **Partenariat** (id, nom_entreprise, contact, email, telephone, message, status, notes_internes)
- **NewsletterAbonne** (id, email, date_inscription, status, token_desabonnement, confirmed_at)
- **BlogArticle** (id, titre[fr,en], slug, contenu[fr,en], extrait, image, date_publication, is_published, meta_description)
- **User** (id, name, email, password, is_admin, role, permissions[json], 2FA fields, lock fields)
- **AdminLog** (id, user_id, action, model, model_id, description, severity, ip)
- **AdminNotification** (id, user_id, type, priority, data, read_at)

## 6. Intégrations externes

| Intégration | Usage v1 | Source |
|---|---|---|
| Facebook oEmbed (public) | Embed vidéo sur fiche épisode | URL collée par admin |
| YouTube iframe API | Embed + contrôles vidéo sur fiche épisode | URL collée par admin |
| Google Analytics 4 | Mesure d'audience | tag dans layout |
| Mailchimp / Brevo (manuel v1) | Diffusion newsletter | export CSV |
| Sentry (recommandé) | Monitoring erreurs prod | hors scope v1 si budget |

**Hors scope v1** : Graph API Facebook (auth + permissions), YouTube Data API v3 (clé + quota).

## 7. Métriques produit à instrumenter

- Vues / épisode (déjà en BDD).
- Taux de partage (event GA par bouton).
- Taux de complétion lecture vidéo (event GA YouTube API ; non disponible Facebook).
- Soumissions astuces / mois.
- Demandes partenariat / mois.
- Inscriptions newsletter / mois + taux de confirmation double opt-in.
- Taux de rebond home et page épisode.

## 8. Plan de versions

### v1.0 — MVP rebrandé (cible : 6–8 semaines)
- Refonte design (Vue 3 + Inertia + Tailwind, voir Architecture).
- Tout l'existant fonctionnel + champ `facebook_url` ajouté à Episode + featured Facebook dynamique (fini le hardcode).
- Spatie Media Library activé pour images blog + thumbnails épisodes.
- Tests automatisés sur le domaine critique.

### v1.1 — Croissance (4 semaines)
- Catégories et tags en tables dédiées (avec back-office).
- Notifications email aux admins sur nouvelles soumissions / partenariats.
- Schémas structurés VideoObject / Article complets.
- Backup BDD planifié quotidien.

### v2.0 — Automatisation (à planifier)
- Import semi-auto Facebook (Graph API) et YouTube (Data API).
- Envoi automatique de newsletter à chaque nouvel épisode (via Brevo API).
- Système de commentaires (interne ou via Disqus).
- Espace contributeur (compte invité avec historique de ses soumissions).

## 9. Critères d'acceptation globaux du PRD

- Toutes les exigences `F-*` ci-dessus sont implémentées et démontrables.
- Toutes les NFR mesurables sont vérifiées (audit Lighthouse, scan headers, test charge).
- Le porteur (Salif) peut publier un épisode de bout en bout sans assistance technique.
- Le site charge en moins de 3 s sur 4G mobile pour les pages publiques principales.

## 10. Questions ouvertes (à trancher avant gel du PRD)

1. **Catégories et tags** : tables dédiées ou conserver l'enum/array actuel ? (recommandation : tables dédiées dès la v1).
2. **Page « Invités »** : faut-il une fiche invité dédiée listant tous ses passages dans l'émission ?
3. **Workflow éditorial** : faut-il un statut « en relecture » entre brouillon et publié pour les épisodes ?
4. **Modération astuces** : faut-il pouvoir éditer le contenu d'une astuce avant publication ?
5. **Newsletter** : envoi auto dès la v1 (alors il faut intégrer un service email) ou v1.1 (export manuel) ?
6. **Commentaires** : on garde Facebook natif ou on intègre Disqus dès la v1 ?
