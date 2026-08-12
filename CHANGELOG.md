# Changelog

Toutes les modifications notables de ce projet sont documentées ici.

## [1.2.0] - 2026-08-13

- Protection optionnelle des commentaires WordPress et avis produits WooCommerce.
- Formulaire de contact sécurisé via `[wp_turnsite_contact_form]` avec nonce, honeypot et destinataire configurable.
- Protection des formulaires classiques WooCommerce : connexion, inscription, mot de passe perdu et checkout.
- Protection des inscriptions d’utilisateurs et de sites WordPress Multisite.
- Actions et champs de réponse Turnstile distincts pour les formulaires présents sur une même page.
- Chargeur tolérant aux fichiers de modules absents ou illisibles.
- Documentation multilingue et fichiers communautaires GitHub enrichis.

## [1.1.0] - 2026-08-13

- Ajout des traductions anglaise, allemande, italienne, espagnole et portugaise.
- Ajout du choix de thème clair, sombre ou automatique.
- Ajout des tailles normale, compacte ou flexible.
- Ajout d’une échelle réglable de 75 à 100 % pour les widgets de taille fixe.
- Interface d’administration en deux colonnes avec aide Cloudflare.
- Capability dédiée `manage_wp_turnsite`, délégable avec un gestionnaire de rôles.
- Champ secret masqué et révélation volontaire sécurisée par nonce AJAX.
- Présentation harmonisée de SAS Jessy System et lien GitHub illustré.

## [1.0.0] - 2026-08-12

- Ajout de Cloudflare Turnstile à la connexion WordPress.
- Ajout de Turnstile au formulaire de mot de passe perdu.
- Ajout de Turnstile au formulaire d’inscription.
- Validation serveur, action et nom d’hôte.
- Menu d’administration pour les clés et les options.
- Test de la clé secrète depuis WordPress.
- Documentation, icône, procédure de désinstallation et licence.
