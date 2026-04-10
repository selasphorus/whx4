<?php

namespace atc\WHx4\Modules\Projects\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class Project extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'project',
            'menu_icon'        => 'dashicons-buddicons-replies', // alt could use dashicons-welcome-write-blog
			'capability_type'  => ['project','projects'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			'taxonomies'       => ['project_category'],
            'default_taxonomy' => 'project_category',
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

    // Other methods related to the Project PostType...
}

