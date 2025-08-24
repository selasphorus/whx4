<?php

namespace atc\WHx4\Modules\Supernatural\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class Habitat extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'habitat',
            'plural_slug'  => 'habitats',
            'object_types' => ['monster'],
            'hierarchical' => true,
        ], $term);
    }
}
