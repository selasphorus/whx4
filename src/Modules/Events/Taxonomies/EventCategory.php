<?php

namespace atc\WHx4\Modules\Events\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class EventCategory extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'event_category', // EM slug: event-categories -- TODO: figure out migration plan
            'plural_slug'  => 'event_categories',
            'object_types' => ['event'],
            'hierarchical' => true,
        ], $term);
    }
}
