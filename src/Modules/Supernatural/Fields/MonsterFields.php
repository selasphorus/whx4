<?php

namespace atc\WHx4\Modules\Supernatural\Fields;

use atc\WHx4\Core\Contracts\FieldGroupInterface;

class MonsterFields implements FieldGroupInterface
{
    public static function register(): void
    {
        //error_log( '=== MonsterFields: register()) ===' );
        if ( !function_exists('acf_add_local_field_group') ) return;

        acf_add_local_field_group([
            'key' => 'group_monster_details',
            'title' => 'Monster Details',
            'fields' => [
                [
                    'key' => 'field_secret_name',
                    'label' => 'Secret Name',
                    'name' => 'secret_name',
                    'type' => 'text',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'monster',
                    ],
                ],
            ],
        ]);
    }
}
