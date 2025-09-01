<?php

namespace atc\WHx4\Modules\Core\PostTypes;

use WP_Post; // Is this necessary?
use atc\WHx4\Core\PostTypeHandler; //use atc\WHx4\Core\PostTypes\PostTypeHandler;

// This handler stub is necessary to faciliate the registration of Subtypes of core WP post types

final class Page extends PostTypeHandler
{
    public function __construct(WP_Post|null $post=null)
    {
        $config = [
            'slug'   => 'page',
            'labels' => [
                'name'          => 'Pages',
                'singular_name' => 'Page',
            ],
        ];

        parent::__construct($config, $post);
    }

    public function boot(): void
    {
        parent::boot(); // Optional if you add shared logic later

        /*$this->applyTitleArgs( $this->getSlug(), [
            'line_breaks'    => true,
            'show_subtitle'  => true,
            'hlevel_sub'     => 4,
            'called_by'      => 'Post::boot',
        ]);*/
    }
}
