<?php

namespace atc\WHx4\Modules\[ModuleName]\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class ThingCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'thing_category',
            'plural_slug'  => 'thing_categories',
            'object_types' => ['thing'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
