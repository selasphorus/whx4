<?php
/**
 * WHx4 Options Page: "Site Settings"
 *
 * @link https://www.advancedcustomfields.com/resources/options-page/
 */

/**
 * Check if ACF PRO is active and function exists
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	add_action( 'acf/init', 'whx4_register_options_page' );
}

function whx4_register_options_page() {

	// Add the top-level page
	acf_add_options_page(
		array(
			'page_title' => 'WHx4 Settings',
			'menu_slug'  => 'whx4_settings',
			'redirect'   => false,
		)
	);

	// Add 'Modules & Settings' field group
	acf_add_local_field_group(
		array(
			'key'      => 'group_whx4_settings',
			'title'    => 'WHx4 Settings',
			'fields'   => array(
				array(
					'key'	=>	'field_whx4_general_settings',
					'label' => 'Modules',
					'name' => 'whx4_modules_tab',
					'aria-label' => '',
					'type' => 'tab',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '22',
						'class' => 'float-left',
						'id' => '',
					),
					'relevanssi_exclude' => 0,
					'placement' => 'top',
					'endpoint' => 0,
					'selected' => 0,
				),
				array(
					'key'           => 'field_whx4_modules', // 'field_6511a57fcbe7e',
					'label'         => 'Active Modules',
					'name'          => 'whx4_active_modules', // rename as 'active_modules' -- check options table structure to see if this will cause confusion/ambiguity
					'type'          => 'checkbox',
					'instructions' => 'Select the modules to activate.',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '25',
						'class' => '',
						'id' => '',
					),
					'choices' => array(
						'people' => 'People',
						'groups' => 'Groups',
						'places' => 'Places',
						//'locations' => 'Locations',
						'events' => 'Events',
						'venues' => 'Venues',
						'addresses' => 'Addresses',
						'buildings' => 'Buildings',
					),
					'default_value' => array(
					),
					'return_format' => 'value',
					'allow_custom' => 0,
					'layout' => 'vertical',
					'toggle' => 0,
					'save_custom' => 0,
					'custom_choice_button_text' => 'Add new choice',
					'aria-label' => '',
					'relevanssi_exclude' => 0,
				),
				array(
					'key'           => 'field_whx4_use_custom_caps',
					'label'         => 'Use custom capabilities?',
					'name'          => 'whx4_use_custom_caps',
					'type'          => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '15',
						'class' => '',
						'id' => '',
					),
					'default_value' => array(
					),
					'return_format' => 'value',
					'layout' => 'horizontal',
					'aria-label' => '',
					'relevanssi_exclude' => 0,
				),
				array(
					'key'	=>	'field_whx4_event_formatting',
					'label' => 'Event Formatting',
					'name' => 'event_formatting_tab',
					'aria-label' => '',
					'type' => 'tab',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '22',
						'class' => 'float-left',
						'id' => '',
					),
					'relevanssi_exclude' => 0,
					'placement' => 'top',
					'endpoint' => 0,
					'selected' => 0,
				),
				array(
					'key'	=>	'field_whx4_event_list_item_format',
					'label' => 'Event List Item Format',
					'name' => 'whx4_event_list_item_format',
					'aria-label' => '',
					'type' => 'textarea',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'placeholder' => '',
					'maxlength' => '',
					'rows' => '',
					'new_lines' => '',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'whx4_settings',
					),
				),
			),
		)
	);
	
}
