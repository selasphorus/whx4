<?php

namespace atc\WHx4\Modules\Snippets\PostTypes;

use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Logger;

class Snippet extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'snippet',
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
        ];
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

