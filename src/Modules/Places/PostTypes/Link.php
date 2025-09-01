<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Link extends PostTypeHandler
{
	public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'link',
			//'plural_slug' => 'links',
			/*'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				//'not_found' => 'No people loitering nearby',
			],*/
			'menu_icon'   => 'dashicons-admin-links',
			'capability_type' => ['place','places'],
			//'supports' => ['title', 'editor'],
			'taxonomies' => [ 'link_category' ],
		];

		parent::__construct( $config, $post );
	}

    public function boot(): void
    {
        parent::boot();
    }
}
