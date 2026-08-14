<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class EventSeries extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'event_series',
            'plural_slug'      => 'event_series',
            //'menu_icon'        => 'dashicons-location-alt',
			'capability_type'  => ['event','events'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
        ];
    }

    public function boot(): void
    {
        parent::boot();
    }
}