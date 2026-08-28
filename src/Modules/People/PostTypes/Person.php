<?php

namespace atc\WHx4\Modules\People\PostTypes;

use atc\WXC\Logger;
use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Query\PostQuery;

class Person extends PostTypeHandler
{
    protected static function defineConfig(): array
    {
        return [
            'slug'             => 'person',
            'plural_slug'      => 'people',
            'menu_icon'        => 'dashicons-groups',
            'capability_type'  => ['person', 'people'],
            'supports'         => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions', 'page-attributes'],
            'taxonomies'       => ['person_category', 'person_role'],
            'default_taxonomy' => 'person_category',
            'labels'           => [
                'not_found' => 'No people loitering nearby',
            ],
            'noindex'          => true,
        ];
    }

    public function boot(): void
	{
	    parent::boot(); // Optional -- in case we add shared logic later
	    self::registerTitleDefaults(static::getSlug(), [
			'line_breaks'   => true,
			'show_subtitle' => true,
			'hlevel_sub'    => 4,
			//'append'         => 'TEST: ',
			//'called_by'      => 'Person::boot',
		]);
	}
	
	/**
	 * Prepare all data needed for the content view
	 * This keeps the view clean and dependency-free
	 * 
	 * @return array Variables ready for view consumption
	 */
	public function prepareViewData(): array
	{
		return [
			//'status' => $this->getStatus(),
			'dates' => $this->getPersonDates(),			
			'compositions' => $this->getPersonCompositions(),
			//'viewData' => $this->prepareTransactionStatsForView(),
			'postMeta' => $this->getPostMeta(),
		];
	}

	public function getCustomTitleArgs( \WP_Post $post ): array
	{
		$pID = $post->ID;
		//$pID = get_the_ID(); // or inject dynamically elsewhere -- ???
		if ( ! $pID ) {
			return [];
		}

		$dates = $this->getPersonDates( $pID );

		return [
			'append' => $dates,
		];
	}

	public static function getPersonDisplayName ($args = []) // was "protected" -- ??
	{
		$displayName = "";

		// Defaults
		$defaults = array(
			'person_id' 	=> null,
			'override'		=> 'none', // options include 'post_title', 'special_name'
			'name_abbr'   	=> 'full', // other option is "abbr", i.e. lastname only
			'show_prefix'   => false,
			'show_suffix'   => false,
			'show_job_title' => false,
			'show_dates'    => false,
			'url'    		=> null,
			'styled'		=> false,
		);

		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );

		wxc_log("args", $args);

		$specialName = get_field('special_name',$person_id);

		if ( $override == "special_name" && $specialName ) {
			$displayName = $specialName;
		} else if ( $override == "post_title" ) {
			$displayName = get_the_title( $person_id );
		} else {
			// Prefix
			if ( $show_prefix ) {
				$prefix = get_field('prefix',$person_id);
				if ( $prefix ) { $displayName .= $prefix." "; }
			}

			if ( $name_abbr == "abbr" && $show_prefix && !$prefix ) {
				$name_abbr == "full"; // ?? or better to just use post_title? see e.g.
				//$displayName = get_the_title( $person_id );
			}

			// First and middle names
			if ( $name_abbr == "full" ) {
				$firstName = get_post_meta( $person_id, 'first_name', true );
				if ( $firstName ) { $displayName .= $firstName." "; }
				$middleName = get_post_meta( $person_id, 'middle_name', true );
				if ( $middleName ) { $displayName .= $middleName." "; }
			}

			// Last name
			$lastName = get_field('last_name',$person_id);
			$displayName .= $lastName;

			// Suffix
			if ( $show_suffix ) {
				$suffix = get_field('suffix',$person_id);
				if ( $suffix ) { $displayName .= ", ".$suffix; }
			}

			/*
			// Job Title
			if ( $show_job_title ) {
				$job_title = get_field('job_title',$person_id);
				if ( $job_title ) { $displayName .= ", <em>".$job_title."</em>"; }
			}*/

			// Dates
			// WIP/TODO: fix 'styled' factor -- see e.g. https://stcnyc.wpengine.com/events/solemn-eucharist-2020-01-05/ Wm Byrd -- span needed around dates.
			if ( $show_dates ) {
				$displayName .= $this->getPersonDates( $person_id, $styled );
			}

			$displayName = trim($displayName);

			if ( empty($displayName) ) {
				$displayName = get_the_title( $person_id );
			}
		}

		// Job Title
		if ( $show_job_title ) {
			$job_title = get_field('job_title',$person_id);
			if ( $job_title ) { $displayName .= ", <em>".$job_title."</em>"; }
		}

		if ( $url ) {
			$displayName = makeLink( $url, $displayName, get_the_title( $person_id ), null, '_blank' );
		}

		return $displayName;
	}

	public static function getPersonDates(?\WP_Post $post = null, $styled = false): string
	{
		$info = "";

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
	
	public static function getPersonCompositions(?\WP_Post $post = null): array
	{
		$compositions = [];
	
		$p = $post ?? $this->getPost();
		if (empty($p)) {
			return $compositions;
		}
		$pID = $p->ID;
		wxc_log('pID: '.$pID);
	
		if (!has_term('composers', 'person_category', $pID)) {
			return $compositions;
		}
	
		$result = (new PostQuery())->find([
			'post_type' => 'repertoire',
			'meta'      => [
				'type'   => 'containsSerialized',
				'key'    => 'composer',
				'values' => [$pID],
			],
			'orderby'   => 'title',
			'order'     => 'ASC',
			'limit'   => -1,
		]);
		
		wxc_log(count($result['posts']).' posts found');
	
		foreach ($result['posts'] as $composition) {
			$rep_info = get_rep_info($composition->ID, 'display', false, true);
			$compositions[] = makeLink(get_permalink($composition->ID), $rep_info, 'TEST rep title') . '<br />';
		}
	
		return $compositions;
	}
	
}
