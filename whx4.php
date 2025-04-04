<?php
/**
 * Plugin Name:       WHx4 plugin
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/What/Where/When).
 * //Requires at least: 6.4
 * //Requires PHP:      7.4
 * Dependencies:	  Requires SDG for various utility functions
 * Requires Plugins:  sdg
 * Version:           0.1
 * Author:            atc
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whx4
 *
 * @package           whx4
 */

// v1 designed using ACF PRO Blocks, Post Types, Options Pages, Taxonomies and more.
// v2 OOP version WIP

if ( !defined( 'ABSPATH' ) ) exit;

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// WIP >> OOP

// Via Composer
use atc\WHx4\Core\Plugin;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
//require 'vendor/autoload.php';
//$plugin = new Core\Plugin();
//$plugin = new WHx4\Core\Plugin();
//$plugin = new atc\WHx4\Core\Plugin();

/* 
-- OR --

// Via Autoloader Class
require_once __DIR__ . '/WHx4/Autoloader.php';
WHx4_Autoloader::register();

// Found at /WHx4/Plugin.php
$plugin = new WHx4_Plugin();

-- OR --

use atc\WHx4\Plugin;

Plugin::run( entry_point: __FILE__ );

*/

/* ***** TODO: Move all of the following away into classes ***** */

// Function to check for main dev/admin user
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
/**/

/* +~+~+ Misc Functions +~+~+ */

//add_action( 'init', 'whx4_redirect');
function whx4_redirect() {

	// If /events/ with query args and limit is set to 1, then see if there's a matching event and redirect to that event
	// /events/?scope=future&category=sunday-recital-series&limit=1&dev=events
	// /music/the-sunday-recital-series/upcoming-sunday-recital/
	//$current_url = home_url( add_query_arg( array(), $wp->request ) );
	
	if ( $wp->request == "/events" && get_query_var('limit') == "1") {
        
        // Run EM search based on query vars
        // Redirect to next single event record matching scope etc.
        
        //wp_redirect( site_url('/de/') ); 
        //exit; 
    }
}

?>