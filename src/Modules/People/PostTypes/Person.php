<?php

namespace atc\WHx4\Modules\People\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Person extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'person',
			'plural_slug' => 'people',
			'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				'not_found' => 'No people loitering nearby',
			],
			//'menu_icon'   => 'dashicons-palmtree',
			//'supports' => ['title', 'editor'],
			'taxonomies' => [ 'person_category', 'person_title', 'admin_tag' ],
		];

		parent::__construct($config, 'post_type', $post);
	}

}

