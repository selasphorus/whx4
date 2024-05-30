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

// Set custom load & save JSON points for ACF sync.
require 'includes/acf-json.php';
// Register blocks and other handy ACF Block helpers.
require 'includes/acf-blocks.php';
// Register a default "Site Settings" Options Page.
require 'includes/acf-settings-page.php';
// Restrict access to ACF Admin screens.
require 'includes/acf-restrict-access.php';
// Display and template helpers.
require 'includes/template-tags.php';
