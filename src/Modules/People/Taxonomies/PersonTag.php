<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class PersonTag extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'person_tag',
            'plural_slug'  => 'person_tags',
            'object_types' => ['person'],
            'hierarchical' => false,
        ];
    }
}
