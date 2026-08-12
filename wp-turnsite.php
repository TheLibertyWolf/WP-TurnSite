<?php
/**
 * Plugin Name: WP TurnSite
 * Plugin URI: https://jessysystem.com
 * Description: Ajoute Cloudflare Turnstile à la connexion et à la récupération de mot de passe WordPress.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: SAS JESSY SYSTEM
 * Author URI: https://jessysystem.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-turnsite
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_TURNSITE_VERSION', '1.0.0');
define('WP_TURNSITE_FILE', __FILE__);
define('WP_TURNSITE_DIR', plugin_dir_path(__FILE__));

require_once WP_TURNSITE_DIR . 'includes/class-wp-turnsite.php';

register_activation_hook(__FILE__, ['WP_TurnSite', 'activate']);
WP_TurnSite::boot();
