<?php

namespace atc\WHx4\Modules\Projects\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Project extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null) {
        $config = [
            'slug'        => 'project',
            //'plural_slug' => 'projects',
            //'rewrite' => ['slug' => 'whimsy'],
            'menu_icon'   => 'dashicons-buddicons-replies',
            'capability_type' => ['project','projects'],
            //'hierarchical' => false,
            'taxonomies' => [ 'project_category' ], //'admin_tag',
        ];

        parent::__construct( $config, $post );
    }

    public function boot(): void
    {
        parent::boot(); // Optional if you add shared logic later
    }

    // Other methods related to the Project PostType...
}

