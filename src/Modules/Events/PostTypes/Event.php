<?php

namespace atc\WHx4\Modules\Events\PostTypes;

use atc\WHx4\Core\PostTypeHandler;

class Event extends PostTypeHandler
{
    public function __construct(WP_Post|null $post = null)
    {
		$slug = apply_filters( 'whx4_events_post_type_slug', 'whx4_event' );

		$config = [
			'slug'        => 'whx4_event',
            'supports'    => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
			//'taxonomies' => [ 'event_category', 'event_tag', 'admin_tag' ],
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
			'called_by'      => 'Event::boot',
			//'append'         => 'TEST: ',
		]);
	}
}
