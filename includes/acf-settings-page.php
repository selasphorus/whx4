<?php
/**
 * ACF demo Options Page: "Site Settings"
 *
 * @link https://www.advancedcustomfields.com/resources/options-page/
 */

/**
 * Check if ACF PRO is active and function exists
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	add_action( 'acf/init', 'whx3_register_options_page' );
}

function whx3_register_options_page() {
	// Add the top-level page
	acf_add_options_page(
		array(
			'page_title' => 'WHX3 Settings',
			'menu_slug'  => 'whx3-settings',
			'redirect'   => false,
		)
	);

	// Add the sub-page
	/*
	acf_add_options_sub_page(
		array(
			'page_title'  => 'Contact Information',
			'menu_slug'   => 'contact-information',
			'parent_slug' => 'site-settings',
		)
	);
	*/

	// Add 'Modules & Settings' field group
	acf_add_local_field_group(
		array(
			'key'      => 'whx3_group_modules',
			//'key'      => 'group_6511a57f5680c',
			'title'    => 'Modules &amp; Settings',
			'fields'   => array(
				array(
					'key'           => 'field_whx3_modules',
					//'key'           => 'field_6511a57fcbe7e',
					'label'         => 'Active Modules',
					'name'          => 'whx3_modules',
					'type'          => 'checkbox',
					'instructions' => 'Select the modules to activate. If using Events Manager, leave Locations and Events deactivated.',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '33',
						'class' => '',
						'id' => '',
					),
					'choices' => array(
						'people' => 'People',
						'groups' => 'Groups',
						'locations' => 'Locations',
						'buildings' => 'Buildings',
						'events' => 'Events',
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
					'key'           => 'field_whx3_use_custom_caps',
					'label'         => 'Use custom capabilities?',
					'name'          => 'whx3_use_custom_caps',
					'type'          => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '15',
						'class' => '',
						'id' => '',
					),
					/*'choices' => array(
						'xxx' => 'XXX',
					),*/
					'default_value' => array(
					),
					'return_format' => 'value',
					'layout' => 'horizontal',
					'aria-label' => '',
					'relevanssi_exclude' => 0,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'whx3-settings',
						//'value'    => 'contact-information',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_6511a5e8c1c15',
			'title'    => 'Notification Bar',
			'fields'   => array(
				array(
					'key'        => 'field_6511a5e897814',
					'label'      => 'Notification Bar',
					'name'       => 'whx3_notification_bar_group',
					'aria-label' => '',
					'type'       => 'group',
					'layout'     => 'row',
					'sub_fields' => array(
						array(
							'key'           => 'field_6511a5f597815',
							'label'         => 'Notification On/Off',
							'name'          => 'whx3_notification_onoff',
							'type'          => 'true_false',
							'message'       => 'Should the site-wide Notification Bar be showing?',
							'default_value' => 1,
							'ui_on_text'    => 'On',
							'ui_off_text'   => 'Off',
							'ui'            => 1,
						),
						array(
							'key'               => 'field_6511a5f597816',
							'label'             => 'Notification Message',
							'name'              => 'whx3_notification_message',
							'type'              => 'textarea',
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'whx3_notification_onoff',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
						),
					),
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'site-settings',
					),
				),
			),
		)
	);
}
