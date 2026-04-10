<?php

namespace atc\WHx4\Modules\Events\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class EventCategory extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'event_category', // EM slug: event-categories -- TODO: figure out migration plan
            'plural_slug'  => 'event_categories',
            'object_types' => ['event'],
            'hierarchical' => true,
        ];
    }
}
