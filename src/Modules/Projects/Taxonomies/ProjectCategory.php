<?php

namespace atc\WHx4\Modules\Projects\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class ProjectCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'project_category',
            'plural_slug'  => 'project_categories',
            'object_types' => ['project'], // array of post types (by slug) to which this taxonomy applies
            'hierarchical' => true,
        ], $term);
    }
}
