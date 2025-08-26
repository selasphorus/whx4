<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class PersonCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'person_category',
            'plural_slug'  => 'person_categories',
            'object_types' => ['person'],
            'hierarchical' => true,
        ], $term);
    }
}
