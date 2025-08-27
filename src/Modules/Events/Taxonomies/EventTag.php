<?php

namespace atc\WHx4\Modules\Events\Taxonomies;

use atc\WHx4\Core\TaxonomyHandler;

class EventTag extends TaxonomyHandler
{
    public function __construct(\WP_Term|null $term = null)
    {
        parent::__construct([
            'slug'         => 'event_tag', // EM slug: event-tags
            //'plural_slug'  => 'event_tags',
            'object_types' => ['event'],
            'hierarchical' => true,
        ], $term);
    }
}
