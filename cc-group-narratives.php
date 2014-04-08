<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   CC Group Narratives
 * @author    David Cavins
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 *
 * @wordpress-plugin
 * Plugin Name:       CC Group Narratives
 * Plugin URI:        @TODO
 * Description:       @TODO
 * Version:           1.0.0
 * Author:            David Cavins
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

require_once( plugin_dir_path( __FILE__ ) . 'public/class-cc-group-narratives.php' );

// Helper functions
require_once( plugin_dir_path( __FILE__ ) . 'includes/ccgn-functions.php' );

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_startup_cc_group_narratives_extension() {
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-bp-group-extension.php' );
}
add_action( 'bp_include', 'bp_startup_cc_group_narratives_extension' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'CC_Group_Narratives', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CC_Group_Narratives', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'CC_Group_Narratives', 'get_instance' ) );

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
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-cc-group-narratives-admin.php' );
	add_action( 'plugins_loaded', array( 'CC_Group_Narratives_Admin', 'get_instance' ) );

}
