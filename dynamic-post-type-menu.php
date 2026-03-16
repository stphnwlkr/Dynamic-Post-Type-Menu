<?php
/**
 * Plugin Name: Dynamic Post Type Menu
 * Description: Adds a configurable Content Types admin bar menu with post type links and optional integrations for ACF, Meta Box, and WPCodeBox.
 * Version: 1.8.1
 * Author: Stephen Walker
 * Requires at least: 6.8.5
 * Tested up to: 7.0 beta 5
 * Author URI: https://flyingw.co/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: dynamic-post-type-menu
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-menu-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-role-manager.php';

function dynamic_post_type_menu_init(): void {
    new Dynamic_Post_Type_Menu\Admin_Settings();
    new Dynamic_Post_Type_Menu\Menu_Handler();
}
add_action( 'plugins_loaded', 'dynamic_post_type_menu_init' );

function dynamic_post_type_menu_load_textdomain(): void {
    load_plugin_textdomain( 'dynamic-post-type-menu', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'dynamic_post_type_menu_load_textdomain' );

function dynamic_post_type_menu_enqueue_admin_styles(): void {
    wp_enqueue_style(
        'dynamic-post-type-menu-admin-style',
        plugins_url( 'assets/css/styles.css', __FILE__ ),
        [],
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/styles.css' )
    );
}
add_action( 'admin_enqueue_scripts', 'dynamic_post_type_menu_enqueue_admin_styles' );

function dynamic_post_type_menu_enqueue_frontend_styles(): void {
    if ( ! is_admin() ) {
        wp_enqueue_style(
            'dynamic-post-type-menu-style',
            plugins_url( 'assets/css/styles.css', __FILE__ ),
            [],
            filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/styles.css' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'dynamic_post_type_menu_enqueue_frontend_styles' );

function dynamic_post_type_menu_activate(): void {
    if ( false === get_option( Dynamic_Post_Type_Menu\Admin_Settings::OPTION_KEY, false ) ) {
        add_option( Dynamic_Post_Type_Menu\Admin_Settings::OPTION_KEY, Dynamic_Post_Type_Menu\Admin_Settings::get_defaults() );
    }
}
register_activation_hook( __FILE__, 'dynamic_post_type_menu_activate' );

function dynamic_post_type_menu_deactivate(): void {
    // Reserved for future deactivation tasks.
}
register_deactivation_hook( __FILE__, 'dynamic_post_type_menu_deactivate' );
