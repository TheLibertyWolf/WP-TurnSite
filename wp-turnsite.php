<?php
/**
 * Plugin Name: WP TurnSite
 * Plugin URI: https://github.com/TheLibertyWolf/WP-TurnSite
 * Description: Ajoute Cloudflare Turnstile à la connexion et à la récupération de mot de passe WordPress.
 * Version: 1.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: SAS Jessy System
 * Author URI: https://jessysystem.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-turnsite
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_TURNSITE_VERSION', '1.2.0');
define('WP_TURNSITE_FILE', __FILE__);
define('WP_TURNSITE_DIR', plugin_dir_path(__FILE__));

$wp_turnsite_modules = [
    'includes/class-wp-turnsite.php',
    'includes/class-wp-turnsite-comments.php',
    'includes/class-wp-turnsite-contact.php',
    'includes/class-wp-turnsite-woocommerce.php',
    'includes/class-wp-turnsite-multisite.php',
];

foreach ($wp_turnsite_modules as $wp_turnsite_module) {
    $wp_turnsite_module_path = WP_TURNSITE_DIR . $wp_turnsite_module;
    if (!is_readable($wp_turnsite_module_path)) {
        add_action('admin_notices', static function () use ($wp_turnsite_module): void {
            if (current_user_can('activate_plugins')) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html(sprintf('WP TurnSite: module unavailable (%s). Reinstall the plugin.', $wp_turnsite_module))
                );
            }
        });
        return;
    }
    require_once $wp_turnsite_module_path;
}

unset($wp_turnsite_modules, $wp_turnsite_module, $wp_turnsite_module_path);

register_activation_hook(__FILE__, ['WP_TurnSite', 'activate']);
WP_TurnSite::boot();
