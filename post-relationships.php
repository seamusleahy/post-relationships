<?php
/*
Plugin Name: Post Relationships
Description: Create relationships between posts
Author: Seamus Leahy
Version: 0.1
 */

define( 'PR_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/pr_configuration.class.php';
require_once __DIR__ . '/pr_relationship_manager.class.php';
require_once __DIR__ . '/api.php';

// Allow the admin ui to be disabled for someone to only use the core.
// To disable it, set PR_ENABLE_ADMIN_UI constant to `false` in your wp-config.php.
if( !defined( 'PR_ENABLE_ADMIN_UI') || PR_ENABLE_ADMIN_UI ) {
	require_once __DIR__ . '/admin-ui/admin-ui.php';
}
