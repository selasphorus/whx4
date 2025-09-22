<?php

namespace atc\WHx4\Admin;

use atc\WHx4\Core\WHx4;
use atc\WHx4\Core\ViewLoader;

class SettingsPageController
{

    public function addHooks(): void
    {
        //error_log( '=== SettingsPageController::addHooks() ===' );
        add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    public function addSettingsPage(): void
    {
        //error_log( '=== SettingsPageController::addSettingsPage() ===' );
        add_options_page(
            'WHx4 v2 Plugin Settings', // page_title
            'WHx4 v2 Settings', // menu_title
            'manage_options', // capability
            'whx4-settings', // menu_slug
            [ $this, 'renderSettingsPage' ] // callback
        );
    }

    public function registerSettings(): void
    {
        //error_log( '=== SettingsPageController::registerSettings() ===' );
        register_setting( 'whx4_plugin_settings_group', 'whx4_plugin_settings' );
        // WIP 08/19/25
        /*register_setting(
            'whx4_plugin_settings_group',
            'whx4_plugin_settings',
            ['type' => 'array', 'sanitize_callback' => [$this, 'sanitizeOptions']]
        );*/

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
        //error_log( '=== SettingsPageController::renderSettingsPage() ===' );
        ViewLoader::render( 'settings-page', [
            'availableModules' => WHx4::ctx()->getAvailableModules(),
            'activeModules'    => WHx4::ctx()->getSettingsManager()->getActiveModuleSlugs(),
            'enabledPostTypes' => WHx4::ctx()->getSettingsManager()->getEnabledPostTypeSlugsByModule(),
            //'enabledPostTypes' => $this->plugin->getSettingsManager()->getEnabledPostTypeSlugs(),
        ]);
    }

    // WIP 08/19/25
    /*public function sanitizeOptions(array $input): array
    {
        $saved    = $this->getOption();
        $allowed  = array_keys($this->plugin->getAvailableModules());

        $active = array_values(array_intersect(
            $input['active_modules'] ?? [],
            $allowed
        ));

        $saved['active_modules'] = $active;

        // Keep whatever else you store (enabled_post_types, etc.)
        // Merge other fields with appropriate sanitization...

        return $saved;
    }*/
}
