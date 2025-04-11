<?php

namespace atc\WHx4\Admin;

use atc\WHx4\Core\Plugin;

class SettingsManager
{
    protected Plugin $plugin;

    public function __construct( Plugin $plugin )
    {
        $this->plugin = $plugin;

        add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    /*public function init(): void
    {
        add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }*/

    public function addSettingsPage(): void
    {
        add_options_page(
            'WHx4 v2 Plugin Settings',
            'WHx4 v2 Settings',
            'manage_options',
            'whx4-settings',
            [ $this, 'renderSettingsPage' ]
        );
    }

    public function registerSettings(): void
    {
        register_setting( 'whx4_plugin_settings_group', 'whx4_plugin_settings' );
    }

	public function renderSettingsPage(): void
	{
		$availableModules = $this->plugin->getAvailableModules();
		$option = get_option( 'whx4_plugin_settings', [] );
		$activeModules = $option['active_modules'] ?? [];
		$disabledPostTypes = $option['disabled_post_types'] ?? [];
	
		echo '<div class="wrap">';
		echo '<h1>Plugin Settings</h1>';
		echo '<form method="post" action="options.php">';
	
		settings_fields( 'whx4_plugin_settings_group' );
	
		echo '<table class="form-table" id="whx4-settings-table"><tbody>';
	
		foreach ( $availableModules as $key => $moduleClass ) {
			$isActive = in_array( $key, $activeModules, true );
			$postTypes = method_exists( $moduleClass, 'getPostTypes' ) ? $moduleClass::getPostTypes() : [];
	
			echo '<tr>';
			echo '<th scope="row">';
			echo '<label>';
			echo '<input type="checkbox" class="module-toggle" name="whx4_plugin_settings[active_modules][]" value="' . esc_attr( $key ) . '" ' . checked( $isActive, true, false ) . ' />';
			echo ' ' . esc_html( $key );
			echo '</label>';
			echo '</th>';
			echo '<td></td>';
			echo '</tr>';
	
			echo '<tr id="post-types-' . esc_attr( $key ) . '" class="post-type-row" ' . ( $isActive ? '' : 'style="display:none;"' ) . '>';
			echo '<td colspan="2" style="padding-left: 30px;">';
	
			foreach ( $postTypes as $postType ) {
				$isDisabled = isset( $disabledPostTypes[ $key ] ) && in_array( $postType, $disabledPostTypes[ $key ], true );
				echo '<label style="display:block;">';
				echo '<input type="checkbox" name="whx4_plugin_settings[disabled_post_types][' . esc_attr( $key ) . '][]" value="' . esc_attr( $postType ) . '" ' . checked( $isDisabled, true, false ) . ' />';
				echo ' Disable <code>' . esc_html( $postType ) . '</code>';
				echo '</label>';
			}
	
			echo '</td></tr>';
		}
	
		echo '</tbody></table>';
	
		submit_button();
	
		// JavaScript for toggling post type sections
		echo <<<HTML
		<script>
		document.addEventListener('DOMContentLoaded', function () {
			const checkboxes = document.querySelectorAll('.module-toggle');
			checkboxes.forEach(cb => {
				cb.addEventListener('change', function () {
					const row = document.getElementById('post-types-' + this.value);
					if (this.checked) {
						row.style.display = '';
					} else {
						row.style.display = 'none';
					}
				});
			});
		});
		</script>
		HTML;
	
		echo '</form></div>';
	}

/*
// Second draft
    public function renderSettingsPage(): void
    {
        $availableModules = $this->plugin->getAvailableModules();
        $option = get_option( 'whx4_plugin_settings', [] );
        $activeModules = $option['active_modules'] ?? [];
        $disabledPostTypes = $option['disabled_post_types'] ?? [];

        echo '<div class="wrap">';
        echo '<h1>Plugin Settings</h1>';
        echo '<form method="post" action="options.php">';

        settings_fields( 'whx4_plugin_settings_group' );
        echo '<table class="form-table"><tbody>';

        foreach ( $availableModules as $key => $moduleClass ) {
            $isActive = in_array( $key, $activeModules, true );
            echo '<tr><th colspan="2">';
            echo '<label><input type="checkbox" name="whx4_plugin_settings[active_modules][]" value="' . esc_attr( $key ) . '" ' . checked( $isActive, true, false ) . ' />';
            echo ' ' . esc_html( $key ) . '</label></th></tr>';

            if ( $isActive ) {
                $postTypes = method_exists( $moduleClass, 'getPostTypes' ) ? $moduleClass::getPostTypes() : [];

                foreach ( $postTypes as $postType ) {
                    $isDisabled = isset( $disabledPostTypes[ $key ] ) && in_array( $postType, $disabledPostTypes[ $key ], true );
                    echo '<tr><td style="padding-left: 30px;">';
                    echo '<label><input type="checkbox" name="whx4_plugin_settings[disabled_post_types][' . esc_attr( $key ) . '][]" value="' . esc_attr( $postType ) . '" ' . checked( $isDisabled, true, false ) . ' />';
                    echo ' Disable <code>' . esc_html( $postType ) . '</code></label>';
                    echo '</td></tr>';
                }
            }
        }

        echo '</tbody></table>';

        submit_button();
        echo '</form>';
        echo '</div>';
    }
*/
	/*
	// First draft
    public function renderSettingsPage(): void
    {
        // output form HTML
        $allModules = [ 'Supernatural' => [ 'monster', 'enchanter', 'spell' ] ]; // Example only
		$settings = get_option( 'whx4_plugin_settings', [] );
		$modules = $settings['active_modules'] ?? [];
	
		echo '<form method="post" action="options.php">';
		settings_fields( 'whx4_plugin_settings_group' );
		do_settings_sections( 'whx4_plugin_settings' );
	
		foreach ( $allModules as $module => $postTypes ) {
			$isActive = $modules[ $module ]['active'] ?? false;
			$enabled = $modules[ $module ]['enabled_post_types'] ?? [];
	
			echo "<h3><label><input type='checkbox' name='whx4_plugin_settings[active_modules][$module][active]' value='1'" . checked( $isActive, true, false ) . "> $module Module</label></h3>";
	
			echo "<ul style='margin-left:2em'>";
			foreach ( $postTypes as $pt ) {
				$checked = ( empty( $enabled ) || in_array( $pt, $enabled ) );
				echo "<li><label><input type='checkbox' name='whx4_plugin_settings[active_modules][$module][enabled_post_types][]' value='$pt'" . checked( $checked, true, false ) . "> $pt</label></li>";
			}
			echo "</ul>";
		}
	
		submit_button();
		echo '</form>';
    }
    */
}
