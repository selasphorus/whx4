<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class GroupCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'group_category',
            'plural_slug'  => 'group_categories',
            'object_types' => ['group'],
            'hierarchical' => true,
        ], $term);
    }
}
