=== WP TurnSite ===
Contributors: jessysystem
Tags: cloudflare, turnstile, login, security, captcha
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cloudflare Turnstile pour la connexion, la récupération de mot de passe et l’inscription WordPress.

== Description ==

WP TurnSite protège les formulaires sensibles avec une validation Cloudflare obligatoire côté serveur. Il vérifie également l’action et le nom d’hôte associés au jeton.

La configuration se fait depuis le menu WP TurnSite de l’administration WordPress.

== Installation ==

1. Téléverser le dossier du plugin dans `/wp-content/plugins/`.
2. Activer le plugin.
3. Ouvrir WP TurnSite dans l’administration.
4. Enregistrer les clés Cloudflare et le nom d’hôte.

== Changelog ==

= 1.1.0 =
* Traductions anglaise, allemande, italienne, espagnole et portugaise.
* Choix du thème, de la taille et de l’échelle du widget.
* Interface d’administration en deux colonnes et aptitude délégable.

= 1.0.0 =
* Première version publique.
* Protection de la connexion, du mot de passe perdu et de l’inscription.
* Validation de l’action et du nom d’hôte.
* Page de configuration et test de la clé secrète.
* Guide intégré avec accès direct au tableau de bord et à la documentation Cloudflare.
* Clé secrète masquée, avec affichage volontaire protégé par nonce.
* Capability dédiée `manage_wp_turnsite`, attribuée aux administrateurs et délégable par un gestionnaire de rôles.
* Choix du thème clair, sombre ou automatique et de la taille normale, compacte ou flexible.
* Échelle réglable de 75 à 100 % pour adapter précisément le widget aux formulaires WordPress.
