<?php

namespace atc\WHx4\Modules\Events\Fields;

use atc\WXC\Contracts\FieldGroupInterface;

final class EventFields implements FieldGroupInterface
{
    public static function register(): void
    {
        if ( !function_exists('acf_add_local_field_group') ) return;
        
        // WHX4 Event Series: Additional Fields (group_whx4_event_series)
		acf_add_local_field_group( array(
			'key' => 'group_whx4_event_series',
			//'key' => 'group_627c0014d01c2',
			'title' => 'WHX4 Event Series: Additional Fields',
			'fields' => array(
				array(
					'key'    =>    'field_whx4_62a36344e2998',
					//'key' => 'field_62a36344e2998',
					'label' => 'Related Events (v2 bidirectional)',
					'name' => 'series_events',
					'aria-label' => '',
					'type' => 'relationship',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'post_type' => array(
						0 => 'event',
					),
					'taxonomy' => '',
					'filters' => array(
						0 => 'search',
						1 => 'taxonomy',
					),
					'elements' => '',
					'min' => '',
					'max' => '',
					'return_format' => 'id',
					'bidirectional' => 1,
					'bidirectional_target' => array(
						0 => 'field_whx4_632a27ff8450f',
					),
					//'bidirectional_target' => array(),
				),
				array(
					'key'    =>    'field_whx4_627c002205d63',
					//'key'    =>    'field_whx4_related_events',
					//'key' => 'field_627c002205d63',
					'label' => 'Related Events (v1)',
					'name' => 'related_events',
					'aria-label' => '',
					'type' => 'relationship',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'post_type' => array(
						0 => 'event',
					),
					'taxonomy' => '',
					'filters' => array(
						0 => 'search',
						1 => 'taxonomy',
					),
					'elements' => '',
					'min' => '',
					'max' => '',
					'return_format' => 'id',
					'bidirectional_target' => array(),
				),
				array(
					'key'    =>    'field_whx4_627c005e05d64',
					//'key'    =>    'field_whx4_series_hide_day_titles',
					//'key' => 'field_627c005e05d64',
					'label' => 'Hide Day Titles?',
					'name' => 'hide_day_titles',
					'aria-label' => '',
					'type' => 'true_false',
					'instructions' => 'Hide day titles from display for all events in the series.',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 0,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
				array(
					'key'    =>    'field_whx4_627c008405d65',
					//'key'    =>    'field_whx4_prepend_series_title',
					//'key' => 'field_627c008405d65',
					'label' => 'Prepend Series Title for Individual Events?',
					'name' => 'prepend_series_title',
					'aria-label' => '',
					'type' => 'true_false',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'message' => '',
					'default_value' => 0,
					'ui' => 0,
					'ui_on_text' => '',
					'ui_off_text' => '',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'event_series',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'acf_after_title',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'field',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 0,
		) );
        
    }
}