<?php

namespace atc\WHx4\Modules\People\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class PersonRole extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'person_role',
            'object_types' => ['person'],
            'hierarchical' => true,
            'meta_box_cb'  => false,
        ], $term);
    }
}
