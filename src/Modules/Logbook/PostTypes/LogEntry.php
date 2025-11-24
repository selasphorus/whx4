<?php

namespace atc\WHx4\Modules\Logbook\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class LogEntry extends PostTypeHandler
{
    public function __construct(?\WP_Post $post = null) {
        $config = [
            'slug'        => 'log_entry',
            'plural_slug' => 'log_entries',
            'labels'      => [
                //'name' => 'Logbook',
                'menu_name' => 'Logbook',
                //'search_items' => 'Search Logbook',
            ],
            //'rewrite' => ['slug' => 'whimsy'],
            'menu_icon'   => 'dashicons-book',
            'supports' => ['title', 'author', 'editor', 'revisions'],
            //'capability_type' => ['secret','secrets'],
            //'hierarchical' => false,
            //'taxonomies' => ['admin_tag', 'secret_category'],
        ];

        parent::__construct( $config, $post );
    }

    public function boot(): void
    {
        parent::boot(); // Optional if you add shared logic later
    }

    // Other methods related to the Thing PostType...
}

