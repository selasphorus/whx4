<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class Link extends PostTypeHandler
{
	protected static function defineConfig(): array
    {
        return [
            'slug'             => 'link',
            'menu_icon'        => 'dashicons-admin-links',
			'capability_type'  => ['place','places'],
            //'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions', 'page-attributes'],
			'taxonomies'       => ['link_category'],
            'default_taxonomy' => 'link_category',
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
