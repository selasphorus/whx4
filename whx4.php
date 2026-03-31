<?php
/**
 * Plugin Name:       WHx4 plugin (extract-core)
 * Description:       A WordPress plugin for managing People, Places, and Events (Who/What/Where/When).
 * Dependencies:	  Requires WHx4-Core for core functionality
 * Requires Plugins:  whx4-core, advanced-custom-fields-pro
 * Version:           2.033126.1
 * Author:            atc
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whx4
 *
 * @package           whx4
 */

declare(strict_types=1);

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
use atc\WXC\App;

// WXC Add-on Modules: "WH" Primary Modules
use atc\WHx4\Modules\People\PeopleModule as People;
use atc\WHx4\Modules\Places\PlacesModule as Places;
use atc\WHx4\Modules\Events\EventsModule as Events;

// WXC Add-on Modules: Secondary Modules
use atc\WHx4\Modules\Media\MediaModule as Media;
use atc\WHx4\Modules\Snippets\SnippetsModule as Snippets;
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
        $modules['media'] = Media::class;
        $modules['snippets'] = Snippets::class;
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

// Global Wrapper Functions for theme access

/**
 * Get post thumbnail with fallback handling
 * See https://developer.wordpress.org/reference/functions/the_post_thumbnail/ >> the_post_thumbnail( string|int[] $size = 'post-thumbnail', string|array $attr = '' )
 *
 * @since 1.0.0
 * @param int|WP_Post|null $post Post ID or object. Default current post.
 * @param string           $size Image size. Default 'thumbnail'.
 * @param array            $args Optional arguments.
 * @return string|false Image HTML or false on failure.
 */
/**
 * Get post thumbnail with fallback handling.
 *
 * Delegates to MediaDisplay::renderPostImage() when the Media module is active.
 *
 * @since 1.0.0
 * @param  array $args  See MediaDisplay::renderPostImage() for supported args.
 * @return string|int|null  Image HTML, attachment ID, or null if unavailable.
 */
function whx4_post_thumbnail( array $args = [] ): string|int|null
{
    $activeSlugs = App::ctx()->getSettingsManager()->getActiveModuleSlugs();
    if ( ! in_array( 'media', $activeSlugs, true ) ) {
        return null;
    }

    return apply_filters(
        'whx4_post_thumbnail',
        atc\WHx4\Modules\Media\Utils\MediaDisplay::renderPostImage( $args ),
        $args
    );
}

/**
 * Find image data associated with a post.
 *
 * Returns an array with keys:
 *   imgID    int|null  Resolved attachment ID, or null if not found.
 *   imgType  string    'post_image' or 'attachment_image'.
 *   imgClass string    Additional CSS classes for the image wrapper.
 *   info     string    Debug output.
 *
 * Returns null if the Media module is inactive.
 *
 * @param  \WP_Post|int     $post     Post object or ID.
 * @param  string           $format   'singular' or 'excerpt'. Default 'excerpt'.
 * @param  string[]|string  $sources  Sources to check. Default ['featured_image','gallery'].
 * @return array{imgID:int|null,imgType:string,imgClass:string,info:string}|null
 */
function whx4_find_post_image(
    \WP_Post|int $post,
    string $format = 'excerpt',
    array|string $sources = ['featured_image', 'gallery']
): ?array {
    $activeSlugs = App::ctx()->getSettingsManager()->getActiveModuleSlugs();
    if (!in_array('media', $activeSlugs, true)) {
        return null;
    }

    return atc\WHx4\Modules\Media\Utils\MediaDisplay::findPostImage($post, $format, $sources);
}