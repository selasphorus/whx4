<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Core\Traits\HasTypeProperties;

class Address extends PostTypeHandler
{
	use HasTypeProperties;

    public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'address',
			'plural_slug' => 'addresses',
			'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				//'not_found' => 'No people loitering nearby',
			],
			//'menu_icon'   => 'dashicons-palmtree',
			//'supports' => ['title', 'editor'],
			//'taxonomies' => [ 'person_category', 'person_title', 'admin_tag' ],
		];
	
		parent::__construct($config, 'post_type', $post);
	}
    
}

?>