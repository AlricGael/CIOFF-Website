# Site du CIOFF France (WordPress)

Refonte du site [cioff-france.org](https://cioff-france.org) selon le [cahier des charges v2.0](docs/cahier-des-charges-v2.pdf).

Objectif : un site que **n'importe quel bénévole peut modifier** sans toucher au code.

## ▶ Démo en ligne (gratuite)

**[Ouvrir le site de démonstration dans WordPress Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/AlricGael/CIOFF-Website/main/blueprint.json)**

Un vrai WordPress, installé automatiquement avec le thème, l'extension et des adhérents **fictifs** (environ 30 secondes de chargement). Chaque visiteur a sa propre copie : rien n'est sauvegardé et les e-mails ne partent pas.

- Vous êtes connecté en administrateur. Voir le menu **À valider**, **Annuaire** et **Réglages CIOFF**.
- Pour tester en tant qu'adhérent : se déconnecter, puis se reconnecter avec l'identifiant `groupe`, `festival` ou `membre` (mot de passe `demo`) et aller dans **Mon espace**.

## Contenu du dépôt

| Dossier | Rôle |
|---|---|
| `wp-content/themes/cioff/` | Thème en blocs (apparence) : couleurs, typographie, en-tête, pied de page, modèles. Tout se modifie dans **Apparence → Éditeur**. |
| `wp-content/plugins/cioff-core/` | Extension « CIOFF France – Fonctions du site » : annuaire et carte, fiches adhérents, validation, agenda, intranet, contact, rôles. |
| `docs/` | Cahier des charges, guide administrateur, guide adhérent. |
| `maquette/` | Maquette **statique** (données fictives) pour montrer le design à la commission. C'est elle que publie Vercel (`vercel.json`). Elle réutilise le CSS et le JS de la carte du plugin. |

> Vercel ne peut pas faire tourner WordPress (PHP + MySQL). Le vrai site se teste dans LocalWP puis s'installe chez OVH.

## Ce que fait l'extension

- **Annuaire des adhérents** : 6 types (festival, festival associé, groupe labellisé, groupe associé, membre participant, membre individuel). Carte unique (Leaflet / OpenStreetMap) avec marqueurs colorés par type, filtres, recherche par nom, ville, département ou région, zoom automatique, et liste triable avec « Afficher plus ».
- **Fiches** avec champs communs et champs propres au type. Position calculée automatiquement à partir de la ville. Les membres individuels sont placés au centre de leur département, par confidentialité. Boutons de partage sur chaque fiche.
- **Mon espace** : l'adhérent crée ou modifie sa fiche depuis le site, sans voir l'administration WordPress.
- **Validation** (modèle hybride) : une page « À valider » regroupe les nouvelles fiches, les modifications (présentées en avant / après) et les événements proposés. Un clic suffit pour publier ou renvoyer avec un commentaire. Des e-mails partent automatiquement. Une fiche déjà publiée reste en ligne tant que ses modifications ne sont pas validées.
- **Agenda** : réunions CIOFF France et CIOFF Jeunes, événements des adhérents (proposés puis validés), mise « à la une » sur l'accueil.
- **Intranet** : la page « Intranet » et ses sous-pages sont réservées aux adhérents connectés. N'importe quelle page peut aussi être réservée, y compris à certains profils seulement. L'intranet contient la liste des membres et de leurs e-mails.
- **Contact** : liste de catégories, chacune liée à une ou plusieurs adresses destinataires, le tout réglable dans l'administration. Anti-spam sans captcha. Chaque message est aussi archivé dans l'administration.
- **Réglages CIOFF** : thème annuel, lien CIOFF International, réseaux sociaux, e-mails de validation, routage du formulaire de contact.
- **8 profils** : Administrateur CIOFF, 6 profils d'adhérents et le visiteur public.
- Blocs « CIOFF France » dans l'éditeur (bouton +), avec des shortcodes équivalents (`[cioff_annuaire]`, `[cioff_agenda]`…).

## Installation locale (LocalWP)

1. Installer [LocalWP](https://localwp.com), puis créer un site (« Preferred », PHP 8+).
2. Copier (ou lier) les deux dossiers dans le site :
   - `wp-content/themes/cioff` vers `…/Local Sites/<site>/app/public/wp-content/themes/cioff`
   - `wp-content/plugins/cioff-core` vers `…/app/public/wp-content/plugins/cioff-core`

   Pour travailler directement dans le dépôt, utiliser des liens (dans PowerShell **administrateur**) :
   ```powershell
   New-Item -ItemType Junction -Path "$env:USERPROFILE\Local Sites\cioff\app\public\wp-content\plugins\cioff-core" -Target "$env:USERPROFILE\Documents\GitHub\CIOFF-Website\wp-content\plugins\cioff-core"
   New-Item -ItemType Junction -Path "$env:USERPROFILE\Local Sites\cioff\app\public\wp-content\themes\cioff" -Target "$env:USERPROFILE\Documents\GitHub\CIOFF-Website\wp-content\themes\cioff"
   ```
3. Dans l'administration : **Réglages → Général**, langue « Français ». Puis **Extensions**, activer « CIOFF France – Fonctions du site ». Enfin **Apparence → Thèmes**, activer « CIOFF France ».
4. **Réglages → Permaliens** : choisir « Titre de la publication ».

À l'activation, l'extension crée les pages (Accueil, Le CIOFF, CIOFF Jeunes, Annuaire, Agenda, Boîte à outils, Intranet, Mon espace, Contact…), le menu principal et la page d'accueil.

## Extensions complémentaires conseillées (gratuites)

| Besoin (§2.3) | Extension |
|---|---|
| Fil Instagram en page d'accueil | Smash Balloon Social Photo Feed |
| SEO | Yoast SEO ou Rank Math |
| Cache | WP Super Cache |
| Sécurité | Wordfence |
| Sauvegarde quotidienne | UpdraftPlus |
| Bandeau cookies (RGPD) | Complianz |
| Envoi fiable des e-mails depuis OVH | WP Mail SMTP |

La carte, les galeries, les formulaires et l'espace membres sont gérés par l'extension CIOFF : **pas besoin** de WP Maps Pro, Gravity Forms, Ultimate Member ni Envira.

## Mise en ligne sur OVH

Envoyer les deux dossiers par FTP dans `wp-content/`, puis activer l'extension et le thème. Rien d'autre à configurer côté serveur.
