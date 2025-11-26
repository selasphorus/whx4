<?php
/**
 * Plugin Name:       WHx4 plugin
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/What/Where/When).
 * Dependencies:	  Requires WHx4-Core for core functionality
 * Requires Plugins:  whx4-core, advanced-custom-fields-pro
 * Version:           2.0
 * Author:            atc
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whx4
 *
 * @package           whx4
 */

declare(strict_types=1);

namespace atc\WHx4;

// Prevent direct access
if ( !defined( 'ABSPATH' ) ) exit;

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'WHX4_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use atc\WXC\Plugin;

// WXC Add-on Modules: "WH" Primary Modules
use atc\WHx4\Modules\People\PeopleModule as People;
use atc\WHx4\Modules\Places\PlacesModule as Places;
use atc\WHx4\Modules\Events\EventsModule as Events;

// WXC Add-on Modules: Secondary Modules
use atc\WHx4\Modules\Projects\ProjectsModule as Projects;
use atc\WHx4\Modules\Logbook\LogbookModule as Logbook;

// Once plugins are loaded, boot everything up
add_action('wxc_pre_boot', function() {
    // Wait until WXC is loaded, but BEFORE it boots
    if (!class_exists(Plugin::class)) {
        return;
    }

    // Register the modules with WHx4
    add_filter('wxc_register_modules', function(array $modules): array {
        $modules['people'] = People::class;
        $modules['places'] = Places::class;
        $modules['events'] = Events::class;
        $modules['projects'] = Projects::class;
        $modules['logbook'] = Logbook::class;
        return $modules;
    });
    
    // Register Field Keys
    add_filter('wxc_registered_field_keys', function() {
        if (!function_exists('acf_get_local_fields')) {
            return [];
        }

        $fields = acf_get_local_fields();
        $keys = [];

        foreach ($fields as $field) {
            if (isset($field['key'])) {
                $keys[] = $field['key'];
            }
        }

        return $keys;
    });
    
    // Register Assets
    add_filter('wxc_assets', static function (array $assets): array {
        // CSS
        $relCss = 'assets/css/whx4.css';
        $srcCss = plugins_url($relCss, __FILE__);
        $pathCss = plugin_dir_path(__FILE__) . $relCss;
    
        $assets['styles'][] = [
            'handle'   => 'whx4',
            'src'      => $srcCss,
            'path'     => $pathCss,
            'deps'     => [],
            'ver'      => 'auto',
            'media'    => 'all',
            'where'    => 'front',
            'autoload' => true,
        ];
    
        // JS
        $relJs = 'assets/js/whx4.js';
        $srcJs = plugins_url($relJs, __FILE__);
        $pathJs = plugin_dir_path(__FILE__) . $relJs;
    
        $assets['scripts'][] = [
            'handle'    => 'whx4',
            'src'       => $srcJs,
            'path'      => $pathJs,
            'deps'      => [],        // e.g., ['jquery']
            'ver'       => 'auto',
            'in_footer' => true,
            'where'     => 'front',
            'autoload'  => true,
        ];
    
        return $assets;
    });
    
    // Register Admin Pages
    /*if (is_admin()) {
        (new TagCleanupPageController())->addHooks();
    }*/
    
}, 15); // Priority < 20 to run before WHx4 boot()

// Init

// Activate the following after EM events have been migrated and the EM plugin has been deactivated
/*
add_filter( 'whx4_events_post_type_slug', function() {
    return 'event';
});
*/
