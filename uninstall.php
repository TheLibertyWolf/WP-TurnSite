<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('turnsite_for_wordpress_settings');
delete_option('turnsite_for_wordpress_secret');
delete_option('wp_turnsite_version');

foreach (wp_roles()->roles as $role_name => $details) {
    $role = get_role($role_name);
    if ($role) {
        $role->remove_cap('manage_wp_turnsite');
    }
}
