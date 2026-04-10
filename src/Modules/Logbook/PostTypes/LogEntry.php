<?php

namespace atc\WHx4\Modules\Logbook\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;

class LogEntry extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'log_entry',
            'plural_slug'      => 'log_entries',
            'menu_icon'        => 'dashicons-book',
			//'capability_type'  => ['group','groups'],
            'supports'         => ['title', 'author', 'editor', 'revisions'],
			//'taxonomies'       => ['group_category'],
            //'default_taxonomy' => 'group_category',
            'labels'           => [
                'menu_name' => 'Logbook',
            ],
        ];
    }

    public function boot(): void
    {
        parent::boot(); // Optional if you add shared logic later
    }

    // Other methods related to the Thing PostType...
}

