<?php
// If the uninstall constant is not defined, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Perform any plugin cleanup here, such as deleting options or custom data.
delete_option('dynamic_post_type_menu_options');  // Example of cleaning up options