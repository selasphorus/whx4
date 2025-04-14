<?php

namespace atc\WHx4\Admin;

use atc\WHx4\Plugin;

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
    	error_log( '=== SettingsManager->addSettingsPage() ===' );
    	
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
    }

	public function renderSettingsPage(): void
	{
		$availableModules = $this->plugin->getAvailableModules();
		$option = get_option( 'whx4_plugin_settings', [] );
		$activeModules = $option['active_modules'] ?? [];
		//$disabledPostTypes = $option['disabled_post_types'] ?? [];
		$enabledPostTypes = $option['enabled_post_types'] ?? [];
	
		//$settingsHTML = ""; // TBD: can we clean this up to not have all those echos...
		
		echo '<div class="wrap">';
		echo '<h1>Plugin Settings</h1>';
		echo '<form method="post" action="options.php">';
	
		settings_fields( 'whx4_plugin_settings_group' );
	
		//event_list_item_format
		//use_custom_caps
		
		echo '<table class="form-table" id="whx4-settings-table"><tbody>';
		
		//echo '<tr><td>enabledPostTypes</td><td><pre>'.print_r($enabledPostTypes, true).'</pre></td></tr>'; // ts
	
		foreach ( $availableModules as $key => $moduleClass ) {
			
			$isActive = in_array( $key, $activeModules, true );			

			echo '<tr>';
			echo '<th scope="row">';
			echo '<label>';
			echo '<input type="checkbox" class="module-toggle" name="whx4_plugin_settings[active_modules][]" value="' . esc_attr( $key ) . '" ' . checked( $isActive, true, false ) . ' />';
			echo ' ' . esc_html( $key );
			echo '</label>';
			echo '</th>';
			echo '<td></td>';
			echo '</tr>';
			
			$postTypes = []; // init
			
			//error_log("Trying to instantiate post type handler: {$moduleClass}");
			$module = new $moduleClass();
			//error_log("Successfully instantiated handler: ".get_class($module));
			
			if ( class_exists( $moduleClass ) ) {
				$module = new $moduleClass();
				$postTypes = $module->getPostTypes();
			} else {
				echo '<tr><td>Missing class:</td><td>'.$moduleClass.'</td></tr>';
			}
			
			/*echo '<tr>';
			echo '<th scope="row">';
			echo '<label>';
			echo 'Post Types for '.$moduleClass;
			echo '</label>';
			echo '</th>';
			echo '<td>'.print_r($postTypes, true).'</td>';
			echo '</tr>';*/
			
			echo '<tr id="post-types-' . esc_attr( $key ) . '" class="post-type-row" ' . ( $isActive ? '' : 'style="display:none;"' ) . '>';
			echo '<td colspan="2" style="padding-left: 30px;">';
			//
			foreach ( $postTypes as $slug => $label ) {
			//foreach ( $postTypes as $postType ) {
				// Post Types enabled by default if no setting exists
				$isEnabled = isset( $enabledPostTypes[ $key ] ) && in_array( $slug, $enabledPostTypes[ $key ], true );
				
				echo '<label style="display:block;">';
				echo '<input type="checkbox" name="whx4_plugin_settings[enabled_post_types][' . esc_attr( $key ) . '][]" value="' . esc_attr( $slug ) . '" ' . checked( $isEnabled, true, false ) . ' />';
				echo ' Enable <code>' . esc_html( $slug ) . '</code>: ' . esc_html( $label );
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

}
