<?php

namespace atc\WHx4\Modules\Places\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class VenueCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'venue_category',
            'plural_slug'  => 'venue_categories',
            'object_types' => ['venue'],
            'hierarchical' => true,
        ];
    }
}
