<?php

namespace atc\WHx4\Modules\Places\PostTypes;

use atc\WXC\Logger;
use atc\WXC\PostTypes\PostTypeHandler;

class Venue extends PostTypeHandler
{
	public function __construct(?\WP_Post $post = null) {
		$config = [
			'slug'        => 'venue',
			//'plural_slug' => 'venues',
			/*'labels'      => [
				//'add_new_item' => 'Summon New Monster',
				'not_found' => 'Nowhere to go',
			],*/
			'menu_icon'   => 'dashicons-location', // could use dashicons-admin-multisite instead
			'capability_type' => ['place','places'],
			'supports' => ['title', 'author', 'thumbnail', 'editor', 'excerpt', 'revisions'],
			'taxonomies' => ['venue_category'], //, 'admin_tag'
		];

		parent::__construct( $config, $post );
	}

    public function boot(): void
    {
        parent::boot();
    }

    // WIP!
    // TODO: generalize beyond NYCAGO-specific usage
	function get_cpt_venue_content( $post_id = null )
	{
		// This function retrieves supplementary info -- the regular content template (content.php) handles title, content, featured image
		// TODO: refine overall get_cpt_XXX_content setup to facilitate designation of content to display before and/or after main post_content

		$logCtx = ['whx4', 'venues'];

		// Init vars
		$arr_info = array(); // WIP --
		$info = "";
		$before_pc = ""; // cpt content to show before/above main post content
		$after_pc = ""; // cpt content to show after/below main post content
		$ts_info = "";
		if ( $post_id === null ) { $post_id = get_the_ID(); }
		if ( $post_id === null ) { return false; }

		$post_meta = get_post_meta( $post_id );
		//$ts_info .= "<pre>post_meta: ".print_r($post_meta, true)."</pre>";


		// Organs
		$arr_obj_organs = getRelatedPosts( $post_id, 'organ', 'venues_organs' ); // getRelatedPosts( $post_id = null, $related_post_type = null, $related_field_name = null, $limit = '-1' )
		if ( $arr_obj_organs ) {

			$info .= '<div class="devview organs">';
			$info .= "<h3>Organs:</h3>";

			foreach ( $arr_obj_organs as $organ ) {
				//$info .= $organ->post_title."<br />";
				$info .= make_link( get_permalink($organ->ID), $organ->post_title, "TEST sermon title" )."<br />";
			}

			$info .= '</div>';
		}

		// Venue Categories
		$term_obj_list = get_the_terms( $post_id, 'venue_category' );
		if ( $term_obj_list ) {
			$terms_string = join(', ', wp_list_pluck($term_obj_list, 'name'));
			$info .= '<div class="devview categories">';
			if ( $terms_string ) {
				$info .= "<p>Categories: ".$terms_string."</p>";
			}
			$info .= '</div>';
		}

		//$info .= display_postmeta( array('post_id' => $post_id) );

		$info .= '<p class="smaller">'.the_modified_date( 'F j, Y g:i a', 'Last updated: ', '', false )."</p>";

		// TODO: generalize -- venue_filename and many other of following meta are particular to NYCAGO

		// Link to Legacy venue page, if any
		$venue_filename = get_post_meta( $post_id, 'venue_filename', true );
		if ( $venue_filename ) {
			if ( isset($post_meta['borough'][0]) ) {
				$borough = $post_meta['borough'][0];
				$boroughs = array('bronx' => 'Brx', 'brooklyn' => 'Bkln', 'manhattan' => 'NYC', 'queens' => 'Qns', 'statenisland' => 'SI');
				$url = "https://nycago.org/Organs/".$boroughs[$borough]."/html/".$venue_filename;// tmp
				//$url = "/Organs/".$boroughs[$borough]."/html/".$venue_filename;
				$text = "View legacy venue page";
				$title = $url;
				$class = "";
				$target = "_blank";
				$info .= '<div class="xxx">'.make_link( $url, $text, $title, $class, $target)."</div>";
				//$info .= '<strong>venue_path</strong>: <div class="xxx wip">'.$venue_path."</div>";
				// TODO: add link to relevant part of borough index page
				//
			}

			//$info .=
			//$info .= '<strong>venue_filename</strong>: <div class="xxx wip">'.print_r($venue_filename, true)."</div>";
		} ///Organs/Brx/html/RCOrphanAsylum.html

		// TS editmode -- tft
		$ts_info .= "dev query_var: ".get_query_var('dev')."<br />";
		$ts_info .= "devmode_active: ".print_r(devmode_active(), true)."<br />";
		$ts_info .= "devmode_active(array('edit')): ".print_r(devmode_active(array("edit")), true)."<br />";
		$ts_info .= "sdg_editmode: ".print_r(sdg_editmode(), true)."<br />";
		$ts_info .= "wp_get_current_user->user_login: ".print_r(wp_get_current_user()->user_login, true)."<br />";
		$ts_info .= "wp_get_current_user->roles: ".print_r(wp_get_current_user()->roles, true)."<br />";
		//$info .= $ts_info;

		//
		if ( function_exists('sdg_editmode') && sdg_editmode() === true ) {

			//$settings = array( 'fields' => array( 'venue_info_ip', 'venue_info_vp', 'venue_addresses', 'building_dates', 'venue_sources', 'venue_html_ip', 'organs_html_ip', 'organs_html_vp' ) ); //, 'venue_html_vp'
			//$info .= acf_form( $settings );
			$acf_fields = 'neighborhood, venue_website, extant_venue, venue_status, venue_info_ip, venue_info_vp, venue_addresses, building_dates, venue_sources, venue_html_ip, organs_html_ip, organs_html_vp';
			$info .= do_shortcode( '[mlib_acf_form fields="'.$acf_fields.'"]' );

		} else {

			// If not in editmode, show content instead of acf_form
			// WIP

			$venue_addresses = get_post_meta( $post_id, 'venue_addresses', true );
			if ( !empty($venue_addresses) ) {
				$info .= '<div class="venue_addresses wipf">'.$venue_addresses."</div>";
				$info .= '<hr />';
			}

			$venue_website = get_post_meta( $post_id, 'venue_website', true );
			if ( !empty($venue_website) ) {
				$venue_website = make_link( $venue_website, $venue_website, 'Venue Website URL', '', '_blank');
				$info .= '<div class="venue_website">'.$venue_website."</div>";
				$info .= '<hr />';
			}

			// Venue Organs
			// Get and display post titles for "venue_instruments".
			$instruments = get_field('venue_instruments', $post_id, false); // returns array of IDs
			if ( $instruments ) {

				$info .= "<h3>Instruments</h3>";
				$info .= "<p>".count($instruments)." instruments related to this venue in our database:</p>";
				$ts_info .= "<pre>instruments: ".print_r($instruments, true)."</pre>";

				foreach ($instruments AS $instrument_id) {
					$instrument_title = get_the_title($instrument_id);
					$info .= '<span class="instrument">';
					$info .= make_link( get_the_permalink($instrument_id), $instrument_title, null, null, "_blank" );
					$info .= '</span><br />';
				}

				$info .= "<hr />";

			}

			//
			/*
			$venue_info_ip = get_post_meta( $post_id, 'venue_info_ip', true );
			if ( !empty($venue_info_ip) ) {
				$info .= '<strong>venue_info_ip</strong>: <div class="venue_info_ip wipf">'.$venue_info_ip."</div>";
				$info .= '<hr />';
			}

			$venue_info_vp = get_post_meta( $post_id, 'venue_info_vp', true );
			if ( !empty($venue_info_vp) ) {
				$info .= '<strong>venue_info_vp</strong>: <div class="venue_info_vp wipf">'.$venue_info_vp."</div>";
				$info .= '<hr />';
			}

			$venue_html_ip = get_post_meta( $post_id, 'venue_html_ip', true );
			if ( !empty($venue_html_ip) ) {
				$info .= '<strong>venue_html_ip</strong>: <div class="venue_html_ip wipf">'.$venue_html_ip."</div>";
				$info .= '<hr />';
			}
			*/

			$venue_sources = get_post_meta( $post_id, 'venue_sources', true );
			if ( !empty($venue_sources) ) {
				$info .= '<strong>Sources</strong>: <div class="venue_sources wipf">'.$venue_sources."</div>";
				$info .= '<hr />';
			}

			/*
			$organs_html_ip = get_post_meta( $post_id, 'organs_html_ip', true );
			$info .= '<strong>organs_html_ip</strong>: <div class="organs_html_ip">'.$organs_html_ip."</div>";
			$info .= '<hr />';

			$organs_html_vp = get_post_meta( $post_id, 'organs_html_vp', true );
			$info .= '<strong>organs_html_vp</strong>: <div class="organs_html_vp">'.$organs_html_vp."</div>";
			$info .= '<hr />';
			*/

		}

		if ( $ts_info != "" && ( $do_ts === true || $do_ts == "venues" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }

		return $info;

	}
}
