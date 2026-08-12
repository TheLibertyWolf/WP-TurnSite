<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WP_TurnSite_Comments
{
    public static function boot(): void
    {
        add_action('comment_form', [self::class, 'render_widget']);
        add_action('pre_comment_on_post', [self::class, 'validate_comment']);
    }

    public static function render_widget(): void
    {
        if (!WP_TurnSite::action_enabled('wp_comment')) {
            return;
        }

        WP_TurnSite::render_widget('wp_comment', 'wp-turnsite-comment-response');
    }

    public static function validate_comment(): void
    {
        if (!WP_TurnSite::action_enabled('wp_comment')) {
            return;
        }

        $result = WP_TurnSite::verify('wp_comment', 'wp-turnsite-comment-response');
        if (is_wp_error($result)) {
            wp_die(
                esc_html($result->get_error_message()),
                esc_html__('Commentaire refusé', 'wp-turnsite'),
                ['response' => 403, 'back_link' => true]
            );
        }
    }
}
