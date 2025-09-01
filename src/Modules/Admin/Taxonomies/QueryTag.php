<?php

namespace atc\WHx4\Modules\Admin\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class QueryTag extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'query_tag',
            'plural_slug'  => 'query_tags',
            'object_types' => ['admin_note', 'note'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
