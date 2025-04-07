<?php

namespace atc\WHx4;

class Person extends Core\CPTHandler { // implements CustomPostType
    
	protected function get_person_display_name ( $args = array() ) {
		
		// TS/logging setup
		$do_ts = devmode_active( array("whx4", "people") ); 
		$do_log = false;
		sdg_log( "divline2", $do_log );
		sdg_log( "function called: get_person_display_name", $do_log );
		
		// Init vars
		$arr_info = array();
		$display_name = "";
		$ts_info = "";
		
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
		
		$ts_info .= "<!-- [get_person_display_name] args: ".print_r($args, true)." -->";
		
		$special_name = get_field('special_name',$person_id);
		
		if ( $override == "special_name" && $special_name ) {
			
			$display_name = $special_name;
			
		} else if ( $override == "post_title" ) {
		
			$display_name = get_the_title( $person_id );
			
		} else {
	
			// Prefix
			if ( $show_prefix ) {
				$prefix = get_field('prefix',$person_id);
				if ( $prefix ) { $display_name .= $prefix." "; }
			}
			
			if ( $name_abbr == "abbr" && $show_prefix && !$prefix ) {
				$name_abbr == "full"; // ?? or better to just use post_title? see e.g. 
				//$display_name = get_the_title( $person_id );
			}
			
			// First and middle names
			if ( $name_abbr == "full" ) {
				$first_name = get_post_meta( $person_id, 'first_name', true );
				if ( $first_name ) { $display_name .= $first_name." "; }
				$middle_name = get_post_meta( $person_id, 'middle_name', true );
				if ( $middle_name ) { $display_name .= $middle_name." "; }
			}
			
			// Last name
			$last_name = get_field('last_name',$person_id);
			$display_name .= $last_name;
			
			// Suffix
			if ( $show_suffix ) {
				$suffix = get_field('suffix',$person_id);
				if ( $suffix ) { $display_name .= ", ".$suffix; }
			}
			
			/*
			// Job Title
			if ( $show_job_title ) {
				$job_title = get_field('job_title',$person_id);
				if ( $job_title ) { $display_name .= ", <em>".$job_title."</em>"; }
			}*/
			
			// Dates
			// WIP/TODO: fix 'styled' factor -- see e.g. https://stcnyc.wpengine.com/events/solemn-eucharist-2020-01-05/ Wm Byrd -- span needed around dates.
			if ( $show_dates ) {
				$display_name .= get_person_dates( $person_id, $styled );
			}
			
			$display_name = trim($display_name);
			
			if ( empty($display_name) ) {
				$display_name = get_the_title( $person_id );
			}
		
		}
			
		// Job Title
		if ( $show_job_title ) {
			$job_title = get_field('job_title',$person_id);
			if ( $job_title ) { $display_name .= ", <em>".$job_title."</em>"; }
		}
		
		if ( $url ) {
			$display_name = make_link( $url, $display_name, get_the_title( $person_id ), null, '_blank' );
		}
		
		//return $display_name;
		$arr_info['info'] = $display_name;
		if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
		
		return $arr_info;
		
	}
	
	public function get_cpt_content() {
		
		$post_id = $this->get_post_id();
		
		// This function retrieves supplementary info -- the regular content template (content.php) handles title, content, featured image
		
		// Init
		$info = "";
		$ts_info = "";
		
		$ts_info .= "post_id: ".$post_id."<br />";
		
		if ( $post_id === null ) { return false; }
		
		//$post = get_post($post_id);
		
		// Group <> Titles & Associations
		// WIP
		
		// Awards
		// WIP
		// TODO: include theme-specific content? via apply_filters?
		
		// Dates
		/*
		// TODO: figure out where to put this -- probably appended to post_title?
		$dates = get_person_dates( $post_id, true );
		if ( $dates && $dates != "" && $dates != "(-)" ) { 
			$info .= $dates; 
		}*/
		
		// Compositions
		// TODO: consider eliminating check for has_term, in case someone forgot to apply the appropriate category
		if ( has_term( 'composers', 'person_category', $post_id ) ) {
			// Get compositions
			$arr_obj_compositions = get_related_posts( $post_id, 'repertoire', 'composer' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
			if ( $arr_obj_compositions ) {
				
				$info .= "<h3>Compositions:</h3>";
				
				//$info .= "<p>arr_compositions (".count($arr_compositions)."): <pre>".print_r($arr_compositions, true)."</pre></p>";
				foreach ( $arr_obj_compositions as $composition ) {
					//$info .= $composition->post_title."<br />";
					$rep_info = get_rep_info( $composition->ID, 'display', false, true ); // ( $post_id = null, $format = 'display', $show_authorship = true, $show_title = true )
					$info .= make_link( get_permalink($composition->ID), $rep_info, "TEST rep title" )."<br />";
				}
			}
		}
		
		// TODO: arranger, transcriber, translator, librettist
		
		// Publications
		if ( is_dev_site() ) {
			// Editions
			$arr_obj_editions = get_related_posts( $post_id, 'edition', 'editor' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
			
			if ( $arr_obj_editions ) {
	
				$info .= '<div class="publications">';
				$info .= "<h3>Publications:</h3>";
	
				//$info .= "<p>arr_obj_editions (".count($arr_obj_editionss)."): <pre>".print_r($arr_obj_editions, true)."</pre></p>";
				foreach ( $arr_obj_editions as $edition ) {
					//$info .= $edition->post_title."<br />";
					$info .= make_link( get_permalink($edition->ID), $edition->post_title, "TEST edition title" )."<br />";
				}
	
				$info .= '</div>';
			}
		}
		
		// Sermons
		$arr_obj_sermons = get_related_posts( $post_id, 'sermon', 'sermon_author' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
		if ( $arr_obj_sermons ) {
			
			$info .= '<div class="devview sermons">';
			$info .= "<h3>Sermons:</h3>";
	
			foreach ( $arr_obj_sermons as $sermon ) {
				//$info .= $sermon->post_title."<br />";
				$info .= make_link( get_permalink($sermon->ID), $sermon->post_title, "TEST sermon title" )."<br />";
			}
			
			$info .= '</div>';
		}
		
		// Related Events
		if ( is_dev_site() ) {
			/*
			// Get Related Events
			$wp_args = array(
				'posts_per_page'=> -1,
				'post_type'		=> 'event',
				'meta_query'	=> array(
					array(
						'key'		=> "personnel_XYZ_person", // name of custom field, with XYZ as a wildcard placeholder (must do this to avoid hashing)
						'compare' 	=> 'LIKE',
						'value' 	=> '"' . $post_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
					)
				),
				'orderby'	=> 'meta_value',
				'order'     => 'DESC',
				'meta_key' 	=> '_event_start_date',
			);
	
			$query = new WP_Query( $wp_args );
			$event_posts = $query->posts;
			$info .= "<!-- wp_args: <pre>".print_r($wp_args,true)."</pre> -->";
			$info .= "<!-- Last SQL-Query: {$query->request} -->";
	
			if ( $event_posts ) { 
				global $post;
				$info .= '<div class="devview em_events">';
				//-- STC
				$info .= '<h3>Events at Saint Thomas Church:</h3>';
				foreach($event_posts as $post) { 
					setup_postdata($post);
					// TODO: modify to show title & event date as link text
					$event_title = get_the_title();
					$date_str = get_post_meta( get_the_ID(), '_event_start_date', true );
					if ( $date_str ) { $event_title .= ", ".$date_str; }
					$info .= make_link( get_the_permalink(), $event_title ) . "<br />";	
				}
				$info .= '</div>';
			} else {
				$info .= "<!-- No related events found for post_id: $post_id -->";
			}
			wp_reset_query();
			*/
		}
		
		// Person Categories
		$term_obj_list = get_the_terms( $post_id, 'person_category' );
		if ( $term_obj_list ) {
			$terms_string = join(', ', wp_list_pluck($term_obj_list, 'name'));
			$info .= '<div class="devview categories">';
			if ( $terms_string ) {
				$info .= "<p>Categories: ".$terms_string."</p>";
			}
			$info .= '</div>';
		}
		
		$info .= $ts_info;
		
		return $info;
		
	}
	
	protected function get_person_dates( $post_id, $styled = false ) {
		
		// TS/logging setup
		$do_ts = devmode_active( array("whx4", "people") ); 
		$do_log = false;
		sdg_log( "divline2", $do_log );
		sdg_log( "function called: get_person_dates", $do_log );
		
		// Init vars
		$info = ""; // init
		//if ( $styled == 'false' ) { $styled = false; } else { $styled = true; }// just in case...
		
		//sdg_log( "[str_from_persons] arr_persons: ".print_r($arr_persons, true), $do_log );
		sdg_log( "[get_person_dates] post_id: ".$post_id, $do_log );
		//sdg_log( "[get_person_dates] styled: ".$styled, $do_log );
		
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

?>