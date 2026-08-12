<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WP_TurnSite_Multisite
{
    public static function boot(): void
    {
        add_action('signup_extra_fields', [self::class, 'render_user']);
        add_action('signup_blogform', [self::class, 'render_site']);
        add_filter('wpmu_validate_user_signup', [self::class, 'validate_user']);
        add_filter('wpmu_validate_blog_signup', [self::class, 'validate_site']);
    }

    public static function render_user(): void
    {
        WP_TurnSite::render_widget('wp_multisite_user', 'wp-turnsite-multisite-user-response');
    }

    public static function render_site(): void
    {
        WP_TurnSite::render_widget('wp_multisite_site', 'wp-turnsite-multisite-site-response');
    }

    public static function validate_user(array $result): array
    {
        if (!self::is_signup_post()) {
            return $result;
        }

        $stage = isset($_POST['stage']) ? sanitize_key((string) $_POST['stage']) : '';
        $action = $stage === 'validate-blog-signup' ? 'wp_multisite_site' : 'wp_multisite_user';
        $field = $action === 'wp_multisite_site' ? 'wp-turnsite-multisite-site-response' : 'wp-turnsite-multisite-user-response';
        return self::append_error($result, WP_TurnSite::verify($action, $field));
    }

    public static function validate_site(array $result): array
    {
        if (!self::is_signup_post()) {
            return $result;
        }
        return self::append_error($result, WP_TurnSite::verify('wp_multisite_site', 'wp-turnsite-multisite-site-response'));
    }

    private static function is_signup_post(): bool
    {
        return isset($_SERVER['REQUEST_METHOD'])
            && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST'
            && isset($_POST['stage']);
    }

    private static function append_error(array $result, $verification): array
    {
        if (is_wp_error($verification) && isset($result['errors']) && $result['errors'] instanceof WP_Error) {
            $result['errors']->add($verification->get_error_code(), $verification->get_error_message());
        }
        return $result;
    }
}
