<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class PersonTag extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'person_tag',
            'object_types' => ['person'],
            'hierarchical' => false,
        ], $term);
    }
}
