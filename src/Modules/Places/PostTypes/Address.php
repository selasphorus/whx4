<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use WXC\Core\PostTypeHandler;

class Address extends PostTypeHandler
{
    public function __construct(?\WP_Post $post = null) {
		$config = [
			'slug'        => 'address',
			'plural_slug' => 'addresses',
			/*'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				//'not_found' => 'No people loitering nearby',
			],*/
			'menu_icon'   => 'dashicons-location-alt',
			'capability_type' => ['place','places'],
			'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			//'taxonomies' => [ 'person_category', 'person_title', 'admin_tag' ],
		];

		parent::__construct( $config, $post );
	}

    public function boot(): void
    {
        parent::boot();
    }
}
