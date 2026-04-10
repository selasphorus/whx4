<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class PersonCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'person_category',
            'plural_slug'  => 'person_categories',
            'object_types' => ['person'],
            'hierarchical' => true,
        ];
    }
}
