<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class PersonRole extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'person_role',
            'plural_slug'  => 'person_roles',
            'object_types' => ['person'],
            'hierarchical' => true,
            'meta_box_cb'  => false,
        ];
    }
}
