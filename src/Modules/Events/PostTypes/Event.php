<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
//use atc\WHx4\Core\Traits\HasTypeProperties;

class Event extends PostTypeHandler 
{
	//use HasTypeProperties;

    public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'event',
			//'plural_slug' => 'monsters',
			/*'labels'      => [
				'add_new_item' => 'Summon New Monster',
				'not_found'    => 'No monsters lurking nearby',
			],*/
			//'menu_icon'   => 'dashicons-palmtree',
		];
	
		parent::__construct($config, 'post_type', $post);
	}
	
	/*
	//public $event_id;
	public $post_id;
	public $event_parent;
	public $event_slug;
	public $event_owner;
	public $event_name;
	*/
	
	public function getCPTContent() {
		
		$post_id = $this->get_post_id();
		
	}
}
