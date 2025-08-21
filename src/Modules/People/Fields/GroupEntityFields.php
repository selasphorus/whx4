<?php

namespace atc\WHx4\Modules\People\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;

final class GroupEntityFields implements FieldGroupInterface
{
    public static function register(): void
    {
        if ( !function_exists('acf_add_local_field_group') ) return;

        acf_add_local_field_group( [
            'key' => 'group_whx4_groupentity', //'key' => 'group_624775b57f8df',
            'title' => 'WHx4 Group: Additional Fields',
            'fields' => [
                [
                    'key' => 'field_whx4_665a0045f05e9',
                    //'key' => 'field_whx4_group_website',
                    //'key' => 'field_665a0045f05e9',
                    'label' => 'Primary Website',
                    'name' => 'website',
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
                    'relevanssi_exclude' => 0,
                    'default_value' => '',
                    'placeholder' => '',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'group',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ] );
    }
}
