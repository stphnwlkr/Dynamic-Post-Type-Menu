<?php
/**
 * Plugin Name: Dynamic Post Type Menu
 * Description: Adds a configurable Content Types admin bar panel with post type links and optional integrations for ACF, Meta Box, and WPCodeBox.
 * Version: 2.0.2
 * Author: Stephen Walker
 * Requires at least: 6.9.4
 * Requires PHP: 8.3
 * Tested up to: 7.0
 * Author URI: https://fwcafe.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: dynamic-post-type-menu
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DPTM_VERSION', '2.0.2' );
define( 'DPTM_PLUGIN_FILE', __FILE__ );
define( 'DPTM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPTM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DPTM_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once DPTM_PLUGIN_DIR . 'includes/class-menu-handler.php';
require_once DPTM_PLUGIN_DIR . 'includes/class-role-manager.php';

function dynamic_post_type_menu_init(): void {
    new Dynamic_Post_Type_Menu\Admin_Settings();
    new Dynamic_Post_Type_Menu\Menu_Handler();
}
add_action( 'plugins_loaded', 'dynamic_post_type_menu_init' );

function dynamic_post_type_menu_load_textdomain(): void {
    load_plugin_textdomain( 'dynamic-post-type-menu', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'dynamic_post_type_menu_load_textdomain' );

function dynamic_post_type_menu_asset_version( string $relative_path ): string {
    $path = DPTM_PLUGIN_DIR . ltrim( $relative_path, '/' );

    return file_exists( $path ) ? (string) filemtime( $path ) : DPTM_VERSION;
}

function dynamic_post_type_menu_enqueue_admin_styles(): void {
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style(
        'dynamic-post-type-menu-admin-style',
        DPTM_PLUGIN_URL . 'assets/css/styles.css',
        [],
        dynamic_post_type_menu_asset_version( 'assets/css/styles.css' )
    );
}
add_action( 'admin_enqueue_scripts', 'dynamic_post_type_menu_enqueue_admin_styles' );

function dynamic_post_type_menu_enqueue_frontend_styles(): void {
    if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
        return;
    }

    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style(
        'dynamic-post-type-menu-style',
        DPTM_PLUGIN_URL . 'assets/css/styles.css',
        [],
        dynamic_post_type_menu_asset_version( 'assets/css/styles.css' )
    );
}

add_action( 'wp_enqueue_scripts', 'dynamic_post_type_menu_enqueue_frontend_styles' );

function dynamic_post_type_menu_enqueue_admin_bar_script(): void {
    if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
        return;
    }

    wp_enqueue_script(
        'dynamic-post-type-menu-admin-bar-panel',
        DPTM_PLUGIN_URL . 'assets/js/admin-bar-panel.js',
        [],
        dynamic_post_type_menu_asset_version( 'assets/js/admin-bar-panel.js' ),
        true
    );
}
add_action( 'admin_enqueue_scripts', 'dynamic_post_type_menu_enqueue_admin_bar_script' );
add_action( 'wp_enqueue_scripts', 'dynamic_post_type_menu_enqueue_admin_bar_script' );


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
