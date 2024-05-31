<?php
/**
 * Plugin Name:       WHX3 ACF plugin
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/Where/When) using ACF PRO Blocks, Post Types, Options Pages, Taxonomies and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           0.1
 * Author:            ACF
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whx3
 *
 * @package           whx3
 */

// Define our handy constants.
define( 'WHX3_VERSION', '0.1.5' );
define( 'WHX3_PLUGIN_DIR', __DIR__ );
define( 'WHX3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHX3_PLUGIN_BLOCKS', WHX3_PLUGIN_DIR . '/blocks/' );

// Load custom post types
require 'includes/posttypes.php'; // wip

// Load custom taxonomies
require 'includes/taxonomies.php'; // wip

/* +~+~+ ACF +~+~+ */

// Set custom load & save JSON points for ACF sync
require 'includes/acf-json.php';

// Load ACF field groups hard-coded as PHP
require 'includes/acf-field-groups.php'; // wip

// Register blocks and other handy ACF Block helpers
require 'includes/acf-blocks.php';

// Register a default "Site Settings" Options Page
require 'includes/acf-settings-page.php';

// Restrict access to ACF Admin screens
require 'includes/acf-restrict-access.php';

// Display and template helpers
require 'includes/template-tags.php';

/* +~+~+ Optional Modules +~+~+ */

// Get plugin options -- WIP
$options = get_option( 'whx3_settings' );
if ( isset($options['active_modules']) ) { $modules = $options['active_modules']; } else { $modules = array(); }
/*
foreach ( $modules as $module ) {

	// Load associated functions file, if any
    $filepath = $plugin_path . 'modules/'.$module.'.php';
    $arr_exclusions = array ( 'admin_notes', 'data_tables', 'links', 'organizations', 'ensembles', 'organs', 'press', 'projects', 'sources' ); // , 'groups', 'newsletters', 'snippets', 'logbook', 'venues', 
    if ( !in_array( $module, $arr_exclusions) ) { // skip modules w/ no associated function files
    	if ( file_exists($filepath) ) { include_once( $filepath ); } else { echo "module file $filepath not found"; }
    }
    
    // Add module options page for adding featured image, page-top content, &c.
    $cpt_names = array(); // array because some modules include multiple post types
    
    // Which post types are associated with this module? Build array
	// Deal w/ modules whose names don't perfectly match their CPT names
	if ( $module == "people" ) {
		$primary_cpt = "person";
		$cpt_names[] = "person";
	} else if ( $module == "places" ) {
		$primary_cpt = "location";
		$cpt_names[] = "location";
		//$cpt_names[] = "building"; // address
	} else if ( $module == "events" ) {
		$primary_cpt = "event";
		$cpt_names[] = "event";
		$cpt_names[] = "event_series";
	} else {
		$cpt_name = $module;
		// Make it singular -- remove trailing "s"
		if ( substr($cpt_name, -1) == "s" && $cpt_name != "press" ) { $cpt_name = substr($cpt_name, 0, -1); }
		$primary_cpt = $cpt_name;
		$cpt_names[] = $cpt_name;
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
*/