# Documentation projet — L'Astuce

Documents produits le **2026-05-02** à partir de l'audit du dépôt et du contexte fourni par le porteur du projet (émission Facebook *LightOn | Niamey*).

| # | Document | Pour qui | Contenu |
|---|---|---|---|
| 01 | [Project Brief](./01-project-brief.md) | Tout le monde | Vision, problème, cibles, objectifs, périmètre, risques |
| 02 | [PRD](./02-prd.md) | Produit + Dev | Personas, user flows, exigences fonctionnelles & non-fonctionnelles, plan de versions |
| 03 | [Architecture](./03-architecture.md) | Dev + Ops | Stack, schéma, intégrations, organisation du code, déploiement, ADR |
| 04 | [Epics & Stories](./04-stories.md) | Dev + PM | 9 epics, ~40 stories avec critères d'acceptation, definition of done |

## Lecture conseillée

1. Commencer par **01-project-brief.md** (10 min).
2. Lire **02-prd.md** pour comprendre ce qui doit être fait (20 min).
3. Lire **03-architecture.md** pour comprendre comment (15 min).
4. Utiliser **04-stories.md** comme backlog opérationnel.

## Décisions à confirmer avant de coder

Voir la section *Questions ouvertes* en fin de chaque document — synthétisé ici :

1. ~~**Stack front**~~ — **Tranchée le 2026-05-02** : Vue 3 + TypeScript + Inertia.js + Vite + Tailwind 4.
2. **Hébergement cible**.
3. **Service email transactionnel** (Brevo, MailerSend, Postmark…).
4. **Import auto Facebook/YouTube** v1 ou v2.
5. **Catégories/tags** : tables dédiées dès v1 (recommandé) ou rester sur enum/array.
6. **Volume d'épisodes** Facebook à reprendre initialement.
7. **Identité visuelle** : logo + charte couleur officielle disponibles ?
