# WP TurnSite

WP TurnSite ajoute Cloudflare Turnstile aux formulaires de connexion et de récupération de mot de passe WordPress.

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

## Installation

1. Copier le dossier `wp-turnsite` dans `wp-content/plugins/`.
2. Activer **WP TurnSite**.
3. Ouvrir le menu **WP TurnSite** dans l’administration.
4. Saisir la clé de site, la clé secrète et le nom d’hôte autorisé.
5. Enregistrer puis tester la clé secrète.

Le menu d’administration contient un accès direct à la création des widgets Cloudflare et rappelle les étapes de configuration.

L’enregistrement des réglages utilise la Settings API et sa protection anti-CSRF. La clé secrète n’est récupérée qu’au clic sur l’œil, via une requête authentifiée distincte.

La capability `manage_wp_turnsite` est attribuée automatiquement au rôle Administrateur. Elle peut être déléguée finement avec un gestionnaire de rôles sans accorder la gestion générale des options WordPress.

Cloudflare doit autoriser le domaine utilisé par le site. Le serveur doit pouvoir joindre `https://challenges.cloudflare.com` en HTTPS.

## Sécurité

Les jetons sont validés côté serveur. La clé secrète n’est jamais rendue dans les pages publiques. Pour signaler une vulnérabilité, contactez SAS JESSY SYSTEM via [jessysystem.com](https://jessysystem.com).

## Auteur

SAS JESSY SYSTEM — [https://jessysystem.com](https://jessysystem.com)

## Licence

GPL-2.0-or-later. Voir `LICENSE`.
