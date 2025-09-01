<?php

namespace atc\WHx4\Modules\Places\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class VenueCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'venue_category',
            'plural_slug'  => 'venue_categories',
            'object_types' => ['venue'],
            'hierarchical' => true,
        ], $term);
    }
}
