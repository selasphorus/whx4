<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Venue extends PostTypeHandler
{
	public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'venue',
			//'plural_slug' => 'venues',
			/*'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				'not_found' => 'Nowhere to go',
			],*/
			'menu_icon'   => 'dashicons-location', // could use dashicons-admin-multisite instead
			//'supports' => ['title', 'editor'],
			'taxonomies' => ['venue_category', 'admin_tag'],
		];

		parent::__construct( $config, $post );
	}

    public function boot(): void
    {
        parent::boot();
    }
}

?>
