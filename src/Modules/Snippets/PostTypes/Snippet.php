<?php

namespace atc\WHx4\Modules\Snippets\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Logger;

class Snippet extends PostTypeHandler
{
    public function __construct(?\WP_Post $post = null)
    {
		$config = [
			'slug'        => 'snippet',
			'plural_slug' => 'snippets',
			'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				//'not_found' => 'No people loitering nearby',
			],
			//'menu_icon'   => 'dashicons-groups', // could use dashicons-id-alt instead
			//'capability_type' => ['snippet','snippets'],
			'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions', 'page-attributes'],
			//'taxonomies' => [ 'person_category', 'person_role' ], //, 'admin_tag'
			//'hierarchical' => true,
		];

		parent::__construct( $config, $post );
	}

	public function boot(): void
	{
	    parent::boot(); // Optional if you add shared logic later
	}

	public function getPersonDates(?\WP_Post $post = null, $styled = false): string
	{
		$info = ""; // init

        $p = $post ?? $this->getPost();
        if ( empty($p) ) { return "no post found"; } // $info
        $pID = $p->ID;

		// Try ACF get_field instead?
		$birth_year = get_post_meta( $pID, 'birth_year', true );
		$death_year = get_post_meta( $pID, 'death_year', true );
		$dates = get_post_meta( $pID, 'dates', true );

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

