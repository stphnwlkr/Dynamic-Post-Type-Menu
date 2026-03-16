<?php
namespace Dynamic_Post_Type_Menu;

/**
 * Admin settings and admin-only restrictions.
 */
class Admin_Settings {
    public const OPTION_KEY = 'dynamic_post_type_menu_options';

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_menu', [ $this, 'maybe_remove_posts_menu_page' ], 999 );
        add_action( 'admin_init', [ $this, 'maybe_block_default_posts_admin_screens' ], 999 );
        add_action( 'admin_bar_menu', [ $this, 'maybe_hide_wp_new_menu' ], 999 );
        add_action( 'wp_before_admin_bar_render', [ $this, 'maybe_hide_wp_new_menu_frontend' ], 999 );
    }

    public static function get_defaults(): array {
        return [
            'remove_default_posts' => 0,
            'hide_wp_new_menu'     => 0,
            'enabled_post_types'   => self::get_default_enabled_post_types(),
            'enabled_features'     => [
                'acf'       => 1,
                'metabox'   => 1,
                'wpcodebox' => 1,
            ],
        ];
    }


    private static function get_default_enabled_post_types(): array {
        return array_values( array_keys( self::get_selectable_post_types( 'names' ) ) );
    }

    public static function get_feature_post_type_slugs(): array {
        return [
            'acf-field-group',
            'acf-post-type',
            'acf-taxonomy',
            'acf-ui-options-page',
            'meta-box',
            'mb-post-type',
            'mb-taxonomy',
            'mb-settings-page',
            'mb-relationship',
            'mb-views',
        ];
    }

    public static function get_excluded_post_type_slugs(): array {
        return [
            'wp_block',
            'wp_navigation',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_font_family',
            'wp_font_face',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_changeset',
            'wp_pattern_category',
            'wp_template_part_area',
            'wf_collection_policy',
        ];
    }

    /**
     * Return post types that should be selectable/renderable in the Content Types menu.
     *
     * - Includes normal content post types that have a UI.
     * - Excludes internal/core utility types.
     * - Excludes feature-owned post types already handled by the feature toggles.
     */
    public static function get_selectable_post_types( string $output = 'objects' ): array {
        $post_types = get_post_types(
            [
                'show_ui' => true,
            ],
            'objects'
        );

        $excluded = array_fill_keys(
            array_merge( self::get_feature_post_type_slugs(), self::get_excluded_post_type_slugs() ),
            true
        );

        foreach ( $post_types as $slug => $post_type ) {
            $is_allowed_builtin = in_array( $slug, [ 'post', 'page', 'attachment' ], true );

            if ( isset( $excluded[ $slug ] ) ) {
                unset( $post_types[ $slug ] );
                continue;
            }

            if ( ! $is_allowed_builtin && ! empty( $post_type->_builtin ) ) {
                unset( $post_types[ $slug ] );
                continue;
            }
        }

        if ( 'names' === $output ) {
            return array_combine( array_keys( $post_types ), array_keys( $post_types ) );
        }

        return $post_types;
    }

    public static function get_options(): array {
        $saved    = get_option( self::OPTION_KEY, [] );
        $defaults = self::get_defaults();
        $options  = wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );

        $options['remove_default_posts'] = empty( $options['remove_default_posts'] ) ? 0 : 1;
        $options['hide_wp_new_menu']     = empty( $options['hide_wp_new_menu'] ) ? 0 : 1;
        $options['enabled_post_types']   = is_array( $options['enabled_post_types'] ) ? array_values( array_unique( array_map( 'sanitize_key', $options['enabled_post_types'] ) ) ) : [];
        $options['enabled_features']     = is_array( $options['enabled_features'] ) ? $options['enabled_features'] : [];

        foreach ( array_keys( $defaults['enabled_features'] ) as $feature_key ) {
            $options['enabled_features'][ $feature_key ] = empty( $options['enabled_features'][ $feature_key ] ) ? 0 : 1;
        }

        return $options;
    }

    public function register_settings(): void {
        register_setting(
            'dynamic_post_type_menu_settings',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_options' ],
                'default'           => self::get_defaults(),
            ]
        );
    }

    public function sanitize_options( $input ): array {
        $defaults   = self::get_defaults();
        $input      = is_array( $input ) ? $input : [];
        $post_types = self::get_selectable_post_types( 'names' );

        $sanitized = [
            'remove_default_posts' => empty( $input['remove_default_posts'] ) ? 0 : 1,
            'hide_wp_new_menu'     => empty( $input['hide_wp_new_menu'] ) ? 0 : 1,
            'enabled_post_types'   => [],
            'enabled_features'     => [],
        ];

        $enabled_post_types = isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] )
            ? array_map( 'sanitize_key', $input['enabled_post_types'] )
            : [];

        foreach ( $enabled_post_types as $post_type ) {
            if ( isset( $post_types[ $post_type ] ) ) {
                $sanitized['enabled_post_types'][] = $post_type;
            }
        }

        foreach ( array_keys( $defaults['enabled_features'] ) as $feature_key ) {
            $sanitized['enabled_features'][ $feature_key ] = empty( $input['enabled_features'][ $feature_key ] ) ? 0 : 1;
        }

        return $sanitized;
    }

    public function register_settings_page(): void {
        add_options_page(
            __( 'Dynamic Post Type Menu', 'dynamic-post-type-menu' ),
            __( 'Dynamic Post Type Menu', 'dynamic-post-type-menu' ),
            'manage_options',
            'dynamic-post-type-menu',
            [ $this, 'render_settings_page' ]
        );
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options    = self::get_options();
        $post_types = self::get_selectable_post_types( 'objects' );

        uasort(
            $post_types,
            static function ( $a, $b ) {
                return strcmp( (string) $a->labels->name, (string) $b->labels->name );
            }
        );
        ?>
        <div class="wrap dptm-settings">
            <h1><?php echo esc_html__( 'Dynamic Post Type Menu', 'dynamic-post-type-menu' ); ?></h1>
            <p><?php echo esc_html__( 'Control whether the default Posts post type is available, whether the WordPress + New menu is shown, and which items appear in the Content Types menu.', 'dynamic-post-type-menu' ); ?></p>

            <form action="options.php" method="post">
                <?php settings_fields( 'dynamic_post_type_menu_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Default Posts', 'dynamic-post-type-menu' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[remove_default_posts]" value="1" <?php checked( 1, $options['remove_default_posts'] ); ?>>
                                    <?php echo esc_html__( 'Remove the built-in Posts post type from the admin UI and block direct admin access to it.', 'dynamic-post-type-menu' ); ?>
                                </label>
                                <p class="description"><?php echo esc_html__( 'This hides the default Posts screens in wp-admin. It does not unregister the built-in post type from WordPress core.', 'dynamic-post-type-menu' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'WP + New menu', 'dynamic-post-type-menu' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[hide_wp_new_menu]" value="1" <?php checked( 1, $options['hide_wp_new_menu'] ); ?>>
                                    <?php echo esc_html__( 'Hide the WordPress + New admin bar menu.', 'dynamic-post-type-menu' ); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php echo esc_html__( 'Content Types menu: post types', 'dynamic-post-type-menu' ); ?></h2>
                <p><?php echo esc_html__( 'Choose which content post types appear in the Content Types admin bar menu. Plugin-managed items like ACF and Meta Box are controlled below under Features.', 'dynamic-post-type-menu' ); ?></p>
                <fieldset>
                    <?php foreach ( $post_types as $post_type ) : ?>
                        <?php
                        $label = $post_type->labels->name;
                        if ( 'bricks_template' === $post_type->name ) {
                            $label = __( 'Bricks Templates', 'dynamic-post-type-menu' );
                        } elseif ( 'attachment' === $post_type->name ) {
                            $label = __( 'Media', 'dynamic-post-type-menu' );
                        } elseif ( 'post' === $post_type->name ) {
                            $label = __( 'Posts', 'dynamic-post-type-menu' );
                        } elseif ( 'page' === $post_type->name ) {
                            $label = __( 'Pages', 'dynamic-post-type-menu' );
                        }

                        $is_checked = empty( $options['enabled_post_types'] ) || in_array( $post_type->name, $options['enabled_post_types'], true );
                        ?>
                        <label style="display:block; margin-block-end:8px;">
                            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( true, $is_checked ); ?>>
                            <?php echo esc_html( $label ); ?>
                            <code><?php echo esc_html( $post_type->name ); ?></code>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <h2><?php echo esc_html__( 'Content Types menu: features', 'dynamic-post-type-menu' ); ?></h2>
                <p><?php echo esc_html__( 'Choose which plugin-related items appear in the Content Types menu. All are enabled by default.', 'dynamic-post-type-menu' ); ?></p>
                <fieldset>
                    <?php
                    $features = [
                        'acf'       => __( 'ACF', 'dynamic-post-type-menu' ),
                        'metabox'   => __( 'Meta Box', 'dynamic-post-type-menu' ),
                        'wpcodebox' => __( 'WPCodeBox', 'dynamic-post-type-menu' ),
                    ];
                    ?>
                    <?php foreach ( $features as $feature_key => $feature_label ) : ?>
                        <label style="display:block; margin-block-end:8px;">
                            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled_features][<?php echo esc_attr( $feature_key ); ?>]" value="1" <?php checked( 1, $options['enabled_features'][ $feature_key ] ?? 0 ); ?>>
                            <?php echo esc_html( $feature_label ); ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function maybe_remove_posts_menu_page(): void {
        if ( ! is_admin() ) {
            return;
        }

        $options = self::get_options();

        if ( empty( $options['remove_default_posts'] ) ) {
            return;
        }

        remove_menu_page( 'edit.php' );
    }

    public function maybe_block_default_posts_admin_screens(): void {
        if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $options = self::get_options();

        if ( empty( $options['remove_default_posts'] ) ) {
            return;
        }

        global $pagenow;

        if ( 'edit.php' === $pagenow && empty( $_GET['post_type'] ) ) {
            wp_safe_redirect( admin_url() );
            exit;
        }

        if ( 'post-new.php' === $pagenow && empty( $_GET['post_type'] ) ) {
            wp_safe_redirect( admin_url() );
            exit;
        }

        if ( 'post.php' === $pagenow && ! empty( $_GET['post'] ) ) {
            $post_id = absint( $_GET['post'] );

            if ( $post_id > 0 && 'post' === get_post_type( $post_id ) ) {
                wp_safe_redirect( admin_url() );
                exit;
            }
        }
    }

    public function maybe_hide_wp_new_menu( $wp_admin_bar ): void {
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        $options = self::get_options();

        if ( ! empty( $options['hide_wp_new_menu'] ) ) {
            $wp_admin_bar->remove_node( 'new-content' );
            return;
        }

        if ( ! empty( $options['remove_default_posts'] ) ) {
            $wp_admin_bar->remove_node( 'new-post' );
        }
    }

    public function maybe_hide_wp_new_menu_frontend(): void {
        global $wp_admin_bar;

        if ( ! $wp_admin_bar instanceof \WP_Admin_Bar ) {
            return;
        }

        $this->maybe_hide_wp_new_menu( $wp_admin_bar );
    }
}
