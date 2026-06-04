<?php
namespace Dynamic_Post_Type_Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Admin_Bar;

class Menu_Handler {
    private array $panel_items = [];

    public function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'add_post_type_menu' ], 71 );
        add_action( 'admin_footer', [ $this, 'render_panel' ] );
        add_action( 'wp_footer', [ $this, 'render_panel' ] );
    }

    public function add_post_type_menu( WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! $this->can_view_menu() ) {
            return;
        }

        $settings = Admin_Settings::get_options();
        $icon_url = DPTM_PLUGIN_URL . 'assets/img/icons/note-edit-stroke-standard.svg';

        $wp_admin_bar->add_node( [
            'id'    => 'post-types',
            'title' => sprintf(
                '<span class="dptm-admin-bar-title"><img class="dptm-admin-bar-title__icon" src="%s" alt="" aria-hidden="true"><span>%s</span><span class="dptm-admin-bar-title__chevron" aria-hidden="true"></span></span>',
                esc_url( $icon_url ),
                esc_html__( 'Content Types', 'dynamic-post-type-menu' )
            ),
            'href'  => '#dptm-content-types-panel',
            'meta'  => [
                'class' => 'dptm-admin-bar-trigger',
                'title' => __( 'Open Content Types panel', 'dynamic-post-type-menu' ),
            ],
        ] );

        $this->panel_items = $this->build_panel_items( $settings );
    }

    public function render_panel(): void {
        if ( ! is_user_logged_in() || ! is_admin_bar_showing() || ! $this->can_view_menu() ) {
            return;
        }

        if ( empty( $this->panel_items ) ) {
            $settings = Admin_Settings::get_options();
            $this->panel_items = $this->build_panel_items( $settings );
        }

        if ( empty( $this->panel_items ) ) {
            return;
        }
        ?>
        <div id="dptm-content-types-panel" class="dptm-panel" data-dptm-panel hidden>
            <div class="dptm-panel__main" aria-label="<?php echo esc_attr__( 'Content Types', 'dynamic-post-type-menu' ); ?>">
                <div class="dptm-panel__types" role="listbox" aria-label="<?php echo esc_attr__( 'Content Types', 'dynamic-post-type-menu' ); ?>">
                    <?php foreach ( $this->panel_items as $item ) : ?>
                        <button
                            type="button"
                            class="dptm-panel__type"
                            data-dptm-type="<?php echo esc_attr( $item['id'] ); ?>"
                            aria-selected="false"
                            role="option"
                        >
                            <?php echo $this->get_icon_markup( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <span class="dptm-panel__label"><?php echo esc_html( $item['label'] ); ?></span>
                            <span class="dptm-panel__arrow" aria-hidden="true"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="dptm-panel__actions-wrap">
                    <?php foreach ( $this->panel_items as $item ) : ?>
                        <div
                            class="dptm-panel__actions"
                            data-dptm-actions="<?php echo esc_attr( $item['id'] ); ?>"
                            hidden
                        >
                            <?php foreach ( $item['actions'] as $action ) : ?>
                                <div class="dptm-panel__action-row">
                                    <a class="dptm-panel__action" href="<?php echo esc_url( $action['url'] ); ?>">
                                        <?php echo $this->get_action_icon_markup( $action['icon'] ?? 'stack' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <span><?php echo esc_html( $action['label'] ); ?></span>
                                    </a>
                                    <a class="dptm-panel__external" href="<?php echo esc_url( $action['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( __( 'Open %s in a new window', 'dynamic-post-type-menu' ), $action['label'] ) ); ?>">
                                        <span class="sr-only"><?php esc_html_e( 'Open in New Window', 'dynamic-post-type-menu' ); ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="dptm-panel__settings-link" href="<?php echo esc_url( admin_url( 'options-general.php?page=dynamic-post-type-menu' ) ); ?>">
                    <span class="dptm-panel__action-icon dptm-panel__action-icon--settings" aria-hidden="true"></span>
                    <span class="dptm-panel__label"><?php esc_html_e( 'Plugin Settings', 'dynamic-post-type-menu' ); ?></span>
                </a>
            </div>
        </div>
        <?php
    }

    private function can_view_menu(): bool {
        return current_user_can( 'edit_posts' ) || current_user_can( 'edit_others_posts' );
    }

    private function build_panel_items( array $settings ): array {
        $items = [];
        $post_types = $this->get_enabled_post_types( $settings );
        [ $priority_post_types, $other_post_types ] = $this->categorize_post_types( $post_types );

        foreach ( $priority_post_types as $post_type ) {
            $items[] = $this->build_post_type_panel_item( $post_type );
        }

        uasort(
            $other_post_types,
            static fn( $a, $b ) => strcmp( (string) $a->labels->name, (string) $b->labels->name )
        );

        foreach ( $other_post_types as $post_type ) {
            $items[] = $this->build_post_type_panel_item( $post_type );
        }

        if ( ! empty( $settings['enabled_features']['acf'] ) && $this->is_acf_active() ) {
            $items[] = $this->build_integration_item( 'acf', 'ACF', 'dashicons-admin-generic', [
                [ 'label' => 'Field Groups', 'url' => admin_url( 'edit.php?post_type=acf-field-group' ), 'icon' => 'stack' ],
                [ 'label' => 'Post Types', 'url' => admin_url( 'edit.php?post_type=acf-post-type' ), 'icon' => 'grid' ],
                [ 'label' => 'Taxonomies', 'url' => admin_url( 'edit.php?post_type=acf-taxonomy' ), 'icon' => 'folder' ],
                [ 'label' => 'Options Pages', 'url' => admin_url( 'edit.php?post_type=acf-ui-options-page' ), 'icon' => 'settings' ],
            ] );
        }

        if ( ! empty( $settings['enabled_features']['metabox'] ) && $this->is_metabox_active() ) {
            $items[] = $this->build_integration_item( 'metabox', 'Meta Box', 'dashicons-admin-generic', [
                [ 'label' => 'Post Types', 'url' => admin_url( 'edit.php?post_type=mb-post-type' ), 'icon' => 'grid' ],
                [ 'label' => 'Taxonomies', 'url' => admin_url( 'edit.php?post_type=mb-taxonomy' ), 'icon' => 'folder' ],
                [ 'label' => 'Custom Fields', 'url' => admin_url( 'edit.php?post_type=meta-box' ), 'icon' => 'stack' ],
                [ 'label' => 'Settings Pages', 'url' => admin_url( 'edit.php?post_type=mb-settings-page' ), 'icon' => 'settings' ],
                [ 'label' => 'Relationships', 'url' => admin_url( 'edit.php?post_type=mb-relationship' ), 'icon' => 'link' ],
            ] );
        }

        if ( ! empty( $settings['enabled_features']['wpcodebox'] ) && $this->is_wpcodebox_active() ) {
            $items[] = $this->build_integration_item( 'wpcodebox', 'WPCodeBox', 'dashicons-editor-code', [
                [ 'label' => 'WPCodeBox', 'url' => admin_url( 'admin.php?page=wpcodebox2' ), 'icon' => 'cube' ],
            ] );
        }

        return array_values( array_filter( $items ) );
    }

    private function build_post_type_panel_item( object $post_type ): array {
        $slug  = sanitize_key( $post_type->name );
        $label = (string) $post_type->labels->name;
        $actions = [];

        if ( current_user_can( 'edit_others_posts' ) ) {
            $actions[] = [ 'label' => sprintf( __( 'All %s', 'dynamic-post-type-menu' ), $label ), 'url' => $this->get_all_items_url( $slug ), 'icon' => 'stack' ];
            $actions[] = [ 'label' => sprintf( __( 'My %s', 'dynamic-post-type-menu' ), $label ), 'url' => $this->get_my_items_url( $slug ), 'icon' => 'user' ];
        } else {
            $actions[] = [ 'label' => sprintf( __( 'My %s', 'dynamic-post-type-menu' ), $label ), 'url' => $this->get_my_items_url( $slug ), 'icon' => 'user' ];
        }

        if ( $this->can_create_post_type( $post_type ) ) {
            $actions[] = [ 'label' => $this->get_add_new_label( $post_type->name, $label ), 'url' => $this->get_add_new_url( $slug ), 'icon' => 'plus' ];
        }

        return [
            'id'        => 'post-type-' . $slug,
            'label'     => $label,
            'type'      => 'post-type',
            'name'      => $post_type->name,
            'menu_icon' => $post_type->menu_icon ?? '',
            'actions'   => $actions,
        ];
    }

    private function build_integration_item( string $id, string $label, string $icon, array $actions ): array {
        return [
            'id'        => 'integration-' . sanitize_key( $id ),
            'label'     => $label,
            'type'      => 'integration',
            'menu_icon' => $icon,
            'actions'   => $actions,
        ];
    }

    private function get_enabled_post_types( array $settings ): array {
        $post_types    = Admin_Settings::get_selectable_post_types( 'objects' );
        $enabled_slugs = is_array( $settings['enabled_post_types'] ?? null ) ? $settings['enabled_post_types'] : [];

        foreach ( $post_types as $slug => $post_type ) {
            if ( ! in_array( $slug, $enabled_slugs, true ) ) {
                unset( $post_types[ $slug ] );
                continue;
            }

            if ( ! empty( $settings['remove_default_posts'] ) && 'post' === $slug ) {
                unset( $post_types[ $slug ] );
            }
        }

        return $post_types;
    }

    private function categorize_post_types( array $post_types ): array {
        $priority_post_types = [];
        $other_post_types    = [];

        foreach ( $post_types as $post_type ) {
            $post_type->labels->name = match ( $post_type->name ) {
                'post'            => __( 'Articles', 'dynamic-post-type-menu' ),
                'page'            => __( 'Pages', 'dynamic-post-type-menu' ),
                'bricks_template' => __( 'Bricks Templates', 'dynamic-post-type-menu' ),
                'attachment'      => __( 'Media', 'dynamic-post-type-menu' ),
                default           => $post_type->labels->name,
            };

            if ( in_array( $post_type->name, [ 'page', 'post', 'bricks_template' ], true ) ) {
                $priority_post_types[ $post_type->name ] = $post_type;
                continue;
            }

            $other_post_types[ $post_type->name ] = $post_type;
        }

        return [ $priority_post_types, $other_post_types ];
    }


    private function get_all_items_url( string $slug ): string {
        if ( 'post' === $slug ) {
            return admin_url( 'edit.php' );
        }

        if ( 'attachment' === $slug ) {
            return admin_url( 'upload.php' );
        }

        return admin_url( 'edit.php?post_type=' . $slug );
    }

    private function get_my_items_url( string $slug ): string {
        if ( 'post' === $slug ) {
            return admin_url( 'edit.php?author=' . get_current_user_id() );
        }

        if ( 'attachment' === $slug ) {
            return admin_url( 'upload.php?author=' . get_current_user_id() );
        }

        return admin_url( 'edit.php?post_type=' . $slug . '&author=' . get_current_user_id() );
    }

    private function get_add_new_url( string $slug ): string {
        if ( 'attachment' === $slug ) {
            return admin_url( 'media-new.php' );
        }

        if ( 'post' === $slug ) {
            return admin_url( 'post-new.php' );
        }

        return admin_url( 'post-new.php?post_type=' . $slug );
    }

    private function can_create_post_type( object $post_type ): bool {
        if ( 'attachment' === $post_type->name ) {
            return current_user_can( 'upload_files' );
        }

        $capability = $post_type->cap->create_posts ?? 'edit_posts';
        return current_user_can( $capability );
    }

    private function get_add_new_label( string $post_type_name, string $plural_label ): string {
        return match ( $post_type_name ) {
            'attachment'      => __( 'Add New Media', 'dynamic-post-type-menu' ),
            'bricks_template' => __( 'Add New Bricks Template', 'dynamic-post-type-menu' ),
            default           => sprintf( __( 'Add New %s', 'dynamic-post-type-menu' ), $plural_label ),
        };
    }

    private function get_icon_markup( array $item ): string {
        $icon = (string) ( $item['menu_icon'] ?? '' );

        if ( 0 === strpos( $icon, 'dashicons-' ) ) {
            return sprintf( '<span class="dptm-panel__icon dashicons %s" aria-hidden="true"></span>', esc_attr( $icon ) );
        }

        if ( filter_var( $icon, FILTER_VALIDATE_URL ) || 0 === strpos( $icon, 'data:' ) ) {
            return sprintf( '<span class="dptm-panel__icon dptm-panel__icon--image" aria-hidden="true" style="--dptm-icon-url: url(%s);"></span>', esc_url( $icon ) );
        }

        $fallback = match ( $item['name'] ?? '' ) {
            'page'       => 'dashicons-admin-page',
            'post'       => 'dashicons-media-document',
            'attachment' => 'dashicons-admin-media',
            default      => 'dashicons-media-default',
        };

        if ( 'integration' === ( $item['type'] ?? '' ) && ! empty( $icon ) ) {
            $fallback = $icon;
        }

        return sprintf( '<span class="dptm-panel__icon dashicons %s" aria-hidden="true"></span>', esc_attr( $fallback ) );
    }

    private function get_action_icon_markup( string $icon ): string {
        return sprintf( '<span class="dptm-panel__action-icon dptm-panel__action-icon--%s" aria-hidden="true"></span>', esc_attr( sanitize_html_class( $icon ) ) );
    }

    private function is_wpcodebox_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ( [ 'wpcodebox2/wpcodebox2.php', 'wpcodebox2/wpcodebox.php', 'wpcodebox/wpcodebox2.php', 'wpcodebox/wpcodebox.php' ] as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }

    private function is_acf_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ( [ 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ] as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }

    private function is_metabox_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ( [ 'meta-box/meta-box.php', 'meta-box-aio/meta-box-aio.php' ] as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }
}
