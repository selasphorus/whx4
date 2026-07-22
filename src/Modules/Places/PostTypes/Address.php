<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class Address extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'address',
            'plural_slug'      => 'addresses',
            'menu_icon'        => 'dashicons-location-alt',
			'capability_type'  => ['place','places'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
        ];
    }

    public function boot(): void
    {
        parent::boot();
    }
}
