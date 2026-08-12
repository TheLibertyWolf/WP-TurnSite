# WP TurnSite

WP TurnSite ajoute Cloudflare Turnstile aux formulaires sensibles de WordPress avec validation obligatoire côté serveur.

[![Version](https://img.shields.io/badge/version-1.2.1-2271b1)](https://github.com/TheLibertyWolf/WP-TurnSite/releases)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.1-777bb4)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-46b450)](LICENSE)
[![Languages](https://img.shields.io/badge/languages-FR%20%7C%20EN%20%7C%20DE%20%7C%20IT%20%7C%20ES%20%7C%20PT-f48120)](#langues)

## Fonctionnalités

- protection de la connexion, du mot de passe perdu et de l’inscription ;
- validation obligatoire côté serveur auprès de Cloudflare ;
- contrôle de l’action et du nom d’hôte retournés par Cloudflare ;
- protection indépendante de la connexion et du mot de passe perdu ;
- thèmes clair, sombre ou automatique ;
- tailles normale, compacte ou flexible ;
- échelle réglable de 75 à 100 % pour les tailles fixes ;
- clé secrète masquée dans l’administration et absente du HTML public ;
- affichage volontaire de la clé par un bouton œil protégé par une nonce AJAX ;
- test de la clé secrète depuis le menu d’administration ;
- page placée sous `Réglages → WP TurnSite` et protégée par `manage_wp_turnsite` ;
- échec fermé en cas d’erreur Cloudflare lorsqu’une protection est configurée.
- interface traduite en français, anglais, allemand, italien, espagnol et portugais.
- commentaires WordPress et avis produits WooCommerce ;
- formulaires classiques WooCommerce : connexion, inscription, mot de passe perdu et commande ;
- inscription d’utilisateurs et de sites WordPress Multisite.

## Modules 1.2

Chaque module est activable séparément dans `Réglages → WP TurnSite`.

| Module | Protection |
|---|---|
| WordPress | Connexion, mot de passe perdu et inscription |
| Commentaires | Commentaires natifs et avis produits WooCommerce |
| WooCommerce | Formulaires classiques du compte et checkout classique |
| Multisite | Inscription d’un utilisateur et création d’un site via `wp-signup.php` |

Le checkout WooCommerce basé sur les **Checkout Blocks** utilise une API JavaScript différente et n’est pas couvert par le module checkout classique de la version 1.2.

## Langues

WP TurnSite suit automatiquement la langue WordPress du site ou du profil administrateur. Les catalogues `.po` et `.mo` inclus couvrent :

- français (langue source) ;
- anglais (`en_US`) ;
- allemand (`de_DE`) ;
- italien (`it_IT`) ;
- espagnol (`es_ES`) ;
- portugais (`pt_PT`).

## Captures d’écran

### Configuration dans WordPress

[![Écran de configuration de WP TurnSite](https://i.ibb.co/fVnT3Zrp/Capture-d-e-cran-2026-08-13-a-00-26-12.png)](https://ibb.co/twx10nYs)

### Widget Turnstile sur la page de connexion

[![Widget WP TurnSite sur la connexion WordPress](https://i.ibb.co/bj8XB2Cf/Capture-d-e-cran-2026-08-13-a-00-26-58.png)](https://ibb.co/ZRkx8YFZ)

## Installation

1. Copier le dossier `wp-turnsite` dans `wp-content/plugins/`.
2. Activer **WP TurnSite**.
3. Ouvrir **Réglages → WP TurnSite** dans l’administration.
4. Saisir la clé de site, la clé secrète et le nom d’hôte autorisé.
5. Enregistrer puis tester la clé secrète.

Le menu d’administration contient un accès direct à la création des widgets Cloudflare et rappelle les étapes de configuration.

L’enregistrement des réglages utilise la Settings API et sa protection anti-CSRF. La clé secrète n’est récupérée qu’au clic sur l’œil, via une requête authentifiée distincte.

La capability `manage_wp_turnsite` est attribuée automatiquement au rôle Administrateur. Elle peut être déléguée finement avec un gestionnaire de rôles sans accorder la gestion générale des options WordPress.

Cloudflare doit autoriser le domaine utilisé par le site. Le serveur doit pouvoir joindre `https://challenges.cloudflare.com` en HTTPS.

## Sécurité

Les jetons sont validés côté serveur. La clé secrète n’est jamais rendue dans les pages publiques. Pour signaler une vulnérabilité, contactez SAS Jessy System via [jessysystem.com](https://jessysystem.com).

Consultez également [SECURITY.md](SECURITY.md) avant de signaler publiquement une vulnérabilité.

## Contribution et support

- [Guide de contribution](CONTRIBUTING.md)
- [Support](SUPPORT.md)
- [Code de conduite](CODE_OF_CONDUCT.md)

## Auteur

SAS Jessy System — [https://jessysystem.com](https://jessysystem.com)

## Licence

GPL-2.0-or-later. Voir `LICENSE`.
