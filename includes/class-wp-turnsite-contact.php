<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WP_TurnSite_Contact
{
    public static function boot(): void
    {
        add_shortcode('wp_turnsite_contact_form', [self::class, 'shortcode']);
        add_action('admin_post_wp_turnsite_contact', [self::class, 'process']);
        add_action('admin_post_nopriv_wp_turnsite_contact', [self::class, 'process']);
    }

    public static function shortcode(): string
    {
        if (!WP_TurnSite::action_enabled('wp_contact')) {
            return '';
        }

        WP_TurnSite::enqueue_public_script();
        $status = isset($_GET['wp_turnsite_contact']) ? sanitize_key((string) $_GET['wp_turnsite_contact']) : '';

        ob_start();
        if ($status === 'sent') {
            echo '<div class="wp-turnsite-contact-notice wp-turnsite-contact-success" role="status">' . esc_html__('Votre message a bien été envoyé.', 'wp-turnsite') . '</div>';
        } elseif ($status === 'error') {
            echo '<div class="wp-turnsite-contact-notice wp-turnsite-contact-error" role="alert">' . esc_html__('Le message n’a pas pu être envoyé. Vérifiez les champs et réessayez.', 'wp-turnsite') . '</div>';
        }
        ?>
        <form class="wp-turnsite-contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="wp_turnsite_contact">
            <?php wp_nonce_field('wp_turnsite_contact', 'wp_turnsite_contact_nonce'); ?>
            <p><label><?php esc_html_e('Nom', 'wp-turnsite'); ?><br><input type="text" name="wp_turnsite_name" autocomplete="name" required></label></p>
            <p><label><?php esc_html_e('Adresse e-mail', 'wp-turnsite'); ?><br><input type="email" name="wp_turnsite_email" autocomplete="email" required></label></p>
            <p><label><?php esc_html_e('Objet', 'wp-turnsite'); ?><br><input type="text" name="wp_turnsite_subject" maxlength="150" required></label></p>
            <p><label><?php esc_html_e('Message', 'wp-turnsite'); ?><br><textarea name="wp_turnsite_message" rows="7" maxlength="5000" required></textarea></label></p>
            <p class="wp-turnsite-honeypot" aria-hidden="true"><label>Website<input type="text" name="wp_turnsite_website" tabindex="-1" autocomplete="off"></label></p>
            <?php WP_TurnSite::render_widget('wp_contact', 'wp-turnsite-contact-response'); ?>
            <p><button type="submit"><?php esc_html_e('Envoyer', 'wp-turnsite'); ?></button></p>
        </form>
        <style>.wp-turnsite-contact-form input,.wp-turnsite-contact-form textarea{box-sizing:border-box;max-width:100%;width:100%}.wp-turnsite-honeypot{left:-10000px;position:absolute}</style>
        <?php
        return (string) ob_get_clean();
    }

    public static function process(): void
    {
        $referer = wp_get_referer() ?: home_url('/');
        $redirect = remove_query_arg('wp_turnsite_contact', $referer);

        if (
            !WP_TurnSite::action_enabled('wp_contact')
            || !isset($_POST['wp_turnsite_contact_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['wp_turnsite_contact_nonce'])), 'wp_turnsite_contact')
            || !empty($_POST['wp_turnsite_website'])
        ) {
            wp_safe_redirect(add_query_arg('wp_turnsite_contact', 'error', $redirect));
            exit;
        }

        $verification = WP_TurnSite::verify('wp_contact', 'wp-turnsite-contact-response');
        $name = isset($_POST['wp_turnsite_name']) ? sanitize_text_field(wp_unslash((string) $_POST['wp_turnsite_name'])) : '';
        $email = isset($_POST['wp_turnsite_email']) ? sanitize_email(wp_unslash((string) $_POST['wp_turnsite_email'])) : '';
        $subject = isset($_POST['wp_turnsite_subject']) ? sanitize_text_field(wp_unslash((string) $_POST['wp_turnsite_subject'])) : '';
        $message = isset($_POST['wp_turnsite_message']) ? sanitize_textarea_field(wp_unslash((string) $_POST['wp_turnsite_message'])) : '';
        $recipient = sanitize_email((string) WP_TurnSite::get_setting('contact_recipient', get_option('admin_email')));

        $sent = !is_wp_error($verification)
            && $name !== ''
            && is_email($email)
            && $subject !== ''
            && $message !== ''
            && $recipient !== ''
            && wp_mail(
                $recipient,
                sprintf('[WP TurnSite] %s', $subject),
                sprintf("%s <%s>\n\n%s", $name, $email, $message),
                ['Reply-To: ' . $name . ' <' . $email . '>']
            );

        wp_safe_redirect(add_query_arg('wp_turnsite_contact', $sent ? 'sent' : 'error', $redirect));
        exit;
    }
}
