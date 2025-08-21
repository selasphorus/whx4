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
            'fields' => [],
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
