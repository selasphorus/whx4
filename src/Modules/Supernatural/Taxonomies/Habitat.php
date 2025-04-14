<?php

class Habitat extends TaxonomyHandler
{
    public function __construct(WP_Term|null $term = null) {
        parent::__construct([
            'slug'         => 'habitat',
            'plural_slug'  => 'habitats',
            'object_types' => [ 'monster' ],
            'hierarchical' => true,
        ], 'taxonomy', $term);
    }
}
