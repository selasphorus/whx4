<?php

namespace atc\WHx4\Modules\Supernatural\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

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
		];

		parent::__construct($config, 'post_type', $post);
	}

	public function boot(): void
	{
	    error_log( '=== Monster class boot() ===' );
	    parent::boot(); // Optional if you add shared logic later

		$this->applyTitleArgs( $this->getSlug(), [
			'line_breaks'    => true,
			'show_subtitle'  => true,
			'hlevel_sub'     => 4,
			'called_by'      => 'Monster::boot',
			'append'         => 'TESTTF',
		]);
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

