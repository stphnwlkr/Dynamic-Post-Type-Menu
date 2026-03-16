<?php
namespace Dynamic_Post_Type_Menu;

use WP_Admin_Bar;

class Menu_Handler {
    public function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'add_post_type_menu' ], 71 );
    }

    public function add_post_type_menu( WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_others_posts' ) ) {
            return;
        }

        $settings = Admin_Settings::get_options();
        $icon_url = plugin_dir_url( __FILE__ ) . '../assets/img/icons/note-edit-stroke-standard.svg';

        $wp_admin_bar->add_node( [
            'id'    => 'post-types',
            'title' => sprintf(
                '<img src="%s" alt="" style="margin-right: 5px; height: 16px; vertical-align: middle;">%s',
                esc_url( $icon_url ),
                esc_html__( 'Content Types', 'dynamic-post-type-menu' )
            ),
            'href'  => false,
            'meta'  => [ 'class' => 'menupop' ],
        ] );

        $post_types = $this->get_enabled_post_types( $settings );
        [ $priority_post_types, $other_post_types ] = $this->categorize_post_types( $post_types );

        foreach ( $priority_post_types as $post_type ) {
            $this->add_role_based_menus( $wp_admin_bar, $post_type );
        }

        uasort(
            $other_post_types,
            static fn( $a, $b ) => strcmp( (string) $a->labels->name, (string) $b->labels->name )
        );

        foreach ( $other_post_types as $post_type ) {
            $this->add_role_based_menus( $wp_admin_bar, $post_type );
        }

        if ( ! empty( $settings['enabled_features']['acf'] ) ) {
            $this->add_acf_menu_item( $wp_admin_bar );
        }

        if ( ! empty( $settings['enabled_features']['metabox'] ) ) {
            $this->add_metabox_menu_item( $wp_admin_bar );
        }

        if ( ! empty( $settings['enabled_features']['wpcodebox'] ) ) {
            $this->add_wpcodebox_menu_item( $wp_admin_bar );
        }
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
                'post'            => __( 'Posts', 'dynamic-post-type-menu' ),
                'page'            => __( 'Pages', 'dynamic-post-type-menu' ),
                'bricks_template' => __( 'Bricks Templates', 'dynamic-post-type-menu' ),
                'attachment'      => __( 'Media', 'dynamic-post-type-menu' ),
                default           => $post_type->labels->name,
            };

            if ( in_array( $post_type->name, [ 'page', 'bricks_template' ], true ) ) {
                $priority_post_types[ $post_type->name ] = $post_type;
                continue;
            }

            if ( 'post' === $post_type->name ) {
                $priority_post_types[ $post_type->name ] = $post_type;
                continue;
            }

            $other_post_types[ $post_type->name ] = $post_type;
        }

        return [ $priority_post_types, $other_post_types ];
    }

    private function add_role_based_menus( WP_Admin_Bar $wp_admin_bar, object $post_type ): void {
        $post_type_slug = sanitize_html_class( $post_type->name );
        $plural_label   = esc_html( $post_type->labels->name );

        if ( current_user_can( 'edit_others_posts' ) ) {
            $this->add_all_my_and_new_submenus( $wp_admin_bar, $post_type_slug, $plural_label, $post_type->name );
        } elseif ( current_user_can( 'edit_posts' ) ) {
            $this->add_my_and_new_submenus( $wp_admin_bar, $post_type_slug, $plural_label, $post_type->name );
        }
    }

    private function add_all_my_and_new_submenus(
        WP_Admin_Bar $wp_admin_bar,
        string $post_type_slug,
        string $plural_label,
        string $post_type_name
    ): void {
        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'post-type-' . $post_type_slug,
            'title'  => $plural_label,
            'href'   => false,
        ] );

        $wp_admin_bar->add_node( [
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'all-' . $post_type_slug,
            'title'  => $this->get_link_markup(
                admin_url( "edit.php?post_type={$post_type_slug}" ),
                sprintf( __( 'All %s', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( 'Open All %s in a new window', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( '%s → Open All in a new tab', 'dynamic-post-type-menu' ), $plural_label )
            ),
            'href'   => false,
        ] );

        $wp_admin_bar->add_node( [
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'my-' . $post_type_slug,
            'title'  => $this->get_link_markup(
                admin_url( 'edit.php?post_type=' . $post_type_slug . '&author=' . get_current_user_id() ),
                sprintf( __( 'My %s', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( 'Open My %s in a new window', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( '%s → Open My items in a new tab', 'dynamic-post-type-menu' ), $plural_label )
            ),
            'href'   => false,
        ] );

        $wp_admin_bar->add_node( [
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'add-new-' . $post_type_slug,
            'title'  => $this->get_link_markup(
                admin_url( "post-new.php?post_type={$post_type_slug}" ),
                $this->get_add_new_label( $post_type_name, $plural_label ),
                sprintf( __( 'Open Add New %s in a new window', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( '%s → Open Add New in a new tab', 'dynamic-post-type-menu' ), $plural_label )
            ),
            'href'   => false,
        ] );
    }

    private function add_my_and_new_submenus(
        WP_Admin_Bar $wp_admin_bar,
        string $post_type_slug,
        string $plural_label,
        string $post_type_name
    ): void {
        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'my-' . $post_type_slug,
            'title'  => $this->get_link_markup(
                admin_url( 'edit.php?post_type=' . $post_type_slug . '&author=' . get_current_user_id() ),
                sprintf( __( 'My %s', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( 'Open My %s in a new window', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( '%s → Open My items in a new tab', 'dynamic-post-type-menu' ), $plural_label )
            ),
            'href'   => false,
        ] );

        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'add-new-' . $post_type_slug,
            'title'  => $this->get_link_markup(
                admin_url( "post-new.php?post_type={$post_type_slug}" ),
                $this->get_add_new_label( $post_type_name, $plural_label ),
                sprintf( __( 'Open Add New %s in a new window', 'dynamic-post-type-menu' ), $plural_label ),
                sprintf( __( '%s → Open Add New in a new tab', 'dynamic-post-type-menu' ), $plural_label )
            ),
            'href'   => false,
        ] );
    }

    private function get_add_new_label( string $post_type_name, string $plural_label ): string {
        return match ( $post_type_name ) {
            'attachment'      => __( 'Add New Media', 'dynamic-post-type-menu' ),
            'bricks_template' => __( 'Add New Bricks Template', 'dynamic-post-type-menu' ),
            default           => sprintf( __( 'Add New %s', 'dynamic-post-type-menu' ), $plural_label ),
        };
    }

    private function get_link_markup( string $url, string $label, string $aria_label, string $title ): string {
        return sprintf(
            '<div class="menu-item-flex"><div><a href="%1$s">%2$s</a></div><div class="icon-link"><a href="%1$s" target="_blank" rel="noopener noreferrer" role="button" aria-label="%3$s" title="%4$s"><span class="sr-only">%5$s</span></a></div></div>',
            esc_url( $url ),
            esc_html( $label ),
            esc_attr( $aria_label ),
            esc_attr( $title ),
            esc_html__( 'Open in New Window', 'dynamic-post-type-menu' )
        );
    }

    private function add_wpcodebox_menu_item( WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! $this->is_wpcodebox_active() ) {
            return;
        }

        $label = 'WPCodeBox';
        $url   = admin_url( 'admin.php?page=wpcodebox2' );

        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'wpcodebox-link',
            'href'   => false,
            'title'  => $this->get_link_markup(
                $url,
                $label,
                __( 'Open WPCodeBox in a new window', 'dynamic-post-type-menu' ),
                __( 'WPCodeBox → Open in a new tab', 'dynamic-post-type-menu' )
            ),
        ] );
    }

    private function is_wpcodebox_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $candidates = [
            'wpcodebox2/wpcodebox2.php',
            'wpcodebox2/wpcodebox.php',
            'wpcodebox/wpcodebox2.php',
            'wpcodebox/wpcodebox.php',
        ];

        foreach ( $candidates as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }

    private function add_acf_menu_item( WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! $this->is_acf_active() ) {
            return;
        }

        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'acf-menu',
            'title'  => esc_html__( 'ACF', 'dynamic-post-type-menu' ),
            'href'   => false,
        ] );

        $items = [
            'Field Groups'  => admin_url( 'edit.php?post_type=acf-field-group' ),
            'Post Types'    => admin_url( 'edit.php?post_type=acf-post-type' ),
            'Taxonomies'    => admin_url( 'edit.php?post_type=acf-taxonomy' ),
            'Options Pages' => admin_url( 'edit.php?post_type=acf-ui-options-page' ),
        ];

        foreach ( $items as $label => $url ) {
            $wp_admin_bar->add_node( [
                'parent' => 'acf-menu',
                'id'     => 'acf-' . sanitize_title( $label ),
                'href'   => false,
                'title'  => $this->get_link_markup(
                    $url,
                    $label,
                    sprintf( __( 'Open %s in a new window', 'dynamic-post-type-menu' ), $label ),
                    sprintf( __( '%s → Open in a new tab', 'dynamic-post-type-menu' ), $label )
                ),
            ] );
        }
    }

    private function is_acf_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $candidates = [
            'advanced-custom-fields/acf.php',
            'advanced-custom-fields-pro/acf.php',
        ];

        foreach ( $candidates as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }

    private function add_metabox_menu_item( WP_Admin_Bar $wp_admin_bar ): void {
        if ( ! $this->is_metabox_active() ) {
            return;
        }

        $wp_admin_bar->add_node( [
            'parent' => 'post-types',
            'id'     => 'metabox-menu',
            'title'  => esc_html__( 'Meta Box', 'dynamic-post-type-menu' ),
            'href'   => false,
        ] );

        $items = [
            'Post Types'     => admin_url( 'edit.php?post_type=mb-post-type' ),
            'Taxonomies'     => admin_url( 'edit.php?post_type=mb-taxonomy' ),
            'Custom Fields'  => admin_url( 'edit.php?post_type=meta-box' ),
            'Settings Pages' => admin_url( 'edit.php?post_type=mb-settings-page' ),
            'Relationships'  => admin_url( 'edit.php?post_type=mb-relationship' ),
        ];

        foreach ( $items as $label => $url ) {
            $wp_admin_bar->add_node( [
                'parent' => 'metabox-menu',
                'id'     => 'metabox-' . sanitize_title( $label ),
                'href'   => false,
                'title'  => $this->get_link_markup(
                    $url,
                    $label,
                    sprintf( __( 'Open %s in a new window', 'dynamic-post-type-menu' ), $label ),
                    sprintf( __( '%s → Open in a new tab', 'dynamic-post-type-menu' ), $label )
                ),
            ] );
        }
    }

    private function is_metabox_active(): bool {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $candidates = [
            'meta-box/meta-box.php',
            'meta-box-aio/meta-box-aio.php',
        ];

        foreach ( $candidates as $plugin_file ) {
            if ( \is_plugin_active( $plugin_file ) ) {
                return true;
            }
        }

        return false;
    }
}
