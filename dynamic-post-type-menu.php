<?php
/**
 * Plugin Name: Dynamic Post Type Menu
 * Description: Adds a dropdown menu to the admin bar with links to all post types and their author-specific listings.
 * Version: 1.5
 * Author: Stephen Walker
 * Requires at least: 6.0
 * Tested up to: 6.6.2
 * Requires PHP 8+
 * Text Domain: dynamic-post-type-menu
 * Domain Path: /languages
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

// Register deactivation hook.
register_deactivation_hook(__FILE__, 'dynamic_post_type_menu_deactivate');
function dynamic_post_type_menu_deactivate() {
    // Cleanup tasks.
}
