<?php

namespace atc\WHx4\Modules\Places\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class LinkCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'link_category',
            'plural_slug'  => 'link_categories',
            'object_types' => ['link'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
