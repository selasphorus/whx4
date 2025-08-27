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
			'menu_icon'   => 'dashicons-groups',
			//'supports' => ['title', 'editor'],
			'taxonomies' => [ 'person_category', 'person_title', 'admin_tag' ],
		];

		parent::__construct( $config, $post );
	}

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later

		$this->applyTitleArgs( $this->getSlug(), [
			'line_breaks'    => true,
			'show_subtitle'  => true,
			'hlevel_sub'     => 4,
			'called_by'      => 'Person::boot',
			//'append'         => " dates: ".$this->getPersonDates( $this->getPostID() ),
		]);
	}

	//
	//public function getCustomTitleArgs(): array
	public function getCustomTitleArgs( \WP_Post $post ): array
	{
		$postId = $post->ID;
		//$postId = get_the_ID(); // or inject dynamically elsewhere -- ???
		if ( ! $postId ) {
			return [];
		}

		$dates = $this->getPersonDates( $postId );

		return [
			'append' => $dates,
		];
	}


	//
	protected function getPersonDates($post_id, $styled = false)
	{
		error_log( '=== Person::getPersonDates() ===' );
		error_log( 'post_id: ' . $post_id );

		// Init vars
		$info = ""; // init

		// Try ACF get_field instead?
		$birth_year = get_post_meta( $post_id, 'birth_year', true );
		$death_year = get_post_meta( $post_id, 'death_year', true );
		$dates = get_post_meta( $post_id, 'dates', true );

		if ( !empty($birth_year) && !empty($death_year) ) {
			$info .= "(".$birth_year."-".$death_year.")";
		} else if ( !empty($birth_year) ) {
			$info .= "(b. ".$birth_year.")";
		} else if ( !empty($death_year) ) {
			$info .= "(d. ".$death_year.")";
		} else if ( !empty($dates) ) {
			$info .= "(".$dates.")";
		}

		if ( !empty($info) ) {
			if ( $styled ) {
				$info = '<span class="person_dates">&nbsp;'.$info.'</span>';
			} else {
				$info = ' '.$info; // add space before dates str
			}
		}

		return $info;
	}
}

