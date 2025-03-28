<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin file, not much I can do when called directly.';
	exit;
}

/*********** Functions pertaining to CPT: VENUE ***********/

// TODO: generalize beyond NYCAGO-specific usage
function get_cpt_venue_content( $post_id = null ) {
	
	// This function retrieves supplementary info -- the regular content template (content.php) handles title, content, featured image
	// TODO: refine overall get_cpt_XXX_content setup to facilitate designation of content to display before and/or after main post_content
	
	// TS/logging setup
    $do_ts = devmode_active( array("whx4", "venues") ); 
    $do_log = false;
    $fcn_id = "[whx4-get_cpt_venue_content]&nbsp;";
    sdg_log( "divline2", $do_log );
    
    // Init vars
	$info = "";
	$ts_info = "";
	if ( $post_id === null ) { $post_id = get_the_ID(); }
	if ( $post_id === null ) { return false; }
	
    $post_meta = get_post_meta( $post_id );
	$ts_info .= $fcn_id."<pre>post_meta: ".print_r($post_meta, true)."</pre>";
    
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
    /*if ( has_term( 'composers', 'person_category', $post_id ) ) {
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
    }*/
    
    // Organs
    $arr_obj_organs = get_related_posts( $post_id, 'organ', 'venues_organs' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
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
    
    //
    if ( function_exists('sdg_editmode') && sdg_editmode() ) {
		
		//$settings = array( 'fields' => array( 'venue_info_ip', 'venue_info_vp', 'venue_addresses', 'building_dates', 'venue_sources', 'venue_html_ip', 'organs_html_ip', 'organs_html_vp' ) ); //, 'venue_html_vp'
		//$info .= acf_form( $settings );
		$acf_fields = 'venue_info_ip, venue_info_vp, venue_addresses, building_dates, venue_sources, venue_html_ip, organs_html_ip, organs_html_vp';
		$info .= do_shortcode( '[mlib_acf_form fields="'.$acf_fields.'"]' );
	
	} else {
    	
    	// If not in editmode, show content instead of acf_form
    	// WIP
    	//$settings = array( 'fields' => array( 'venue_info_ip', 'venue_info_vp', 'venue_sources', 'venue_html_ip', 'organs_html_ip', 'organs_html_vp' ) );
    	$venue_info_ip = get_post_meta( $post_id, 'venue_info_ip', true );
    	$info .= '<strong>venue_info_ip</strong>: <div class="venue_info_ip">'.$venue_info_ip."</div>";
    	$info .= '<hr />';
    	
    	$venue_info_vp = get_post_meta( $post_id, 'venue_info_vp', true );
    	$info .= '<div class="venue_info_vp">'.$venue_info_vp."</div>";
    	$info .= '<hr />';
    	
    	$venue_html_ip = get_post_meta( $post_id, 'venue_html_ip', true );
    	$info .= '<strong>venue_html_ip</strong>: <div class="venue_html_ip">'.$venue_html_ip."</div>";
    	$info .= '<hr />';
    	
    	$venue_sources = get_post_meta( $post_id, 'venue_sources', true );
    	$info .= '<strong>Sources</strong>: <div class="venue_sources">'.$venue_sources."</div>";
    	$info .= '<hr />';
    	
    	/*
    	$organs_html_ip = get_post_meta( $post_id, 'organs_html_ip', true );
    	$info .= '<strong>organs_html_ip</strong>: <div class="organs_html_ip">'.$organs_html_ip."</div>";
    	$info .= '<hr />';
    	
    	$organs_html_vp = get_post_meta( $post_id, 'organs_html_vp', true );
    	$info .= '<strong>organs_html_vp</strong>: <div class="organs_html_vp">'.$organs_html_vp."</div>";
    	$info .= '<hr />';
    	*/
    	
    	// Venue Organs
    	
		// Get and display post titles for "related_liturgical_dates".
		$instruments = get_field('venue_instruments', $post_id, false); // returns array of IDs
		if ( $instruments ) {
		
			$info .= "<h3>Instruments</h3>";
			$info .= "<p>".count($instruments)." instruments related to this venue in our database:</p>";
			$ts_info .= $fcn_id."<pre>instruments: ".print_r($instruments, true)."</pre>";
			
			foreach ($instruments AS $instrument_id) {
				$instrument_title = get_the_title($instrument_id);
				$info .= '<span class="instrument">';
				$info .= make_link( get_the_permalink($instrument_id), $instrument_title, null, null, "_blank" );
				$info .= '</span><br />';
			}
			
			$info .= "<hr />";
	
		}
    	
    }
    
    if ( $ts_info != "" && ( $do_ts === true || $do_ts == "venues" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
    
    return $info;
    
}

?>