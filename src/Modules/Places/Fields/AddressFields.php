<?php

namespace atc\WHx4\Modules\Places\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;

// TODO: rename all fields (keys/names) according to whx4 naming conventions
final class AddressFields implements FieldGroupInterface
{
    public static function register(): void
    {
        //error_log( '=== AddressFields: register()) ===' );
        if ( !function_exists('acf_add_local_field_group') ) return;

        //use atc\WHx4\Migrations\FieldKeyMigrator;
        /*
        // Migrate
        FieldKeyMigrator::migrate([
            'field_624775f4b6221' => [
                'new_field_key' => 'field_whx4_modulename_first_name',
                'old_meta_key'  => 'first_name',
                'new_meta_key'  => 'whx4_modulename_first_name',
            ],
            'field_abc123xyz456' => [
                'new_field_key' => 'field_whx4_notes',
                // no meta_key rename
            ],
        ]);
        */

        acf_add_local_field_group( array(
            'key' => 'group_65fa06690453c',
            'title' => 'Location Details',
            'fields' => array(
                array(
                    'key' => 'field_65fa0669cf4ed',
                    'label' => 'Location Address',
                    'name' => 'location_address',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06945d685',
                    'label' => 'Location City/Town',
                    'name' => 'location_city',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06a55d686',
                    'label' => 'Location State',
                    'name' => 'location_state',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06b35d687',
                    'label' => 'Location Postcode',
                    'name' => 'location_postcode',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06c25d688',
                    'label' => 'Location Region',
                    'name' => 'location_region',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06c85d689',
                    'label' => 'Location Country',
                    'name' => 'location_country',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06d65d68a',
                    'label' => 'Location Latitude',
                    'name' => 'location_latitude',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_65fa06dc5d68b',
                    'label' => 'Location Longitude',
                    'name' => 'location_longitude',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'location',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'address',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ) );
    }
}


