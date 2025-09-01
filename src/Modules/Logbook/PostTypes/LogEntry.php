<?php

namespace atc\WHx4\Modules\Logbook\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class LogEntry extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null) {
        $config = [
            'slug'        => 'log_entry',
            'plural_slug' => 'log_entries',
            //'rewrite' => ['slug' => 'whimsy'],
            //'menu_icon'   => 'dashicons-palmtree',
            //'capability_type' => ['secret','secrets'],
            //'hierarchical' => false,
            //'taxonomies' => ['admin_tag', 'secret_category'],
            'menu_name' => 'Logbook',
            'name' => 'Logbook',
            'search_items' => 'Search Logbook',
        ];

        parent::__construct( $config, $post );
    }

    public function boot(): void
    {
        parent::boot(); // Optional if you add shared logic later
    }

    // Other methods related to the Thing PostType...
}

