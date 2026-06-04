# Changelog

## 2.0.2

- Aligned the action submenu with the selected content type row.
- Added a plugin settings link at the bottom of the Content Types panel.
- Preserved the compact panel architecture and left-aligned panel labels.
- Fixed a duplicated conditional in the post type add-new URL helper.

## 2.0.1

- Rebuilt the settings screen into the Etch Central-style card grid so the post type and feature controls align predictably.
- Updated the plugin header, README, and compatibility notes for WordPress 6.9.4 minimum, WordPress 7.0 tested, and PHP 8.3 requirement.
- Left-aligned `.dptm-panel__label` in the admin-bar panel.
- Kept the 2.0 panel architecture intact while correcting the admin settings layout.

## 2.0.0

- Promoted the custom panel architecture to the stable 2.0 release.
- Reviewed plugin structure and kept responsibilities separated across the bootstrap file, settings class, menu handler class, assets, and uninstall file.
- Added shared plugin constants for version, file path, directory path, and plugin URL.
- Added safer asset version handling with a version fallback when asset files are unavailable.
- Added direct-access guards to include files.
- Updated plugin headers to require WordPress 6.9.4 and mark compatibility through WordPress 7.0.
- Required PHP 8.3 and documented WordPress 6.9.4 minimum / WordPress 7.0 tested compatibility.
- Fixed the settings-screen layout so post type and feature cards stack predictably instead of drifting into separate columns.
- Removed remaining settings-screen inline label styles in favor of CSS classes.
- Updated README documentation for the panel-based architecture.

## 1.9.1

- Opened the custom panel with no default post type selected.
- Reduced the panel width so the content type list is closer to its actual content size.
- Improved submenu hover contrast for WCAG AA readability.
- Kept submenu actions click-enabled and removed keyboard auto-selection while arrowing through the list.

## 1.9.0

- Replaced the native WordPress nested admin-bar submenu with a custom click-enabled panel architecture.
- Added a two-column Content Types panel: post types on the left and contextual actions on the right.
- Removed native submenu flyout behavior to prevent overlap, duplicate indicators, and WordPress admin-bar CSS conflicts.
- Used post type admin icons where available, with sensible fallbacks.
- Added keyboard navigation, Escape-to-close, outside-click close, and direct/new-window actions.

## 1.8.5

- Reworked the admin-bar dropdowns into an Etch Central-style panel treatment.
- Added rounded panel containers, row dividers, compact vertically centered rows, subtle item icons, and tighter nested flyout alignment.

## 1.8.4

- Tightened admin-bar menu spacing and removed nested wrapper padding from link rows.
- Adjusted dropdowns toward the Etch Central panel style with compact, vertically centered rows.

## 1.8.3

- Updated the settings screen and admin-bar menu styling to align with Etch Central.
- Kept the standard admin-bar dropdown structure without adding search or panel behavior.

## 1.8.2

- Removed loading of assets when not logged in.

## 1.8.1

- Added plugin settings page.
- Added optional removal of default Posts screens from wp-admin.
- Added optional hiding of the WordPress + New menu.
- Added controls for which post types appear in Content Types.
- Added controls for which feature items appear in Content Types.
