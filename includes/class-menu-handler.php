<?php
namespace Dynamic_Post_Type_Menu;

use WP_Admin_Bar;

class Menu_Handler {

    public function __construct() {
        // Hook into the admin bar to add the menu (for both admin and front-end)
        add_action('admin_bar_menu', [$this, 'add_post_type_menu'], 71);
    }

    /**
     * Adds the parent "Content Types" menu to the admin bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
     */
    public function add_post_type_menu(WP_Admin_Bar $wp_admin_bar): void {
        // Only display to users who can edit posts or have author/contributor capabilities
        if (!current_user_can('edit_posts') && !current_user_can('edit_others_posts')) {
            return;
        }

        // Path to the icon file
        $icon_url = plugin_dir_url(__FILE__) . '../assets/img/icons/note-edit-stroke-standard.svg';

        // Add the parent menu item with an icon
        $wp_admin_bar->add_node([
            'id'    => 'post-types',
            'title' => sprintf(
                '<img src="%s" alt="Icon" style="margin-right: 5px; height: 16px; vertical-align: middle;">%s',
                esc_url($icon_url),
                __('Content Types', 'dynamic-post-type-menu')
            ),
            'href'  => false,
            'meta'  => ['class' => 'menupop'],
        ]);

        // Get all public post types
        $post_types = get_post_types(['public' => true], 'objects');

        // Separate default post types (Posts and Pages) and Bricks Templates from others
        [$default_post_types, $other_post_types] = $this->categorize_post_types($post_types);

        // First add Posts, Pages, and Bricks Templates
        foreach ($default_post_types as $post_type) {
            $this->add_role_based_menus($wp_admin_bar, $post_type);
        }

        // Sort other post types alphabetically by label and add them
        usort($other_post_types, fn($a, $b) => strcmp($a->labels->name, $b->labels->name));

        foreach ($other_post_types as $post_type) {
            $this->add_role_based_menus($wp_admin_bar, $post_type);
        }
    }

    /**
     * Categorize post types into default types (Posts, Pages, Bricks Templates) and others.
     */
    private function categorize_post_types(array $post_types): array {
        $default_post_types = [];
        $other_post_types = [];

        foreach ($post_types as $post_type) {
            $post_type->labels->name = match ($post_type->name) {
                'post' => 'Posts',
                'page' => 'Pages',
                'bricks_template' => 'Bricks Templates',  // Rename for Bricks Templates (but not "Add New")
                'attachment' => 'Media',  // Special case for Media
                default => $post_type->labels->name,
            };

            if (in_array($post_type->name, ['post', 'page', 'bricks_template'], true)) {
                $default_post_types[$post_type->name] = $post_type;
            } else {
                $other_post_types[$post_type->name] = $post_type;
            }
        }

        return [$default_post_types, $other_post_types];
    }

    /**
     * Adds submenus for a specific post type based on user role.
     *
     * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
     * @param object $post_type The post type object.
     */
    private function add_role_based_menus(WP_Admin_Bar $wp_admin_bar, object $post_type): void {
        // Sanitize the post type name for use in HTML classes and IDs
        $post_type_slug = sanitize_html_class($post_type->name);
        $plural_label = esc_html($post_type->labels->name);

        // Handle role-based menu items for Admins/Editors and Authors/Contributors
        if (current_user_can('edit_others_posts')) {
            // Add 'All', 'My', and 'Add New' submenus for Admins and Editors
            $this->add_all_my_and_new_submenus($wp_admin_bar, $post_type_slug, $plural_label, $post_type->name);
        } elseif (current_user_can('edit_posts')) {
            // Add 'My' and 'Add New' submenus for Authors and Contributors
            $this->add_my_and_new_submenus($wp_admin_bar, $post_type_slug, $plural_label, $post_type->name);
        }
    }

    /**
     * Add 'All', 'My', and 'Add New' submenus for Admins and Editors
     */
    private function add_all_my_and_new_submenus(WP_Admin_Bar $wp_admin_bar, string $post_type_slug, string $plural_label, string $post_type_name): void {
        // Add the main post type menu item
        $wp_admin_bar->add_node([
            'parent' => 'post-types',
            'id'     => 'post-type-' . $post_type_slug,
            'title'  => $plural_label,
            'href'   => false,
        ]);

        // Add 'All' submenu
        $wp_admin_bar->add_node([
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'all-' . $post_type_slug,
            'title'  => sprintf(__('All %s', 'dynamic-post-type-menu'), $plural_label),
            'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}")),
        ]);

        // Add 'My' submenu
        $wp_admin_bar->add_node([
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'my-' . $post_type_slug,
            'title'  => sprintf(__('My %s', 'dynamic-post-type-menu'), $plural_label),
            'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}&author=" . get_current_user_id())),
        ]);

        // Add 'Add New' submenu
        $add_new_label = ($post_type_name === 'attachment')
            ? __('Add New Media', 'dynamic-post-type-menu')
            : (($post_type_name === 'bricks_template')
                ? __('Add New Bricks Template', 'dynamic-post-type-menu')  // No renaming for Bricks Templates here
                : sprintf(__('Add New %s', 'dynamic-post-type-menu'), $plural_label));

        $wp_admin_bar->add_node([
            'parent' => 'post-type-' . $post_type_slug,
            'id'     => 'add-new-' . $post_type_slug,
            'title'  => $add_new_label,
            'href'   => esc_url(admin_url("post-new.php?post_type={$post_type_slug}")),
        ]);
    }

    /**
     * Add 'My' and 'Add New' submenus for Authors and Contributors
     */
    private function add_my_and_new_submenus(WP_Admin_Bar $wp_admin_bar, string $post_type_slug, string $plural_label, string $post_type_name): void {
        // Add 'My' submenu
        $wp_admin_bar->add_node([
            'parent' => 'post-types',
            'id'     => 'my-' . $post_type_slug,
            'title'  => sprintf(__('My %s', 'dynamic-post-type-menu'), $plural_label),
            'href'   => esc_url(admin_url("edit.php?post_type={$post_type_slug}&author=" . get_current_user_id())),
        ]);

        // Add 'Add New' submenu
        $add_new_label = ($post_type_name === 'attachment')
            ? __('Add New Media', 'dynamic-post-type-menu')
            : (($post_type_name === 'bricks_template')
                ? __('Add New Bricks Template', 'dynamic-post-type-menu')  // No renaming for Bricks Templates here
                : sprintf(__('Add New %s', 'dynamic-post-type-menu'), $plural_label));

        $wp_admin_bar->add_node([
            'parent' => 'post-types',
            'id'     => 'add-new-' . $post_type_slug,
            'title'  => $add_new_label,
            'href'   => esc_url(admin_url("post-new.php?post_type={$post_type_slug}")),
        ]);
    }
}