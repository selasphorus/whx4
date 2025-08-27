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
	    // TODO: consider alternative approaches to allow for more customization? e.g. different args as with old SDG get_person_display_name method
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

	public function getCustomContent(WP_Post $post): string
	//public function getCustomContent()
	{
		//return "Hello, Monster!";

		/*$habitat = get_post_meta($post->ID, 'rex_supernatural_habitat', true);
		if ( ! $habitat ) {
			return '';
		}*/
		$habitat = "The Great Dismal Swamp"; // tft

		$html  = '<div class="rex-custom-content rex-monster-meta">';
		$html .= '<strong>Habitat:</strong> ' . esc_html($habitat);
		$html .= '</div>';

		return $html;
	}

    public function getColor() {
        // Assuming the color is stored as a custom field, for example, _monster_color
        return isset($this->post) ? get_post_meta($this->post->ID, '_monster_color', true) : null;
    }

    // Other methods related to the monster...
}

