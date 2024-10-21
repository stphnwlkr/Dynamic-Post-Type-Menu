<?php
/**
 * Plugin Name: Dynamic Post Type Menu
 * Description: Adds a dropdown menu to the admin bar with links to all post types and their author-specific listings.
 * Version: 1.5.1
 * Author: Stephen Walker
 * Requires at least: 6.0
 * Tested up to: 6.6.2
 * Author URI:https://flyingw.co/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt

 * Text Domain: dynamic-post-type-menu
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Load necessary class files.
require_once plugin_dir_path(__FILE__) . 'includes/class-menu-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-role-manager.php';

// Initialize the plugin.
function dynamic_post_type_menu_init() {
    new Dynamic_Post_Type_Menu\Menu_Handler();
}
add_action('plugins_loaded', 'dynamic_post_type_menu_init');

// Load text domain for translations.
function dynamic_post_type_menu_load_textdomain() {
    load_plugin_textdomain('dynamic-post-type-menu', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'dynamic_post_type_menu_load_textdomain');

// Register activation hook.
register_activation_hook(__FILE__, 'dynamic_post_type_menu_activate');
function dynamic_post_type_menu_activate() {
    // Activation tasks such as flushing rewrite rules, etc.
}
function dynamic_post_type_menu_enqueue_styles() {
    // Frontend styles
    if (!is_admin()) {
        wp_enqueue_style(
            'dynamic-post-type-menu-style', 
            plugins_url('assets/css/styles.css', __FILE__)
        );
    }
}
add_action('wp_enqueue_scripts', 'dynamic_post_type_menu_enqueue_styles');

// Admin styles
function dynamic_post_type_menu_admin_enqueue_styles() {
    wp_enqueue_style(
        'dynamic-post-type-menu-admin-style', 
        plugins_url('assets/css/styles.css', __FILE__)
    );
}
add_action('admin_enqueue_scripts', 'dynamic_post_type_menu_admin_enqueue_styles');

// Register deactivation hook.
register_deactivation_hook(__FILE__, 'dynamic_post_type_menu_deactivate');
function dynamic_post_type_menu_deactivate() {
    // Cleanup tasks.
}



