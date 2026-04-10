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
			//'rewrite'          => ['slug' => 'ledger'],
            'menu_icon'        => 'dashicons-location-alt',
			'capability_type'  => ['place','places'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			//'taxonomies'       => ['group_category'],
            //'default_taxonomy' => 'group_category',
        ];
    }

    public function boot(): void
    {
        parent::boot();
    }
}
