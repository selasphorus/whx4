<?php

namespace atc\WHx4\Modules\People\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Person extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null)
    {
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

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later

		$this->applyTitleArgs( $this->getSlug(), [
			'line_breaks'    => true,
			'show_subtitle'  => true,
			'hlevel_sub'     => 4,
			'called_by'      => 'Person::boot',
			//'append'         => 'TESTTF',
		]);
	}

	/*public function getCPTContent()
	{
		$post_id = $this->get_post_id();
	}*/
}

