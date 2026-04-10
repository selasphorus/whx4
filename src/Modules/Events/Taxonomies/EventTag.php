<?php

namespace atc\WHx4\Modules\Events\Taxonomies;

use atc\WXC\Taxonomies\TaxonomyHandler;

class EventTag extends TaxonomyHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'         => 'event_tag', // EM slug: event-tags -- TODO: figure out migration plan
            'plural_slug'  => 'event_tags',
            'object_types' => ['event'],
            'hierarchical' => false,
        ];
    }
}
