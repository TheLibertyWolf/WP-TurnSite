<?php

if (!defined('ABSPATH')) {
    exit;
}

final class WP_TurnSite
{
    private const SETTINGS_OPTION = 'turnsite_for_wordpress_settings';
    private const SECRET_OPTION = 'turnsite_for_wordpress_secret';
    private const SETTINGS_GROUP = 'turnsite_for_wordpress';
    private const MENU_SLUG = 'wp-turnsite';
    private const ADMIN_CAPABILITY = 'manage_wp_turnsite';
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const SECRET_MASK = '••••••••••••••••';

    private static array $verification_cache = [];
    private static string $settings_page_hook = '';

    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'load_textdomain']);
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_init', [self::class, 'maybe_upgrade']);
        add_filter('option_page_capability_' . self::SETTINGS_GROUP, [self::class, 'settings_capability']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('admin_notices', [self::class, 'configuration_notice']);
        add_action('admin_post_turnsite_test_configuration', [self::class, 'test_configuration']);
        add_action('wp_ajax_wp_turnsite_reveal_secret', [self::class, 'reveal_secret']);

        add_action('login_enqueue_scripts', [self::class, 'enqueue_script']);
        add_filter('script_loader_tag', [self::class, 'make_script_async'], 10, 2);
        add_action('login_form', [self::class, 'render_login_widget']);
        add_action('lostpassword_form', [self::class, 'render_lostpassword_widget']);
        add_action('register_form', [self::class, 'render_registration_widget']);

        add_filter('authenticate', [self::class, 'protect_login'], 5, 3);
        add_filter('authenticate', [self::class, 'protect_login'], 100, 3);
        add_action('lostpassword_post', [self::class, 'protect_lostpassword'], 5, 1);
        add_filter('registration_errors', [self::class, 'protect_registration'], 5, 3);
    }

    public static function activate(): void
    {
        self::grant_administrator_capability();

        if (get_option(self::SETTINGS_OPTION, null) === null) {
            add_option(self::SETTINGS_OPTION, self::defaults(), '', false);
        }

        if (get_option(self::SECRET_OPTION, null) === null) {
            add_option(self::SECRET_OPTION, '', '', false);
        }

        update_option('wp_turnsite_version', WP_TURNSITE_VERSION, false);
    }

    private static function grant_administrator_capability(): void
    {
        $administrator = get_role('administrator');
        if ($administrator && !$administrator->has_cap(self::ADMIN_CAPABILITY)) {
            $administrator->add_cap(self::ADMIN_CAPABILITY);
        }
    }

    public static function maybe_upgrade(): void
    {
        if (get_option('wp_turnsite_version') === WP_TURNSITE_VERSION) {
            return;
        }

        self::grant_administrator_capability();
        update_option('wp_turnsite_version', WP_TURNSITE_VERSION, false);
    }

    public static function settings_capability(): string
    {
        return self::ADMIN_CAPABILITY;
    }

    public static function load_textdomain(): void
    {
        load_plugin_textdomain(
            'wp-turnsite',
            false,
            dirname(plugin_basename(WP_TURNSITE_FILE)) . '/languages'
        );
    }

    private static function defaults(): array
    {
        return [
            'site_key' => '',
            'hostname' => strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST)),
            'protect_login' => '1',
            'protect_lostpassword' => '1',
            'protect_registration' => '1',
            'theme' => 'auto',
            'size' => 'normal',
            'scale' => '100',
        ];
    }

    private static function settings(): array
    {
        $settings = get_option(self::SETTINGS_OPTION, []);
        return wp_parse_args(is_array($settings) ? $settings : [], self::defaults());
    }

    private static function secret(): string
    {
        return trim((string) get_option(self::SECRET_OPTION, ''));
    }

    private static function is_configured(): bool
    {
        $settings = self::settings();
        return $settings['site_key'] !== '' && $settings['hostname'] !== '' && self::secret() !== '';
    }

    public static function add_admin_menu(): void
    {
        self::$settings_page_hook = (string) add_options_page(
            __('WP TurnSite', 'wp-turnsite'),
            __('WP TurnSite', 'wp-turnsite'),
            self::ADMIN_CAPABILITY,
            self::MENU_SLUG,
            [self::class, 'render_settings_page']
        );
    }

    public static function enqueue_admin_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== self::$settings_page_hook) {
            return;
        }

        wp_enqueue_style(
            'wp-turnsite-admin',
            plugin_dir_url(WP_TURNSITE_FILE) . 'assets/admin.css',
            [],
            WP_TURNSITE_VERSION
        );
        wp_enqueue_script(
            'wp-turnsite-admin',
            plugin_dir_url(WP_TURNSITE_FILE) . 'assets/admin.js',
            [],
            WP_TURNSITE_VERSION,
            true
        );
        wp_localize_script('wp-turnsite-admin', 'wpTurnSiteAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_turnsite_reveal_secret'),
            'revealLabel' => __('Afficher la clé secrète', 'wp-turnsite'),
            'hideLabel' => __('Masquer la clé secrète', 'wp-turnsite'),
            'errorMessage' => __('Impossible de récupérer la clé secrète.', 'wp-turnsite'),
        ]);
    }

    public static function register_settings(): void
    {
        register_setting(self::SETTINGS_GROUP, self::SETTINGS_OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize_settings'],
            'default' => self::defaults(),
        ]);

        register_setting(self::SETTINGS_GROUP, self::SECRET_OPTION, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_secret'],
            'default' => '',
        ]);
    }

    public static function sanitize_settings($input): array
    {
        $existing = self::settings();
        $input = is_array($input) ? $input : [];

        $site_key = isset($input['site_key']) ? trim(sanitize_text_field((string) $input['site_key'])) : '';
        if ($site_key !== '' && !preg_match('/^[A-Za-z0-9_-]{10,128}$/', $site_key)) {
            add_settings_error(self::SETTINGS_OPTION, 'invalid_site_key', __('Le format de la clé de site est invalide.', 'wp-turnsite'));
            $site_key = (string) $existing['site_key'];
        }

        $hostname = isset($input['hostname']) ? strtolower(trim(sanitize_text_field((string) $input['hostname']))) : '';
        $hostname = preg_replace('#^https?://#i', '', $hostname);
        $hostname = rtrim((string) $hostname, '/');
        if ($hostname !== '' && !preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $hostname)) {
            add_settings_error(self::SETTINGS_OPTION, 'invalid_hostname', __('Le nom d’hôte attendu est invalide.', 'wp-turnsite'));
            $hostname = (string) $existing['hostname'];
        }

        $theme = isset($input['theme']) ? sanitize_key((string) $input['theme']) : 'auto';
        if (!in_array($theme, ['auto', 'light', 'dark'], true)) {
            $theme = 'auto';
        }

        $size = isset($input['size']) ? sanitize_key((string) $input['size']) : 'normal';
        if (!in_array($size, ['normal', 'compact', 'flexible'], true)) {
            $size = 'normal';
        }

        $scale = isset($input['scale']) ? (string) absint($input['scale']) : '100';
        if (!in_array($scale, ['75', '80', '90', '100'], true)) {
            $scale = '100';
        }

        return [
            'site_key' => $site_key,
            'hostname' => $hostname,
            'protect_login' => empty($input['protect_login']) ? '0' : '1',
            'protect_lostpassword' => empty($input['protect_lostpassword']) ? '0' : '1',
            'protect_registration' => empty($input['protect_registration']) ? '0' : '1',
            'theme' => $theme,
            'size' => $size,
            'scale' => $scale,
        ];
    }

    public static function sanitize_secret($value): string
    {
        if (!empty($_POST['turnsite_clear_secret'])) {
            return '';
        }

        $value = trim(sanitize_text_field((string) $value));
        if ($value === '' || hash_equals(self::SECRET_MASK, $value)) {
            return self::secret();
        }

        if (!preg_match('/^[A-Za-z0-9_-]{10,255}$/', $value)) {
            add_settings_error(self::SECRET_OPTION, 'invalid_secret', __('Le format de la clé secrète est invalide.', 'wp-turnsite'));
            return self::secret();
        }

        return $value;
    }

    public static function render_settings_page(): void
    {
        if (!current_user_can(self::ADMIN_CAPABILITY)) {
            return;
        }

        $settings = self::settings();
        $secret_is_set = self::secret() !== '';
        $test_status = isset($_GET['turnsite_test']) ? sanitize_key((string) $_GET['turnsite_test']) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP TurnSite', 'wp-turnsite'); ?></h1>
            <p><?php esc_html_e('Protection Cloudflare Turnstile des formulaires sensibles de WordPress.', 'wp-turnsite'); ?></p>

            <?php if ($test_status === 'success') : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('La clé secrète a été acceptée par Cloudflare.', 'wp-turnsite'); ?></p></div>
            <?php elseif ($test_status === 'failure') : ?>
                <div class="notice notice-error"><p><?php esc_html_e('Cloudflare a refusé la clé secrète.', 'wp-turnsite'); ?></p></div>
            <?php elseif ($test_status === 'transport') : ?>
                <div class="notice notice-error"><p><?php esc_html_e('Impossible de joindre Cloudflare. Vérifiez la connectivité sortante.', 'wp-turnsite'); ?></p></div>
            <?php endif; ?>

            <div id="poststuff" class="wp-turnsite-poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
            <form method="post" action="options.php">
                <?php settings_fields(self::SETTINGS_GROUP); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="turnsite-site-key"><?php esc_html_e('Clé de site', 'wp-turnsite'); ?></label></th>
                        <td><input id="turnsite-site-key" class="regular-text code" type="text" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[site_key]" value="<?php echo esc_attr((string) $settings['site_key']); ?>" autocomplete="off" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="turnsite-secret"><?php esc_html_e('Clé secrète', 'wp-turnsite'); ?></label></th>
                        <td>
                            <span class="wp-turnsite-secret-field">
                                <input id="turnsite-secret" class="regular-text code" type="password" name="<?php echo esc_attr(self::SECRET_OPTION); ?>" value="<?php echo esc_attr($secret_is_set ? self::SECRET_MASK : ''); ?>" autocomplete="new-password" placeholder="<?php esc_attr_e('Saisir la clé secrète', 'wp-turnsite'); ?>" data-secret-configured="<?php echo $secret_is_set ? '1' : '0'; ?>">
                                <button type="button" class="button wp-turnsite-secret-toggle" aria-label="<?php esc_attr_e('Afficher la clé secrète', 'wp-turnsite'); ?>" aria-controls="turnsite-secret" data-revealed="0">
                                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                                </button>
                            </span>
                            <?php if ($secret_is_set) : ?>
                                <p><label><input type="checkbox" name="turnsite_clear_secret" value="1"> <?php esc_html_e('Supprimer la clé secrète enregistrée', 'wp-turnsite'); ?></label></p>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e('La clé secrète n’est jamais affichée dans l’administration ni envoyée au navigateur.', 'wp-turnsite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="turnsite-hostname"><?php esc_html_e('Nom d’hôte attendu', 'wp-turnsite'); ?></label></th>
                        <td>
                            <input id="turnsite-hostname" class="regular-text code" type="text" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[hostname]" value="<?php echo esc_attr((string) $settings['hostname']); ?>" required>
                            <p class="description"><?php esc_html_e('La réponse Cloudflare est rejetée si elle concerne un autre domaine.', 'wp-turnsite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Formulaires protégés', 'wp-turnsite'); ?></th>
                        <td>
                            <p><label><input type="checkbox" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[protect_login]" value="1" <?php checked($settings['protect_login'], '1'); ?>> <?php esc_html_e('Connexion', 'wp-turnsite'); ?></label></p>
                            <p><label><input type="checkbox" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[protect_lostpassword]" value="1" <?php checked($settings['protect_lostpassword'], '1'); ?>> <?php esc_html_e('Mot de passe perdu', 'wp-turnsite'); ?></label></p>
                            <p><label><input type="checkbox" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[protect_registration]" value="1" <?php checked($settings['protect_registration'], '1'); ?>> <?php esc_html_e('Inscription', 'wp-turnsite'); ?></label></p>
                            <p class="description"><?php esc_html_e('La protection de l’inscription ne réactive pas les inscriptions si WordPress les a désactivées.', 'wp-turnsite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="turnsite-theme"><?php esc_html_e('Apparence du widget', 'wp-turnsite'); ?></label></th>
                        <td>
                            <select id="turnsite-theme" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[theme]">
                                <option value="auto" <?php selected($settings['theme'], 'auto'); ?>><?php esc_html_e('Automatique (selon le navigateur)', 'wp-turnsite'); ?></option>
                                <option value="light" <?php selected($settings['theme'], 'light'); ?>><?php esc_html_e('Clair', 'wp-turnsite'); ?></option>
                                <option value="dark" <?php selected($settings['theme'], 'dark'); ?>><?php esc_html_e('Sombre', 'wp-turnsite'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="turnsite-size"><?php esc_html_e('Taille du widget', 'wp-turnsite'); ?></label></th>
                        <td>
                            <select id="turnsite-size" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[size]">
                                <option value="normal" <?php selected($settings['size'], 'normal'); ?>><?php esc_html_e('Normale — 300 × 65 px', 'wp-turnsite'); ?></option>
                                <option value="compact" <?php selected($settings['size'], 'compact'); ?>><?php esc_html_e('Compacte — 150 × 140 px', 'wp-turnsite'); ?></option>
                                <option value="flexible" <?php selected($settings['size'], 'flexible'); ?>><?php esc_html_e('Flexible — largeur du formulaire', 'wp-turnsite'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Les tailles normale et compacte sont centrées dans le formulaire.', 'wp-turnsite'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="turnsite-scale"><?php esc_html_e('Échelle du widget', 'wp-turnsite'); ?></label></th>
                        <td>
                            <select id="turnsite-scale" name="<?php echo esc_attr(self::SETTINGS_OPTION); ?>[scale]">
                                <option value="100" <?php selected($settings['scale'], '100'); ?>>100 %</option>
                                <option value="90" <?php selected($settings['scale'], '90'); ?>>90 %</option>
                                <option value="80" <?php selected($settings['scale'], '80'); ?>>80 %</option>
                                <option value="75" <?php selected($settings['scale'], '75'); ?>>75 %</option>
                            </select>
                            <p class="description"><?php esc_html_e('Appliquée aux tailles normale et compacte. La taille flexible utilise toujours toute la largeur disponible.', 'wp-turnsite'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <?php if ($secret_is_set) : ?>
                        <hr>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="turnsite_test_configuration">
                            <?php wp_nonce_field('turnsite_test_configuration'); ?>
                            <?php submit_button(__('Tester la clé secrète', 'wp-turnsite'), 'secondary', 'submit', false); ?>
                        </form>
            <?php endif; ?>
                    </div>

                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Sécurité de l’administration', 'wp-turnsite'); ?></h2></div>
                            <div class="inside wp-turnsite-security-status">
                                <ul>
                                    <li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e('Accès contrôlé par « manage_wp_turnsite ».', 'wp-turnsite'); ?></li>
                                    <li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e('Enregistrement protégé par nonce.', 'wp-turnsite'); ?></li>
                                    <li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e('Affichage du secret protégé par nonce AJAX.', 'wp-turnsite'); ?></li>
                                </ul>
                                <p class="description"><?php esc_html_e('Cette aptitude est attribuée aux administrateurs et peut être déléguée avec un gestionnaire de rôles.', 'wp-turnsite'); ?></p>
                            </div>
                        </div>

                        <div class="postbox">
                            <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Obtenir les clés Cloudflare', 'wp-turnsite'); ?></h2></div>
                            <div class="inside">
                                <ol>
                                    <li><?php esc_html_e('Ouvrez Turnstile puis choisissez « Add widget ».', 'wp-turnsite'); ?></li>
                                    <li><?php esc_html_e('Ajoutez le domaine dans les noms d’hôte autorisés.', 'wp-turnsite'); ?></li>
                                    <li><?php esc_html_e('Choisissez « Managed », créez le widget et copiez les deux clés.', 'wp-turnsite'); ?></li>
                                </ol>
                                <p><a class="button button-primary" href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ouvrir Cloudflare', 'wp-turnsite'); ?></a></p>
                                <p><a href="https://developers.cloudflare.com/turnstile/get-started/widget-management/dashboard/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Consulter la documentation Cloudflare', 'wp-turnsite'); ?></a></p>
                                <p class="description"><?php esc_html_e('La clé de site est publique. La clé secrète doit rester privée.', 'wp-turnsite'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
        <?php
    }

    public static function configuration_notice(): void
    {
        if (!current_user_can(self::ADMIN_CAPABILITY) || self::is_configured()) {
            return;
        }

        $url = admin_url('options-general.php?page=' . self::MENU_SLUG);
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            wp_kses_post(sprintf(
                __('WP TurnSite est actif mais incomplet. <a href="%s">Configurer les clés Cloudflare</a>.', 'wp-turnsite'),
                esc_url($url)
            ))
        );
    }

    public static function reveal_secret(): void
    {
        if (!current_user_can(self::ADMIN_CAPABILITY)) {
            wp_send_json_error(['message' => __('Accès refusé.', 'wp-turnsite')], 403);
        }

        check_ajax_referer('wp_turnsite_reveal_secret');
        $secret = self::secret();
        if ($secret === '') {
            wp_send_json_error(['message' => __('Aucune clé secrète enregistrée.', 'wp-turnsite')], 404);
        }

        wp_send_json_success(['secret' => $secret]);
    }

    public static function test_configuration(): void
    {
        if (!current_user_can(self::ADMIN_CAPABILITY)) {
            wp_die(esc_html__('Accès refusé.', 'wp-turnsite'), '', ['response' => 403]);
        }

        check_admin_referer('turnsite_test_configuration');
        $result = self::siteverify('turnsite-configuration-test');
        $status = 'transport';

        if (!is_wp_error($result)) {
            $codes = isset($result['error-codes']) && is_array($result['error-codes']) ? $result['error-codes'] : [];
            $status = in_array('invalid-input-secret', $codes, true) || in_array('missing-input-secret', $codes, true)
                ? 'failure'
                : 'success';
        }

        wp_safe_redirect(add_query_arg('turnsite_test', $status, admin_url('options-general.php?page=' . self::MENU_SLUG)));
        exit;
    }

    private static function requested_login_action(): string
    {
        return isset($_REQUEST['action']) ? sanitize_key((string) $_REQUEST['action']) : 'login';
    }

    private static function action_enabled(string $action): bool
    {
        if (!self::is_configured()) {
            return false;
        }

        $settings = self::settings();
        if ($action === 'fr_login') {
            return $settings['protect_login'] === '1';
        }

        if ($action === 'fr_lostpassword') {
            return $settings['protect_lostpassword'] === '1';
        }

        if ($action === 'fr_registration') {
            return $settings['protect_registration'] === '1';
        }

        return false;
    }

    public static function enqueue_script(): void
    {
        $action = self::requested_login_action();
        $enabled = match ($action) {
            'login' => self::action_enabled('fr_login'),
            'lostpassword', 'retrievepassword' => self::action_enabled('fr_lostpassword'),
            'register' => self::action_enabled('fr_registration'),
            default => false,
        };

        if (!$enabled) {
            return;
        }

        wp_enqueue_script(
            'turnsite-cloudflare',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [],
            null,
            true
        );
    }

    public static function make_script_async(string $tag, string $handle): string
    {
        if ($handle !== 'turnsite-cloudflare') {
            return $tag;
        }

        return str_replace(' src=', ' async defer src=', $tag);
    }

    private static function render_widget(string $action): void
    {
        if (!self::action_enabled($action)) {
            return;
        }

        $settings = self::settings();
        $size = (string) $settings['size'];
        $scale_percent = (int) $settings['scale'];
        $base_dimensions = $size === 'compact' ? [150, 140] : [300, 65];
        $scale = $size === 'flexible' ? 1.0 : $scale_percent / 100;
        $width = (int) round($base_dimensions[0] * $scale);
        $height = (int) round($base_dimensions[1] * $scale);
        $wrapper_style = $size === 'flexible'
            ? 'width:100%;height:auto'
            : sprintf('width:%dpx;height:%dpx', $width, $height);
        $widget_style = $scale === 1.0
            ? ''
            : sprintf('transform:scale(%.2F);transform-origin:top left', $scale);

        printf(
            '<div class="turnsite-widget-wrap turnsite-size-%4$s" style="%5$s"><div class="cf-turnstile turnsite-widget" style="%6$s" data-sitekey="%1$s" data-action="%2$s" data-theme="%3$s" data-size="%4$s"></div></div>',
            esc_attr((string) $settings['site_key']),
            esc_attr($action),
            esc_attr((string) $settings['theme']),
            esc_attr($size),
            esc_attr($wrapper_style),
            esc_attr($widget_style)
        );
        echo '<style>.turnsite-widget-wrap{margin:16px auto}.turnsite-size-flexible{margin-left:0;margin-right:0}</style>';
    }

    public static function render_login_widget(): void
    {
        self::render_widget('fr_login');
    }

    public static function render_lostpassword_widget(): void
    {
        self::render_widget('fr_lostpassword');
    }

    public static function render_registration_widget(): void
    {
        self::render_widget('fr_registration');
    }

    private static function is_post_request(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST';
    }

    private static function verification_error(): WP_Error
    {
        return new WP_Error(
            'turnsite_verification_failed',
            __('La vérification de sécurité a échoué. Veuillez recommencer.', 'wp-turnsite')
        );
    }

    private static function siteverify(string $token)
    {
        $body = [
            'secret' => self::secret(),
            'response' => $token,
            'idempotency_key' => wp_generate_uuid4(),
        ];

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        if (filter_var($remote_ip, FILTER_VALIDATE_IP)) {
            $body['remoteip'] = $remote_ip;
        }

        $response = wp_remote_post(self::VERIFY_URL, [
            'timeout' => 10,
            'sslverify' => true,
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('turnsite_http_error', __('Réponse Cloudflare inattendue.', 'wp-turnsite'));
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($payload)
            ? $payload
            : new WP_Error('turnsite_invalid_json', __('Réponse Cloudflare invalide.', 'wp-turnsite'));
    }

    private static function verify(string $expected_action)
    {
        if (array_key_exists($expected_action, self::$verification_cache)) {
            return self::$verification_cache[$expected_action];
        }

        if (!self::action_enabled($expected_action)) {
            return self::$verification_cache[$expected_action] = true;
        }

        $token = isset($_POST['cf-turnstile-response'])
            ? sanitize_text_field(wp_unslash((string) $_POST['cf-turnstile-response']))
            : '';

        if ($token === '' || strlen($token) > 2048) {
            return self::$verification_cache[$expected_action] = self::verification_error();
        }

        $payload = self::siteverify($token);
        if (is_wp_error($payload)) {
            error_log('WP TurnSite: Cloudflare verification transport error.');
            return self::$verification_cache[$expected_action] = self::verification_error();
        }

        $settings = self::settings();
        if (
            empty($payload['success'])
            || !hash_equals($expected_action, (string) ($payload['action'] ?? ''))
            || !hash_equals((string) $settings['hostname'], strtolower((string) ($payload['hostname'] ?? '')))
        ) {
            return self::$verification_cache[$expected_action] = self::verification_error();
        }

        return self::$verification_cache[$expected_action] = true;
    }

    public static function protect_login($user, string $username, string $password)
    {
        if (!self::is_post_request() || !isset($_POST['log'], $_POST['pwd'])) {
            return $user;
        }

        $result = self::verify('fr_login');
        return is_wp_error($result) ? $result : $user;
    }

    public static function protect_lostpassword(WP_Error $errors): void
    {
        if (!self::is_post_request()) {
            return;
        }

        $result = self::verify('fr_lostpassword');
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }
    }

    public static function protect_registration(WP_Error $errors, string $sanitized_user_login, string $user_email): WP_Error
    {
        if (!self::is_post_request()) {
            return $errors;
        }

        $result = self::verify('fr_registration');
        if (is_wp_error($result)) {
            $errors->add($result->get_error_code(), $result->get_error_message());
        }

        return $errors;
    }
}
