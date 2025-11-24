<?php

namespace atc\WHx4\Modules\People\PostTypes;

use WXC\Core\PostTypeHandler;

class Person extends PostTypeHandler
{
    public function __construct(?\WP_Post $post = null)
    {
		$config = [
			'slug'        => 'person',
			'plural_slug' => 'people',
			'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				'not_found' => 'No people loitering nearby',
			],
			'menu_icon'   => 'dashicons-groups', // could use dashicons-id-alt instead
			'capability_type' => ['person','people'],
			'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions', 'page-attributes'],
			'taxonomies' => [ 'person_category', 'person_role' ], //, 'admin_tag'
			//'hierarchical' => true,
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

	protected function getPersonDisplayName ($args = [])
	{
		// Init vars
		$arr_info = array();
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

		$ts_info .= "<!-- [getPersonDisplayName] args: ".print_r($args, true)." -->";

		$special_name = get_field('special_name',$person_id);

		if ( $override == "special_name" && $special_name ) {

			$displayName = $special_name;

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
				$first_name = get_post_meta( $person_id, 'first_name', true );
				if ( $first_name ) { $displayName .= $first_name." "; }
				$middle_name = get_post_meta( $person_id, 'middle_name', true );
				if ( $middle_name ) { $displayName .= $middle_name." "; }
			}

			// Last name
			$last_name = get_field('last_name',$person_id);
			$displayName .= $last_name;

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
				$displayName .= get_person_dates( $person_id, $styled );
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

		// TODO: rethink this setup -- do we really need to always return the array? Probably better to add an optional arr return version for TS if needed
		//return $displayName;
		$arr_info['info'] = $displayName;
		if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }

		return $arr_info;
	}

	//
	/*public function getSN(?\WP_Post $post = null): string
    {
        $p = $post ?? $this->getPost();
        return $p ? (string)get_post_meta($p->ID, 'secret_name', true) : 'Unknown';
    }*/

	public function getPersonDates(?\WP_Post $post = null, $styled = false): string
	{
		//error_log( '=== Person::getPersonDates() ===' );
		$info = ""; // init

        $p = $post ?? $this->getPost();
        if ( empty($p) ) { return "no post found"; } // $info
        $pID = $p->ID;
		//error_log( 'post_id: ' . $pID );

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

