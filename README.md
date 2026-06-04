# Dynamic Post Type Menu

- Requires at least: WordPress 6.9.4
- Tested up to: WordPress 7.0
- Requires PHP: 8.3
- Stable tag: 2.0.2

Adds a configurable **Content Types** panel to the WordPress admin bar. The panel gives editors and administrators quick access to enabled post types and supported content-management integrations without relying on WordPress's native nested admin-bar submenu behavior.

## Features

- Custom click-enabled admin-bar panel
- No default post type selection when the panel opens
- Enabled public post types listed in a compact left-side panel
- Contextual actions for **All**, **My**, and **Add New** items
- Uses each post type's admin menu icon when available
- Optional feature links for **ACF**, **Meta Box**, and **WPCodeBox** when those plugins are active
- Settings screen to remove the default **Posts** admin UI, hide the WordPress **+ New** menu, choose visible post types, and choose visible feature items
- Keyboard support for opening, closing, moving through content types, and selecting actions
- Plugin settings link anchored at the bottom of the Content Types panel

## Requirements

- WordPress 6.9.4 or newer
- Tested up to WordPress 7.0
- PHP 8.3 is required

## Settings

Go to **Settings → Dynamic Post Type Menu** to configure the plugin.

Available settings include:

- Remove the built-in Posts post type from the admin UI
- Hide the WordPress + New admin bar menu
- Select which post types appear in the Content Types panel
- Select which feature integrations appear in the Content Types panel

## Notes

When **Remove the built-in Posts post type** is enabled, the plugin hides the default Posts menu and blocks direct admin access to the default Posts list, add-new screen, and edit screen. This does **not** unregister WordPress core's built-in `post` post type.

The admin-bar panel is rendered as custom plugin markup instead of WordPress nested admin-bar submenus. This avoids submenu overlap, duplicate indicator icons, and admin-bar CSS conflicts while keeping the top-level **Content Types** item in the standard WordPress admin bar.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
