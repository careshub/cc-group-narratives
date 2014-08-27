<?php
/**
 *
 * @package   CC Group Narratives
 * @author    David Cavins
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 *
 * @wordpress-plugin
 * Plugin Name:       CC Group Narratives
 * Description:       Allows groups to contribute blog posts
 * Version:           1.0.0
 * Author:            CARES staff
 * Author URI:        @TODO
 * Text Domain:       plugin-name-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'CC_Group_Narratives', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CC_Group_Narratives', 'deactivate' ) );

/* Do our setup after BP is loaded, but before we create the group extension */
function ccgn_class_init() {

	// Helper functions
	require_once( plugin_dir_path( __FILE__ ) . 'includes/ccgn-functions.php' );

	// The main class
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-cc-group-narratives.php' );

	add_action( 'bp_include', array( 'CC_Group_Narratives', 'get_instance' ), 21 );

	// Admin and dashboard functionality
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

		require_once( plugin_dir_path( __FILE__ ) . 'admin/class-cc-group-narratives-admin.php' );
		add_action( 'bp_include', array( 'CC_Group_Narratives_Admin', 'get_instance' ), 21 );

	}

}
add_action( 'bp_include', 'ccgn_class_init' );

/* Only load the group extension if BuddyPress is loaded and initialized. */
function bp_startup_cc_group_narratives_extension() {
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-bp-group-extension.php' );
}
add_action( 'bp_include', 'bp_startup_cc_group_narratives_extension', 24 );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */

