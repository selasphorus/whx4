<?php

namespace atc\WHx4\Modules\Supernatural\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
//use atc\WHx4\Core\Traits\HasTypeProperties;

class Monster extends PostTypeHandler 
{
	//use HasTypeProperties;

    public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'monster',
			//'plural_slug' => 'monsters',
			'labels'      => [
				'add_new_item' => 'Summon New Monster',
				'not_found'    => 'No monsters lurking nearby',
			],
			'menu_icon'   => 'dashicons-palmtree',
		];
	
		parent::__construct($config, 'post_type', $post);
	}
    
    /*
    // Usage in code:
    $handler = new Monster( get_post(123) );
	if ($handler->isPost()) {
		$title = get_the_title( $handler->getObject() );
	}	
	*/

	public function getCPTContent() {
		return "hello";
	}
    
    public function get_color() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return isset($this->post) ? get_post_meta($this->post->ID, '_monster_color', true) : null;
    }

    // Other methods related to the monster...
}

