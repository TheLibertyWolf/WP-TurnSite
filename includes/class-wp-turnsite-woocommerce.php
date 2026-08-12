<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WP_TurnSite_WooCommerce
{
    public static function boot(): void
    {
        add_action('woocommerce_login_form', [self::class, 'render_login']);
        add_action('woocommerce_register_form', [self::class, 'render_registration']);
        add_action('woocommerce_lostpassword_form', [self::class, 'render_lostpassword']);
        add_filter('woocommerce_process_login_errors', [self::class, 'validate_login'], 5, 3);
        add_filter('woocommerce_registration_errors', [self::class, 'validate_registration'], 5, 3);
        add_action('woocommerce_review_order_before_submit', [self::class, 'render_checkout']);
        add_action('woocommerce_after_checkout_validation', [self::class, 'validate_checkout'], 5, 2);
    }

    public static function render_login(): void
    {
        WP_TurnSite::render_widget('woo_login', 'wp-turnsite-woo-login-response');
    }

    public static function render_registration(): void
    {
        WP_TurnSite::render_widget('woo_registration', 'wp-turnsite-woo-registration-response');
    }

    public static function render_lostpassword(): void
    {
        WP_TurnSite::render_widget('fr_lostpassword');
    }

    public static function validate_login(WP_Error $errors, string $username, string $password): WP_Error
    {
        $result = WP_TurnSite::verify('woo_login', 'wp-turnsite-woo-login-response');
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }
        return $errors;
    }

    public static function validate_registration(WP_Error $errors, string $username, string $email): WP_Error
    {
        $result = WP_TurnSite::verify('woo_registration', 'wp-turnsite-woo-registration-response');
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }
        return $errors;
    }

    public static function render_checkout(): void
    {
        WP_TurnSite::render_widget('woo_checkout', 'wp-turnsite-woo-checkout-response');
    }

    public static function validate_checkout(array $data, WP_Error $errors): void
    {
        $result = WP_TurnSite::verify('woo_checkout', 'wp-turnsite-woo-checkout-response');
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }
    }
}
