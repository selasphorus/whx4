<?php
/**
 * Plugin Name:       WHx4 plugin
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/What/Where/When).
 * //Requires at least: 6.4
 * //Requires PHP:      7.4
 * //Dependencies:	  Requires SDG for various utility functions
 * Requires Plugins:  advanced-custom-fields-pro
 * Version:           1.0
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

//error_log( '=== WHx4 test: did this run? ===' );

// Via Composer
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use atc\WHx4\Plugin;
use atc\WHx4\Core\PostUtils;
// TBD whether there's a way to streamline the following
use atc\WHx4\Modules\Supernatural\Module as Supernatural;
use atc\WHx4\Modules\People\Module as People;
use atc\WHx4\Modules\Places\Module as Places;
use atc\WHx4\Modules\Events\Module as Events;

// Init
add_filter( 'whx4_register_modules', function( array $modules ) {
    return array_merge( $modules, [
        'supernatural'	=> Supernatural::class, //\YourPlugin\Modules\Supernatural\Module::class,
        'people'		=> People::class,
        'places'		=> Places::class,
        'events' 		=> Events::class
    ]);
});

// Once plugins are loaded, boot everything up
add_action( 'plugins_loaded', function() {
    Plugin::getInstance()->boot();
});

// On activation, set up post types and capabilities
register_activation_hook( __FILE__, function() {
    $plugin = Plugin::getInstance();
    $plugin->boot();
    $plugin->assignPostTypeCapabilities();
});

// Deactivation
register_deactivation_hook( __FILE__, function() {
    $plugin = Plugin::getInstance();
    $plugin->removePostTypeCapabilities();
});

    
/* ***** TODO: Move most or all of the following away into classes ***** */

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

