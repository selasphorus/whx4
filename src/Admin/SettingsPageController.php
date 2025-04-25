<?php

namespace atc\WHx4\Admin;

use atc\WHx4\Plugin;

class SettingsPageController
{
    protected Plugin $plugin;

    public function __construct( Plugin $plugin )
    {
        //error_log( '=== SettingsPageController __construct() ===' );
        $this->plugin = $plugin;

        //add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
        //add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    public function addHooks(): void
    {
        //error_log( '=== addHooks() ===' );
        add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    public function addSettingsPage(): void
    {
        //error_log( '=== SettingsManager->addSettingsPage() ===' );
        add_options_page(
            'WHx4 v2 Plugin Settings', // page_title
            'WHx4 v2 Settings', // menu_title
            'manage_options', // capability
            'whx4_settings', // menu_slug
            [ $this, 'renderSettingsPage' ] // callback
        );
    }

    public function registerSettings(): void
    {
        register_setting( 'whx4_plugin_settings_group', 'whx4_plugin_settings' );

        add_settings_section(
            'whx4_main_settings',
            'Module and Post Type Settings',
            null,
            'whx4_plugin_settings'
        );

        // Add settings fields here
        // Example:
        // add_settings_field( ... );
    }

    public function renderSettingsPage(): void
    {
        $this->plugin->renderView( 'settings-page', [
            'availableModules' => $this->plugin->getAvailableModules(),
            'activeModules'    => $this->plugin->getSettingsManager()->getActiveModuleSlugs(),
            'enabledPostTypes' => $this->plugin->getSettingsManager()->getEnabledPostTypeSlugsByModule(),
            //'enabledPostTypes' => $this->plugin->getSettingsManager()->getEnabledPostTypeSlugs(),
        ]);
    }

}
