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
	
    $info = ""; // init
    if ($post_id === null) { $post_id = get_the_ID(); }
    
    if ( $post_id === null ) { return false; }
    
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
    
    // Person Categories
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
    
    $venue_filename = get_post_meta( $post_id, 'venue_filename', true );
    $info .= '<strong>venue_filename</strong>: <div class="xxx wip">'.print_r($venue_filename, true)."</div>";
    
    if ( function_exists('sdg_editmode') && !sdg_editmode() ) {
    	// If not in editmode, show content instead of acf_form
    	// WIP
    	//$settings = array( 'fields' => array( 'venue_info_ip', 'venue_info_vp', 'venue_sources', 'venue_html_ip', 'organs_html_ip', 'organs_html_vp' ) );
    	$venue_info_ip = get_post_meta( $post_id, 'venue_info_ip', true );
    	$info .= '<strong>venue_info_ip</strong>: <div class="xxx wip">'.$venue_info_ip."</div>";
    	//<div class="source venue_source wip">
    	$venue_info_vp = get_post_meta( $post_id, 'venue_info_vp', true );
    	$info .= '<strong>venue_info_vp</strong>: <div class="xxx wip">'.$venue_info_vp."</div>";
    	
    	$venue_sources = get_post_meta( $post_id, 'venue_sources', true );
    	$info .= '<strong>venue_sources</strong>: <div class="xxx wip">'.$venue_sources."</div>";
    	
    	$venue_html_ip = get_post_meta( $post_id, 'venue_html_ip', true );
    	$info .= '<strong>venue_html_ip</strong>: <div class="xxx wip">'.$venue_html_ip."</div>";
    	
    	$organs_html_ip = get_post_meta( $post_id, 'organs_html_ip', true );
    	$info .= '<strong>organs_html_ip</strong>: <div class="xxx wip">'.$organs_html_ip."</div>";
    	
    	$organs_html_vp = get_post_meta( $post_id, 'organs_html_vp', true );
    	$info .= '<strong>organs_html_vp</strong>: <div class="xxx wip">'.$organs_html_vp."</div>";
    }
    
    return $info;
    
}

?>