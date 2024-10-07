<?php
namespace Dynamic_Post_Type_Menu;

/**
 * Handles the dynamic toolbar menu for post types.
 */
class Menu_Handler {

    public function __construct() {
        // Hook into the admin bar to add the menu (for both admin and front-end).
        add_action('admin_bar_menu', [$this, 'add_post_type_menu'], 71);
    }

    /**
     * Adds the parent "Post Type Listings" menu to the admin bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
     */
    public function add_post_type_menu($wp_admin_bar) {
        // Only display to users who can edit posts or have author/contributor capabilities.
        if (!current_user_can('edit_posts') && !current_user_can('edit_others_posts')) {
            return;
        }

        // Add the parent menu item.
        $wp_admin_bar->add_node([
            'id'    => 'post-types',
            'title' => __('Post Type Listings', 'dynamic-post-type-menu'),
            'href'  => false,
            'meta'  => ['class' => 'menupop'],
        ]);

        // Get all public post types.
        $post_types = get_post_types(['public' => true], 'objects');

        // Separate Posts and Pages from other post types.
        $default_post_types = [];
        $other_post_types = [];

        foreach ($post_types as $post_type) {
            if ($post_type->name === 'post' || $post_type->name === 'page') {
                $default_post_types[$post_type->name] = $post_type;
            } else {
                $other_post_types[$post_type->name] = $post_type;
            }
        }

        // Sort other post types alphabetically by label.
        usort($other_post_types, function ($a, $b) {
            return strcmp($a->labels->name, $b->labels->name);
        });

        // First add Posts and Pages.
        foreach ($default_post_types as $post_type) {
            $this->add_role_based_menus($wp_admin_bar, $post_type);
        }

        // Then add other post types in alphabetical order.
        foreach ($other_post_types as $post_type) {
            $this->add_role_based_menus($wp_admin_bar, $post_type);
        }
    }

    /**
     * Adds submenus for a specific post type based on user role.
     *
     * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
     * @param object $post_type The post type object.
     */
    private function add_role_based_menus($wp_admin_bar, $post_type) {
        // Sanitize the post type name for use in HTML classes and IDs.
        $post_type_slug = sanitize_html_class($post_type->name);
        $plural_label = esc_html($post_type->labels->name);

        // Check if the current user is an Admin or Editor.
        if (current_user_can('edit_others_posts')) {
            // Add main post type menu item for Admins and Editors.
            $wp_admin_bar->add_node([
                'parent' => 'post-types',
                'id'     => 'post-type-' . $post_type_slug,
                'title'  => $plural_label,
                'href'   => false,
            ]);

            // Add 'All' submenu for Admins and Editors.
            $wp_admin_bar->add_node([
                'parent' => 'post-type-' . $post_type_slug,
                'id'     => 'all-' . $post_type_slug,
                'title'  => sprintf(__('All %s', 'dynamic-post-type-menu'), $plural_label),
                'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}")),
            ]);

            // Add 'My' submenu for Admins and Editors.
            $wp_admin_bar->add_node([
                'parent' => 'post-type-' . $post_type_slug,
                'id'     => 'my-' . $post_type_slug,
                'title'  => sprintf(__('My %s', 'dynamic-post-type-menu'), $plural_label),
                'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}&author=" . get_current_user_id())),
            ]);
        }

        // Check if the current user is an Author or Contributor.
        if (current_user_can('edit_posts') && !current_user_can('edit_others_posts')) {
            // Add only the 'My' submenu for Authors and Contributors.
            $wp_admin_bar->add_node([
                'parent' => 'post-types',
                'id'     => 'my-' . $post_type_slug,
                'title'  => sprintf(__('My %s', 'dynamic-post-type-menu'), $plural_label),
                'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}&author=" . get_current_user_id())),
            ]);
        }
    }
}