<?php

namespace atc\WHx4\Modules\Projects\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Project extends PostTypeHandler
{
    public function __construct(?\WP_Post $post = null) {
        $config = [
            'slug'        => 'project',
            //'plural_slug' => 'projects',
            /*'labels'      => [
                //'name' => 'SpecialName',
                //'menu_name' => 'SpecialName',
            ],*/
            //'rewrite' => ['slug' => 'whimsy'],
            'menu_icon'   => 'dashicons-buddicons-replies', // alt could use dashicons-welcome-write-blog
            'capability_type' => ['project','projects'],
            'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
            //'hierarchical' => false,
            'taxonomies' => [ 'project_category' ], //'admin_tag',
        ];

        parent::__construct( $config, $post );
    }

    public function boot(): void
    {
        parent::boot();
    }

    // Other methods related to the Project PostType...
}

