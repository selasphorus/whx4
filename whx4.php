<?php
/**
 * Plugin Name:       WHx4 ACF plugin
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/What/Where/When) using ACF PRO Blocks, Post Types, Options Pages, Taxonomies and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           0.1
 * Author:            ACF
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whx4
 *
 * @package           whx4
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Define our handy constants.
define( 'WHX4_VERSION', '0.2.0' );
define( 'WHX4_PLUGIN_DIR', __DIR__ );
define( 'WHX4_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHX4_PLUGIN_BLOCKS', WHX4_PLUGIN_DIR . '/blocks/' );

/* +~+~+ *** +~+~+ */

// Function to check for dev/admin user
function whx4_queenbee() {
	$current_user = wp_get_current_user();
	$username = $current_user->user_login;
	$useremail = $current_user->user_email;
	//
    if ( $username == 'stcdev' || $useremail == "birdhive@gmail.com" ) {
    	return true;
    } else {
    	return false;
    }
}

/* +~+~+ ACF +~+~+ */

// Set custom load & save JSON points for ACF sync
require 'includes/acf-json.php';

// Register blocks and other handy ACF Block helpers
require 'includes/acf-blocks.php';

// Register a default "Site Settings" Options Page
require 'includes/acf-settings-page.php';

// Restrict access to ACF Admin screens
require 'includes/acf-restrict-access.php';

// Display and template helpers
require 'includes/template-tags.php';
	
// Load ACF field groups hard-coded as PHP
require 'includes/acf-field-groups.php';

// Post types, taxonomies, field groups
require 'includes/cpts.php';

// Load custom post types
//require 'posttypes.php';

// Load custom taxonomies
//require 'taxonomies.php';

/* +~+~+ Optional Modules +~+~+ */

// Get plugin options -- WIP
$options = get_option( 'whx4_settings' );
if ( get_field('whx4_active_modules', 'option') ) { $active_modules = get_field('whx4_active_modules', 'option'); } else { $active_modules = array(); }
//if ( isset($options['whx4_active_modules']) ) { $active_modules = $options['whx4_active_modules']; } else { $active_modules = array(); }
foreach ( $active_modules as $module ) {
    
    $sub_modules = array();
    // Add module options page for adding featured image, page-top content, &c.
    $cpt_names = array(); // array because some modules include multiple post types
	
	// Which post types are associated with this module? Build array
	// Deal w/ modules whose names don't perfectly match their CPT names
	if ( $module == "people" ) {
		$sub_modules[] = "people";
		$sub_modules[] = "groups";
		$primary_cpt = "person";
		$cpt_names[] = "person";
		//$cpt_names[] = "group";
	} else if ( $module == "places" ) {
		$sub_modules[] = "venues";
		$primary_cpt = "location";
		$cpt_names[] = "location";
		//$cpt_names[] = "building"; // address
	} else if ( $module == "events" ) {
		$sub_modules[] = "events";
		$primary_cpt = "event";
		$cpt_names[] = "event";
		$cpt_names[] = "event_series";
	} else {
		$sub_modules[] = $module;
		$cpt_name = $module;
		// Make it singular -- remove trailing "s"
		if ( substr($cpt_name, -1) == "s" && $cpt_name != "press" ) { $cpt_name = substr($cpt_name, 0, -1); }
		$primary_cpt = $cpt_name;
		$cpt_names[] = $cpt_name;
	}
	
	// Load associated functions file, if any
	foreach ( $sub_modules as $sub_module ) {
		$filepath = WHX4_PLUGIN_DIR.'/modules/'.$sub_module.'.php';
		$arr_exclusions = array ( 'organizations', 'ensembles' );
		if ( !in_array( $module, $arr_exclusions) ) { // skip modules w/ no associated function files
			if ( file_exists($filepath) ) { require( $filepath ); } else { echo "WHx4 module file $filepath not found"; }
		}
    }
    
	if ( function_exists('acf_add_options_page') ) {
		// Add module options page
    	acf_add_options_sub_page(array(
			'page_title'	=> ucfirst($module).' Module Options',
			'menu_title'    => ucfirst($module).' Module Options',//'menu_title'    => 'Archive Options', //ucfirst($cpt_name).
			'menu_slug' 	=> $module.'-module-options',
			'parent_slug'   => 'edit.php?post_type='.$primary_cpt,
		));
	}

}

?>