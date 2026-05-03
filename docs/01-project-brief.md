# Project Brief — L'Astuce

**Version** : 1.0  
**Date** : 2026-05-02  
**Statut** : Brouillon — à valider avec le porteur du projet  
**Owner produit** : (à compléter)  
**Page Facebook source** : [facebook.com/light0n](https://www.facebook.com/light0n/) — *LightOn | Niamey*

---

## 1. Vision en une phrase

**L'Astuce** est la plateforme web officielle de l'émission Facebook **LightOn | Niamey**, conçue pour rassembler tous les épisodes (interviews où des invités partagent une astuce de vie / pro / quotidienne), les rendre cherchables et partageables, et créer une communauté autour des astuces de la vie courante.

## 2. Le problème résolu

Aujourd'hui, l'émission **L'Astuce** vit exclusivement sur Facebook :

- Les épisodes se perdent dans le fil chronologique de la page — un épisode publié il y a 6 mois est quasiment introuvable.
- Aucune navigation par thème, invité, date, ou astuce.
- Pas de SEO : impossible d'être trouvé via Google sur des recherches comme « astuce pour conserver les tomates » ou « comment économiser au marché ».
- Aucune mémoire éditoriale : pas de fiche par épisode, pas d'archive consultable.
- Aucun canal direct avec l'audience hors Facebook (newsletter, espace soumission d'astuces, demandes de partenariat).
- L'expansion vers YouTube est planifiée — il faut un hub unique qui agrège **les deux sources**.

## 3. Vision produit

Construire un site rapide, moderne et mobile-first qui devient :

1. **L'archive officielle** : tous les épisodes Facebook (et bientôt YouTube) consultables, classés, recherchables.
2. **La porte d'entrée éditoriale** : pages épisodes riches (invité, thème, astuces clés, transcript éventuel) optimisées pour Google.
3. **Le canal de relation directe** avec la communauté : newsletter, formulaire de soumission d'astuce par les internautes, formulaire partenariat.
4. **Le back-office de l'émission** : interface d'admin pour publier les nouveaux épisodes en quelques clics dès qu'ils sortent sur Facebook/YouTube, et gérer la modération des contributions.

## 4. Cibles utilisateurs

### 4.1 Spectateur / lecteur (cible primaire)
- Profil : public francophone, principalement du Niger et de la diaspora ouest-africaine, mobile-first (réseau parfois lent), 18–55 ans.
- Besoins : retrouver un épisode déjà vu, en découvrir d'autres sur le même thème, partager facilement à un ami WhatsApp/Facebook, recevoir les nouveautés sans avoir à scroller Facebook.

### 4.2 Contributeur (cible secondaire)
- Profil : spectateur fidèle qui a sa propre astuce à proposer pour passer à l'antenne.
- Besoins : un formulaire simple pour soumettre, suivre le statut de sa proposition.

### 4.3 Partenaire / sponsor (cible secondaire)
- Profil : marque locale ou ONG qui veut sponsoriser un épisode ou un cycle.
- Besoins : comprendre l'offre, prendre contact, suivre sa demande.

### 4.4 Équipe éditoriale (admin)
- Profil : 1 à 3 personnes (animateur + 1 modérateur).
- Besoins : ajouter un épisode en moins de 2 minutes, modérer les soumissions, voir les stats, gérer la newsletter.

## 5. Objectifs business (12 premiers mois)

| Objectif | Indicateur cible |
|---|---|
| Asseoir l'identité « hub officiel » | 100 % des épisodes Facebook référencés sur le site |
| Acquisition organique | Top 10 Google sur 30+ requêtes longue traîne « astuce + thème » |
| Croissance newsletter | 1 000 abonnés actifs |
| Engagement contributif | 50+ soumissions d'astuces par mois |
| Diversification revenus | 3 partenariats signés via le site |
| Préparer le passage YouTube | Plateforme prête à indexer les vidéos YouTube dès le lancement |

## 6. Périmètre — ce qui EST dans la v1

- Catalogue d'épisodes (Facebook + YouTube) avec fiche détaillée, embed vidéo natif, recherche, filtres par type/catégorie/date.
- Page d'accueil avec épisode mis en avant, derniers épisodes, accroche newsletter.
- Soumission d'astuce par les internautes (workflow modération en attente / approuvée / rejetée).
- Demande de partenariat (workflow nouveau / en cours / accepté / refusé).
- Newsletter (inscription + désabonnement par token, pas d'envoi automatique en v1 — voir hors-périmètre).
- Page contact, page « à propos ».
- Blog (articles longs autour des thématiques de l'émission).
- Back-office complet : CRUD épisodes / astuces / partenariats / newsletter / blog / utilisateurs admin, dashboard stats, logs d'activité, sécurité (rate limiting, blocage IP, comptes verrouillés).
- Multilingue **FR / EN** (interface).
- SEO : sitemap.xml, RSS, méta-données, schémas structurés.
- Mobile-first, performant sur réseau lent.

## 7. Hors périmètre v1 (à reconfirmer)

- **Import automatique** des vidéos depuis l'API Graph Facebook ou l'API YouTube Data → en v1 chaque épisode est ajouté manuellement via le back-office (l'admin colle l'URL Facebook/YouTube).
- **Envoi automatique de la newsletter** à chaque nouvel épisode → la liste est collectée mais l'envoi est manuel (export ou intégration Mailchimp/Brevo plus tard).
- **Commentaires** sur les épisodes → on s'appuie sur les commentaires Facebook natifs.
- **Système de notes / votes** sur les astuces.
- **Application mobile native** → site responsive uniquement.
- **Monétisation directe** sur le site (pas de pub display, pas de paywall).

## 8. Hypothèses fortes (à valider)

1. La page Facebook **light0n** est bien la source unique des épisodes actuels.
2. Le porteur a la main sur la page Facebook (donc on pourra utiliser le Graph API si un jour on automatise l'import).
3. L'audience principale est francophone (le bilingue FR/EN est nice-to-have, pas un blocant).
4. L'hébergement cible permet PHP 8.1+ et MySQL (compatible Laravel 10).
5. Pas de contraintes RGPD européennes fortes (audience principale Niger), mais on respecte les bonnes pratiques (token de désabonnement newsletter déjà en place).

## 9. Risques

| Risque | Mitigation |
|---|---|
| Facebook change ses règles d'embed (la vidéo ne s'affiche plus) | Avoir un fallback : lien direct + miniature cliquable |
| Volumétrie d'épisodes Facebook à reprendre = travail manuel important | Prévoir une commande Artisan d'import en lot depuis un CSV |
| Newsletter vue comme du spam | Confirmation double opt-in + désabonnement 1-clic en place |
| Charge serveur sur épisode viral | Cache HTTP + lazy load images + pagination déjà prévus |
| Soumissions d'astuces malveillantes | Modération obligatoire + rate limiting + anti-bots déjà en place |

## 10. Ce qui existe déjà (audit du dépôt)

Le projet Laravel **est déjà très avancé**. Sont implémentés :

- Modèles : Episode, AstucesSoumise, Partenariat, NewsletterAbonne, BlogArticle, User (avec rôles), AdminLog, AdminNotification, FailedLoginAttempt.
- Toutes les routes publiques (home, episodes, astuces, partenariats, newsletter, contact, blog, sitemap, RSS).
- Tout le back-office (auth admin sécurisée, dashboard, CRUD complets, export, bulk actions, logs).
- Sécurité : rate limiting, blocage IP, anti-bots, 2FA structuré.
- Front : Vite + Tailwind + GSAP + Swiper + composants JS modulaires, lazy loading, multilingue FR/EN.
- Embed YouTube (via URL) et embed Facebook (URL hardcodée sur la home — à dynamiser).

**Le travail restant porte essentiellement sur** :
- Modernisation du front (refonte design + framework JS de composants).
- Dynamisation du featured Facebook (plus de hardcode).
- Préparation propre du champ YouTube côté épisode (déjà là) + champ Facebook côté épisode (à ajouter).
- Activation de Spatie Media Library (importé mais désactivé).
- Stabilisation (le projet a beaucoup de commits "Fix:" récents, peu/pas de tests automatisés).
- Optionnel : import semi-auto depuis Facebook/YouTube.

## 11. Critères de succès du projet

La v1 est un succès si, 3 mois après le lancement :
- 100 % des épisodes Facebook sont sur le site.
- Le site reçoit ≥ 500 visites uniques par semaine.
- Au moins 1 partenariat est signé via le formulaire.
- ≥ 200 inscrits newsletter.
- Le porteur peut publier un nouvel épisode en moins de 2 minutes sans aide technique.

---

## Questions ouvertes pour le porteur

1. ~~**Stack front cible**~~ — **Tranchée le 2026-05-02** : Vue 3 + TypeScript + Inertia.js + Vite + Tailwind 4.
2. **Import automatique Facebook/YouTube** — souhaité dès la v1 ou v2 ?
3. **Newsletter** — quel envoyeur d'email cible (Mailchimp, Brevo, SES, MailerSend) ?
4. **Hébergement cible** — VPS, OVH, Infomaniak, autre ?
5. **Nom de domaine** — déjà acquis ?
6. **Identité visuelle** — y a-t-il un logo, une charte couleur officielle de LightOn / L'Astuce ?
7. **Volume d'épisodes existants à reprendre** — combien d'épisodes Facebook à intégrer initialement ?
8. **Équipe d'administration** — combien de personnes auront un compte admin / modérateur ?
