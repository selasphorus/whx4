<?php

namespace atc\WHx4\Modules\Places\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;

// TODO: rename all fields (keys/names) according to whx4 naming conventions
final class LinkFields implements FieldGroupInterface
{
    public static function register(): void
    {
        //error_log( '=== XXXFields: register()) ===' );
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
            'key' => 'group_641509845a51d',
            'title' => 'Link Details [whx4-ok]',
            'fields' => array(
                array(
                    'key' => 'field_64150985e54f2',
                    'label' => 'URL',
                    'name' => 'url',
                    'aria-label' => '',
                    'type' => 'url',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'formula' => '',
                    'calculated_format' => '',
                    'blank_if_zero' => 0,
                    'readonly' => 0,
                    'default_value' => '',
                    'placeholder' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'link',
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
