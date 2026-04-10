<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class Building extends PostTypeHandler
{
	protected static function defineConfig(): array
    {
        return [
            'slug'             => 'building',
            'menu_icon'        => 'dashicons-building',
			'capability_type'  => ['place','places'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			//'taxonomies'       => ['group_category'],
            //'default_taxonomy' => 'group_category',
            'labels'           => [
				//'add_new_item' => 'Gather a new Group',
            ],
			//'hierarchical' => true,
        ];
    }

    public function boot(): void
    {
        parent::boot();
    }
}
