<?php

namespace atc\WHx4\Modules\Supernatural\PostTypes;

use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Modules\Supernatural\Taxonomies\Habitat;

class Monster extends PostTypeHandler
{
	public function __construct(WP_Post|null $post = null) {
		$config = [
			'slug'        => 'monster',
			//'plural_slug' => 'monsters',
			'labels'      => [
				'add_new_item' => 'Summon New Monster',
				'not_found'    => 'No monsters lurking nearby',
			],
			'menu_icon'   => 'dashicons-palmtree',
			'taxonomies'   => [ 'habitat' ],
			//'capability_type' => ['monster', 'monsters'],
			//'map_meta_cap'       => true,
		];

		parent::__construct( $config, $post );
	}

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later

	    // Apply Title Args -- this modifies front-end display only
		$this->applyTitleArgs( $this->getSlug(), [
			'line_breaks'    => true,
			'show_subtitle'  => true,
			'hlevel_sub'     => 4,
			'called_by'      => 'Monster::boot',
			'append'         => ' {Rowarrr!}',
		]);
	}

    /*
    // Usage in code:
    $handler = new Monster( get_post(123) );
	if ($handler->isPost()) {
		$title = get_the_title( $handler->getObject() );
	}
	*/

	public function getCustomContent()
	{
		return "hello";
	}

    public function getColor() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return isset($this->post) ? get_post_meta($this->post->ID, '_monster_color', true) : null;
    }

    // Other methods related to the monster...
}

