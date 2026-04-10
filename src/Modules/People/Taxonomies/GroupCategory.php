<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class GroupCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'group_category',
            'plural_slug'  => 'group_categories',
            'object_types' => ['group'],
            'hierarchical' => true,
        ];
    }
}
