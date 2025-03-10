<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin file, not much I can do when called directly.';
	exit;
}

/*********** POST/EVENT RELATIONSHIPS ***********/

function get_related_event( $post_id = null, $post_type = null, $link = true, $link_text = null ) {
	
	$info = ""; // init
	$ts_info = "";
	if ($post_id === null) { $post_id = get_the_ID(); }
	
	// If we don't have actual values for both parameters, there's not enough info to proceed
	if ($post_id === null || $post_type === null) { return null; }
	
	$event_id = get_related_posts( $post_id, $post_type, 'event', 'single' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
	//echo "event_id: $event_id; post_id: $post_id"; // tft
	//$ts_info .= "event_id: $event_id; post_id: post_id<br />"; // tft
	
	if ($event_id && $event_id !== "no posts") {
		if ($link === true) { 
			$info .= '<a href="'. esc_url(get_the_permalink($event_id)) . '" title="'.get_the_title($event_id).'">';
			if ($link_text !== null) { $info .= $link_text; } else { $info .= get_the_title($event_id); }
			$info .= '</a>';
		} else {
			$info .= get_the_title($event_id);
		}
		//$info .= '<a href="'. esc_url(get_the_permalink($event_id)) . '" title="event_id: '.$event_id.'/post_id: '.$post_id.'">' . get_the_title($event_id) . '</a>';
	} else {
		//$ts_info .= "event_id: $event_id; post_id: post_id<br />";
		return null;
	}
	
	//$info .= '<a href="'. esc_url(get_permalink($event_id)) . '">' . get_the_title($event_id) . '</a>';
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
	return $info;
	
}

// WIP: Get Related Events based on program info
// TODO: make this not so terribly slow!!!
function get_related_events ( $meta_field = null, $term_id = null, $return_fields = 'ids' ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = false;
    sdg_log( "divline2", $do_log );

    // Init vars
    $arr_info = array();
    $ts_info = "";
    
    // Determine meta_key based on field name, with XYZ as a wildcard placeholder (must do this to avoid hashing)
    if ( $meta_field == "program_label" ) {
        $meta_key = "program_items_XYZ_item_label";
    } else if ( $meta_field == "program_item" ) {
        $meta_key = "program_items_XYZ_program_item";
    } else if ( $meta_field == "role" ) {
        $meta_key = "personnel_XYZ_role";
    } else if ( $meta_field == "person" ) {
        $meta_key = "personnel_XYZ_person";
    } else {
    	$meta_key = "";
    }
    
    $ts_info .= "meta_field: ".$meta_field."; meta_key: ".$meta_key."; term_id: ".$term_id."<br />";
    
    // Build query args
    $wp_args = array(
        'posts_per_page'=> -1,
        'post_type'		=> 'event',
        'meta_query'	=> array(
            array(
                'key'		=> $meta_key,
                'compare' 	=> 'LIKE',
                //'value' 	=> $term_id,
                'value' 	=> '"' . $term_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
            )
        ),
        'orderby'	=> 'meta_value',
		'order'     => 'DESC',
		'meta_key' 	=> '_event_start_date',
		'fields' => $return_fields,
    );
    
    $query = new WP_Query( $wp_args );
    $event_posts = $query->posts;
    

    $ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
    $ts_info .= "event_posts: <pre>".print_r($event_posts, true)."</pre>";
    $ts_info .= "Last SQL-Query: <pre>{$query->request} </pre><br />";
    
    if ( $event_posts ) {
        // WIP
    } else {
        $ts_info .= "No related events found.<br />";
        //$ts_info .= "Last SQL-Query: <pre>{$query->request} </pre><br />";
        //$ts_info .= "Query object: <pre>{$query} </pre><br />";
    }
    
    $arr_info['event_posts'] = $event_posts;
    if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
    
    return $arr_info;
    
}


/*********** EVENT PROGRAMS ***********/

add_shortcode('display_event_program', 'get_event_program_content');
// Get program per ACF fields for Event post
function get_event_program_content( $post_id = null ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
	
	// Init vars
	$info = "";
	$ts_info = "";
    $ts_info .= "===== get_event_program_content =====<br />";
	if ( $post_id == null ) { $post_id = get_the_ID(); }
    
    // What type of program is this? Service order or concert program?
    $program_type = get_post_meta( $post_id, 'program_type', true );
    
    // What program order? (default is personnel first)
    $program_order = get_post_meta( $post_id, 'program_order', true );
    
    // Troubleshooting
    $ts_info .= "post_id: $post_id<br />";
    $ts_info .= "program_type: $program_type<br />";
    $ts_info .= "program_order: $program_order<br />";
    // Get and display any admin_tags for the post
    $admin_tags = wp_get_post_terms( $post_id, 'admin_tag', array( 'fields' => 'slugs' ) );
    if ( $admin_tags ) { $admin_tags_str = implode(", ", $admin_tags); } else { $admin_tags_str = ""; }
    $ts_info .= "admin_tags: ".$admin_tags_str."<br /><br />";
	
    $info .= '<div class="event_program '.$program_type.' '.$program_order.'">';
    
    // Get personnel
    $arr_personnel = get_event_personnel( $post_id );
    $personnel = $arr_personnel['info'];
    $ts_info .= $arr_personnel['ts_info'];
    // Get program_items
    $arr_program_items = get_event_program_items( $post_id );
    $program_items = $arr_program_items['info'];
    $ts_info .= $arr_program_items['ts_info'];
    //
    if ( $program_order == "first_program_items" ) {
        $info .= $program_items;
        $info .= $personnel;	   
    } else {
        $info .= $personnel;
        $info .= $program_items;
    }
    
	$info .= '</div>';
    $ts_info .= "===== // get_event_program_content =====<br />";
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; } //$info = $ts_info.$info; // ts_info at the top of the page
	
    // TODO: get and display program_pdf?
	//$info .= make_link($program_pdf,"Download Leaflet PDF", null, null, "_blank");
	
	return $info;
	
}

add_shortcode('display_event_ticketing_info', 'get_event_ticketing_info');
// Get ticketing info per ACF fields for Event post
function get_event_ticketing_info( $post_id = null ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
	
	$info = "";
	$ts_info = "";
	if ( $post_id == null ) { $post_id = get_the_ID(); }
    
    // Get ticket header and info
    $show_ticket_info = get_post_meta( $post_id, 'show_ticketing_info', true );
    if ( $show_ticket_info != "1" ) { $ticket_info_div_class = " devinfo"; } else { $ticket_info_div_class = ""; }
    $ticket_info_header = get_post_meta( $post_id, 'ticket_info_header', true );
    $ticket_info = get_post_meta( $post_id, 'ticket_info', true );
    
    $info .= '<div class="event_tickets'.$ticket_info_div_class.'">';
    
    if ( !empty($ticket_info_header) ) {
        $info .= '<h2 id="tickets">'.$ticket_info_header.'</h2>';
    }
    if ( !empty($ticket_info) ) {
        $info .= $ticket_info;
    }
    
    // Ticket URLs
    $show_ticket_urls = get_post_meta( $post_id, 'show_ticketing_urls', true );
    $ts_info .= "show_ticket_urls: ".$show_ticket_urls."<br />"; // tft
    if ( $show_ticket_urls != "1" ) { $ticket_div_class = " devinfo"; } else { $ticket_div_class = ""; }
    //
    $rows = get_field('ticket_urls', $post_id);
    //$ticket_urls = get_post_meta( $post_id, 'ticket_urls', true );
    
    if ( empty($rows) ) { $rows = array(); }
    if ( is_array($rows)) { $num_rows = count($rows); } else { $num_rows = 0; }
    $ts_info .= "".$num_rows." ticket url rows<br />\n"; // tft
    
    // Loop through the ticket url rows and accumulate data for display
	if ( $num_rows > 0 ) {
        
        $i = 0;
        
        $info .= '<div class="ticket_urls'.$ticket_div_class.'">';
        
        foreach ($rows as $row) {
            
            // initialize vars
            $row_info = "";
            
            if ( isset($row['ticket_url_link_text']) && $row['ticket_url_link_text'] != "" ) {
                $link_text = $row['ticket_url_link_text'];
            } else {
                $link_text = "Buy Tickets";
            }
            $ts_info .= "link_text: $link_text<br />";
            
            if ( isset($row['ovationtix_id']) && $row['ovationtix_id'] != "" ) { 
                $ticket_url = "https://ci.ovationtix.com/35174/production/".$row['ovationtix_id'];
            } else if ( isset($row['ticket_url']) && $row['ticket_url'] != "" ) { 
                $ticket_url = $row['ticket_url'];
            } else {
                $ticket_url = null;
            }
            $ts_info .= "ticket_url: $ticket_url<br />";
            
            if ( $ticket_url) { 
                $info .= make_link( $ticket_url, $link_text, $link_text, "button", "_blank")."<br />";
            } else {
                $ts_info .= "No ticket_url for link_text ".$link_text."<br />";
            }
        }
        
        $info .= '</div>';
    }
    
	$info .= '</div>';
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
	return $info;
	
}

// Music Department infos

add_shortcode('call_time', 'get_call_time' );
function get_call_time( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
	
	// Extract args
	$args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
        'format' => 'long'    
    ), $atts );
	extract( $args );
	
	// Init vars
	$info = "";
	$ts_info = "";
    //$ts_info .= "===== get_call_time =====<br />";
    //$ts_info .= "post_id: $post_id<br />";
	
	$hclass = 'call_time';
	if ( $format == "short" ) { $hclass .= " inline"; }
	
	// Call Time
    $call_time = get_field( 'call_time', $post_id ); //$call_time = get_post_meta( $post_id, 'call_time', true );
    $info .= '<h3 class="'.$hclass.'">Call Time:</h3>'.$call_time.'<br />';
    
    if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
	return $info;
    
}

add_shortcode('md_overview', 'get_music_dept_overview' );
function get_music_dept_overview( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
	
	// Extract args
	$args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
        'format' => 'long'    
    ), $atts );
	extract( $args );
	
	// Init vars
	$info = "";
	$ts_info = "";
    $ts_info .= "===== get_music_dept_overview =====<br />";
    $ts_info .= "post_id: $post_id<br />";
    //
    if ( $format == "short" ) { $hclass = "inline"; } else { $hclass = ""; }
	//if ( $format != "short" ) { $info .= "<h2>Overview</h2>"; }
    
    // Music Staff
    $info .= '<h3 class="'.$hclass.'">Music Staff:</h3>';
    $staff = get_field( 'music_staff', $post_id );
    foreach ( $staff as $post ) {
    	if ( $format == "short" ) {
			$info .= get_field( 'initials', $post->ID ).", ";
		} else { 
			$info .= $post->post_title;
			$info .= "<br />";
		}
	}
	// Trim trailing semicolon and space
    if ( substr($info, -2) == ', ' ) { $info = substr($info, 0, -2)."<br />"; }
    
    // Groups
    $groups = get_field( 'participating_groups', $post_id );
    $info .= '<h3 class="'.$hclass.'">Participating Groups:</h3>';
    foreach ( $groups as $group ) {
		$info .= $group['label'];
    	if ( $format == "short" ) { $info .= "; "; } else { $info .= "<br />"; }
	}
	// Trim trailing semicolon and space
    if ( substr($info, -2) == '; ' ) { $info = substr($info, 0, -2)."<br />"; }
    
    $ts_info .= "===== // get_music_dept_overview =====<br />";
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
	return $info;

}

add_shortcode('roster', 'get_event_roster' );
function get_event_roster( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
	
	// Extract args
	$args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
        'format' => 'long'    
    ), $atts );
	extract( $args );
	
	// Init vars
	$info = "";
	$ts_info = "";
    $ts_info .= "===== get_roster =====<br />";
    $ts_info .= "post_id: $post_id<br />";
    if ( $format == "short" ) { $hclass = "inline"; } else { $hclass = ""; }
    
    if ( $format != "short" ) { $info .= "<h2>Roster</h2>"; } //else { $info .= '<h3>Roster</h3>'; }
	
	if ( $format != "short" ) { $info .= '<div class="roster flex-container">'; }
	$roster = array('soprano','alto','tenor','bass','absent','sick');
    foreach ( $roster as $fieldname ) {
    	if ( $format != "short" ) { $info .= '<div class="roster-section '.$fieldname.' flex-box mini alignleft">'; }
    	$posts = get_field( $fieldname, $post_id );
    	if ( $posts ) {
    		if ( $format == "short" && $fieldname == "absent" ) { $info .= "-----<br />"; } // break between roster and absences in short form
    		$info .= '<h3 class="'.$hclass.'">'.ucfirst($fieldname).':</h3>';
    		foreach ( $posts as $post ) {
    			if ( $format == "short" ) {
    				$info .= get_field( 'initials', $post->ID ).", ";
    			} else { 
    				$info .= $post->post_title;
    				$info .= "<br />";
    			}
			}
    	} else {
    		//$info .= "No ".$fieldname."s found for this event.";
    	}
    	// Trim trailing semicolon and space
    	if ( substr($info, -2) == ', ' ) { $info = substr($info, 0, -2)."<br />"; }
    	if ( $format != "short" ) { $info .= '</div>'; } // roster-section
    }
    if ( $format != "short" ) { $info .= '</div>'; } // roster
    
    $ts_info .= "===== // get_roster =====<br />";
	
	$choir_notes = get_field( 'choir_notes', $post_id );
	if ( $choir_notes ) {
		$info .= '<h3 class="'.$hclass.'">Choir Notes:</h3>';
		$info .= $choir_notes;
	}
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
	return $info;

}

add_shortcode('repertoire', 'get_event_rep');
function get_event_rep( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
	
	// Extract args
	$args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
        'format' => 'long'    
    ), $atts );
	extract( $args );
	
	// Init vars
	$info = "";
	$ts_info = "";
    $ts_info .= "===== get_event_rep =====<br />";
    
    // Troubleshooting
    $ts_info .= "post_id: $post_id<br />";
    //$ts_info .= "program_type: $program_type<br />";
    //$ts_info .= "program_order: $program_order<br />";
    
    // Get and display any admin_tags for the post
    $admin_tags = wp_get_post_terms( $post_id, 'admin_tag', array( 'fields' => 'slugs' ) );
    if ( $admin_tags ) { $admin_tags_str = implode(", ", $admin_tags); } else { $admin_tags_str = ""; }
    $ts_info .= "admin_tags: ".$admin_tags_str."<br /><br />";
	
	if ( $format == "short" ) {
		$info .= '<h3>Repertoire</h3>';
	} else {
		$info .= "<h2>Repertoire</h2>";
	}
    
    $choral_rep = get_field( 'choral_rep', $post_id );
    if ( $choral_rep ) {
    	$info .= "<ul>";
    	foreach ( $choral_rep as $i => $post ) {
			$rep_id = $post->ID;
			$rep_info = get_rep_info( $rep_id, 'display', true, true, true ); // get_rep_info( $post_id = null, $format = 'display', $show_authorship = true, $show_title = true, $full_title = false ) {
			$info .= "<li>";
			$info .= $rep_info['info'];
			$scores = get_field( 'scores', $rep_id );
			if ( $scores ) { 
				$info .= "&nbsp;";
				foreach ( $scores as $score ) {
					//$info .= print_r($score,true); //."<br />";
					$info .= make_link( $score['url'], '[PDF]', null, "important", "_blank"); // make_link( $url, $text, $title = null, $class = null, $target = null)
				}
    		} else {
    			$info .= "No scores available for this work.<br />";
    		}
    		$info .= "</li>";
			//$info .= $post->post_title."<br />";
			//$info .= get_rep_meta_info($tmp_id);
			//$info .= "<br />";
			// TODO: link to individual work record for more info -- or:
			// TODO: link to PDF, YT search, IMSLP search, CPDL search
			// TODO: show tags -- voicing etc.
		}
		$info .= "</ul>";
    } else {
    	$info .= "No choral rep found for this event.<br />";
    }
    
    $opening_voluntary = get_field( 'opening_voluntary', $post_id );
    $closing_voluntary = get_field( 'closing_voluntary', $post_id );
    
    $ts_info .= "===== // get_event_rep =====<br />";
	
	if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
	
    // TODO: get and display program_pdf?
	//$info .= make_link($program_pdf,"Download Leaflet PDF", null, null, "_blank");
	
	return $info;
	
}

/***  Program/Event personnel via Event CPT & ACF ***/
//
add_shortcode('display_event_personnel', 'get_event_personnel');
function get_event_personnel( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
    
    $args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
        'run_updates' => false,
        'display'	=> 'table'       
    ), $atts );
    
    // Extract
	extract( $args );
    
    // Init vars
    $arr_info = array();
    $info = "";
    $ts_info = "";
    $ts_info .= "===== get_event_personnel =====<br />";
    
    // *** WIP ***
    //if ( devmode_active() || is_dev_site() ) { $run_updates = true; } // TMP disabled 03/25/22
    //if ( devmode_active() || ( is_dev_site() && devmode_active() )  ) { $run_updates = true; } // ???
    
    $ts_info .= "Event Personnel for post_id: $post_id<br />";
    
    // What type of program is this? Service order or concert program?
    $program_type = get_post_meta( $post_id, 'program_type', true );
    
    // Program Layout -- left or centered?
    $program_layout = get_post_meta( $post_id, 'program_layout', true );
    
    $ts_info .= "run_updates: $run_updates<br />";
    //$ts_info .= "display: $display<br />";
    
	// Get the program personnel repeater field values (ACF)
    $rows = get_field('personnel', $post_id);  // ACF function: https://www.advancedcustomfields.com/resources/get_field/ -- TODO: change to use have_rows() instead?
    /*
    if ( have_rows('personnel', $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/have_rows/
        while ( have_rows('personnel', $post_id) ) : the_row();
            $XXX = get_sub_field('XXX'); // ACF function: https://www.advancedcustomfields.com/resources/get_sub_field/
        endwhile;
    } // end if
    */
    if ( empty($rows) ) { $rows = array(); } //$rows = (!empty(get_field('personnel', $post_id))) ? 'default' : array();
    if ( is_array($rows) ) { $ts_info .= count($rows)." personnel row(s)<br />"; }
    
    // Loop through the personnel rows and accumulate data for display
	if ( is_array($rows) && count($rows) > 0 ) {
        
        $table_classes = "event_program program personnel ".$program_layout;
        
        $i = 0; 
		//$i = 1; // row index counter init -- why not zero? see https://www.advancedcustomfields.com/resources/update_sub_field/#notes
        $deletion_count = 0;
        
		$table = '<table class="'.$table_classes.'">';
        $table .= '<tbody>';

        // Has a Personnel header been designated? If so, then display it.
        $personnel_header = get_post_meta( $post_id, 'personnel_header', true );
        if ( !empty($personnel_header) ) {
            $table .= '<tr><th colspan="2"><h2>'.$personnel_header.'</h2></th></tr>';
        }
        
        foreach ($rows as $row) {
            
            // initialize vars
            $placeholder_label = false;
            $placeholder_item = false;
            $arr_person_role = array();
            $arr_person = array();
            $person_role = null;
            $person_name = null;
            $delete_row = false;
            $row_info = "";
            
            // What's the row type? Options include "default", "header", "role_only", and "name_only"
            if ( isset($row['row_type']) ) { $row_type = $row['row_type']; } else { $row_type = "default"; }
            $row_info .= "row_type: ".$row_type."<br />"; // tft
            
            // Should this row be displayed on the front end?
            if ( isset($row['show_row']) && $row['show_row'] != "" ) { 
                $show_row = $row['show_row'];
                $row_info .= "show_row: ".$show_row."<br />"; // tft
            } else { 
                $show_row = 1; // Default to 'Yes'/true/show the row if no zero value has been saved explicitly
                $row_info .= "default: show_row = 1<br />"; // tft
            }
            
            if ( $display == 'dev' ) { // || devmode_active()
            
                $row_info .= "<code>";                
                $row_info .= "personnel row [$i]: <pre>".print_r($row, true)."</pre>";                
                /*
                if ( isset($row['role']) )          { 
                	$row_info .= "role => ".$row['role']."; ";
                	$row_info .= "row['role']: <pre>".print_r($row['role'], true)."</pre>";
                }
                if ( isset($row['role'][0]) )       { $row_info .= "role[0] => ".$row['role'][0]."; "; } // relates to old version where role was stored as CPT instead of taxonomy?
                
                if ( isset($row['role_old']) )      { $row_info .= "role_old => ".$row['role_old']."; "; }
                if ( isset($row['role_txt']) )      { $row_info .= "role_txt => ".$row['role_txt']."; "; }
                if ( isset($row['person']) )        { $row_info .= "person => ".$row['person']."; "; }
                if ( isset($row['person'][0]) )     { $row_info .= "person[0] => ".$row['person'][0]."; "; }
                if ( isset($row['group']) )      { $row_info .= "group => ".$row['group']."; "; }
                if ( isset($row['group'][0]) )   { $row_info .= "group[0] => ".$row['group'][0]."; "; }
                if ( isset($row['person_txt']) )    { $row_info .= "person_txt => ".$row['person_txt'].""; }
                */
                $row_info .= "</code><hr />";
            } else {
                //$row_info .= "personnel row [$i]: ".print_r($row, true)."<br />";
                //$row_info .= "personnel row [$i]: <pre>".print_r($row, true)."</pre><br />";
            }
            
            // Troubleshooting
            $row_info .= "i: [$i]; post_id: [$post_id]; program_type: [$program_type]; display: [$display]; run_updates: [$run_updates]<br />";
            //$row_info .= "personnel row [$i]: <pre>".print_r($row, true)."</pre>";
            
            if ( $row_type == "header" ) {
            
            	if ( isset($row['header_txt']) ) { $header_txt = $row['header_txt']; } else { $header_txt = ""; }
            
            } else {
            
            	// Set up the args array for the personnel role/person functions
				// --------------------
				$personnel_args = array( 'index' => $i, 'post_id' => $post_id, 'row' => $row, 'program_type' => $program_type, 'display' => $display, 'run_updates' => $run_updates );
			
				// Get the person role
				// --------------------
				$arr_person_role = get_personnel_role( $personnel_args );
				//$row_info .= "arr_person_role row [$i]: <pre>".print_r($arr_person_role, true)."</pre>";
				$person_role = $arr_person_role['info'];
				$row_info .= $arr_person_role['ts_info'];
			
				// Get the person
				// --------------------
				$arr_person = get_personnel_person( $personnel_args );
				$person_name = $arr_person['info'];
				$row_info .= $arr_person['ts_info'];
			
				$row_info .= "person_role: [$person_role]; person_name: [$person_name]<br />";
            
            }
            
            // Check for extra (empty) import rows -- prep to delete them
            if ( ( $row_type != "header" && empty($person_role) && empty($person_name) ) 
            	|| ( $row_type == "header" && empty($header_txt) )
                || ( ( $person_role == "x" || $person_role == "MATCH_DOUBLE (( post_title :: " ) && ( $person_name == "x" || empty($person_name) ) )
                ) {
                $delete_row = true;
                $row_info .= "row $i to be deleted! [person_role: $person_role; person_name: $person_name]<br />";
            }
            
            if ( $run_updates == true ) { 
                $do_deletions = true; // tft
            } else {
                $do_deletions = false; // tft
            }
            // *** NB: tmp override
            $do_deletions = false; // tft
            
            // If the row is empty/x-filled and needs to be deleted, then do so
            // WIP -- needs work
            if ( $delete_row == true ) {
                
                //sdg_log( "divline1", $do_log );
                //sdg_log( "personnel row to be deleted:", $do_log );
                //sdg_log( print_r($row, true), $do_log );
                //$row_info .= "<pre>".print_r($row, true)."</pre>"; // tft
                
                // ... but only run the action if this is the first deletion for this post_id in this round
                // ... because if a row has already been deleted then the row indexes will have changed for all the personnel rows
                // ... and though it would likely not be so difficult to reset the row index accordingly, for now let's proceed with caution...
                if ( $deletion_count == 0 && $do_deletions == true) {

                    if ( delete_row('personnel', $i, $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/delete_row/ -- syntax: delete_row($selector, $row_num, $post_id) 
                        $row_info .= "[personnel row $i deleted]<br />";
                        $deletion_count++;
                        //sdg_log( "[personnel row $i deleted successfully]", $do_log );
                    } else {
                        $row_info .= "[deletion failed for personnel row $i]<br />";
                        //sdg_log( "[failed to delete personnel row $i]", $do_log );
                    }
                    
                } else {
                    
                    if ( $do_deletions == true ) {
                        $row_info .= "[$i] row to be deleted on next round due to row_index issues.<br />";
                        //sdg_log( "row to be deleted on next round due to row_index issues.", $do_log );
                    } else {
                        $row_info .= "[$i] row to be deleted when do_deletions is re-enabled.<br />";
                        //sdg_log( "row to be deleted when do_deletions is re-enabled.", $do_log );
                    }
                    
                }
                
            }
            
            // Data Cleanup -- WIP
			// ...figuring out how to sync repertoire related_events w/ updates to program items -- display some TS info to aid this process
			if ( is_dev_site() ) {			
				$arr_row_info = event_program_row_cleanup ( $post_id, $i, $row, "personnel" );								
				$ts_info .= $arr_row_info['ts_info'];
				$row_errors = $arr_row_info['errors'];
				//if ( $row_errors ) { $post_errors = true; }
			}
                
			if ( $delete_row != true ) { // $display == 'table' && 
				$tr_class = "program_objects";
				if ( $show_row == "0" ) { $tr_class .= " hidden"; }
				$table .= '<tr class="'.$tr_class.'">';
			}
			
			/*if ( $run_updates == true || is_dev_site() || devmode_active() ) {
				$row_info = "*** START row_info ***<br />".$row_info."*** END row_info ***<br />"; // Display comments w/ in row for ease of parsing dev notes
                $table .= $row_info;
			} else {
				//$ts_info .= $row_info;
			}*/
			
			// Insert row_info for troubleshooting
			if ( devmode_active( array("whx4", "events") ) ) {
				if ( $display == 'table' ) {
					//$table .= '<div class="troubleshooting">row_info:<br />'.$row_info.'</div>'; //$row_info; // Display comments w/ in row for ease of parsing dev notes
				} else {
					//$ts_info .= 'row_info:<br />'.$row_info;
				}
				$ts_info .= $row_info; //$ts_info .= 'row_info:<br />'.$row_info;
			}
			
			if ( $delete_row != true ) {
			
				$td_class = "";
				
				if ( $row_type == "header" ) { $td_class .= "header "; }
				
				if ( $program_type == "concert_program" || $row_type == "header" ) {
					
					$td_class .= "concert_program_personnel";
					
					if ( $row_type != "header" ) {
						$role_class = "person_role";
						$item_class = "person";
					
						if ( $placeholder_label == true )	{ $role_class .= " placeholder"; }
						if ( $placeholder_item == true ) 	{ $item_class .= " placeholder"; }					
					}
					
					$table .= '<td colspan="2" class="'.$td_class.'">';
					if ( $row_type == "header" ) {
						$table .= $header_txt;
					} else {
						$table .= '<span class="'.$item_class.'">'.$person_name.'</span>';
						if ( $person_role != "" && $person_role != "N/A" && $person_role != "-- N/A --" ) { $table .= ', <span class="'.$role_class.'">'.$person_role.'</span>'; }
					}					
					$table .= '</td>';
					
				} else {
				
					$td_class .= "program_label";
					if ( $placeholder_label == true ) { $td_class .= " placeholder"; }
					$table .= '<td class="'.$td_class.'">'.$person_role.'</td>';
					$td_class = "program_item";
					if ( $placeholder_item == true ) { $td_class .= " placeholder"; }
					$table .= '<td class="'.$td_class.'">'.$person_name.'</td>';
					
				}
                
				$table .= '</tr>';
			}
			
			// --------------------
			
            $i++;
            
        } // end foreach $rows

		$table .= '</tbody>';
        $table .= '</table>';
        
        $info .= $table;

        // TODO: remove program-personnel-placeholders tag when ALL personnel placeholders have been replaced...
        //if ( $placeholder_label == false && $placeholder_item == false ) { 
            //$row_info .= sdg_remove_post_term( $post_id, 'program-personnel-placeholders', 'admin_tag', true ); 
        //
        
    } // end if $rows
	
    /*if ( $display == 'dev' ) {
        $ts_info = str_replace('<!-- ','<code>',$ts_info);
        $ts_info = str_replace(' -->','</code><br />',$ts_info);
        $ts_info = str_replace("\n",'<br />',$ts_info);
       	//$ts_info .= '</div>';
    }*/
    
    $arr_info['info'] = $info;
    $arr_info['ts_info'] = $ts_info;
	return $arr_info; //return $info;
	
}

//
function get_personnel_role ( $args = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
	
	// Init vars
	$arr_info = array();
	$ts_info = "";
	$person_role = "";
    //$placeholder_label = false;
	
	//$ts_info .= "get_personnel_role<br />"; // tft
    
    // Defaults
	$defaults = array(
		'index'   		=> null,
		'post_id' 		=> null,
		'row'			=> null,
		'program_type'	=> 'service_order', // other possible values include: "concert_program", "???"
		'display'    	=> "",
	);
	
	// Parse & Extract args
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
    // First, look for a proper taxonomy term (Personnel Role)
	if ( isset($row['role']) && !empty($row['role']) ) { 
		$term = get_term( $row['role'] );
		if ($term) { $person_role = $term->name; }
	}
	
	// If no role has been set via the Personnel Roles taxonomy, then look for a placeholder value
	if ( empty($person_role) ) {
		
		if ( isset($row['role_old']) && $row['role_old'] != "" ) {
			$ts_info .= "role is empty -> use placeholder role_old<br />";
			$person_role = get_the_title($row['role_old'][0]);
			//$placeholder_label = true;
		} else if ( isset($row['role_txt']) && $row['role_txt'] != "" && $row['role_txt'] != "x" ) {                    
			$ts_info .= "role is empty -> use placeholder role_txt<br />";
			$person_role = $row['role_txt'];
			//$placeholder_label = true;                    
		}
		
		// Fill in Placeholder -- see if a matching record can be found to fill in a proper person_role
		// TMP(?) disabled to avoid redundancy with event_program_row_cleanup function
		/*if ( $placeholder_label == true ) { 
			$title_to_match = $person_role;
			// TODO: deal w/ junk values like title_to_match == 'x'
			$ts_info .= "seeking match for placeholder value: '$title_to_match'<br />";
			$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => 'personnel', 'field_name' => 'role', 'taxonomy' => true, 'display' => $display );
			$match_result = match_placeholder( $match_args );
			$ts_info .= $match_result;
			//$ts_info .= sdg_add_post_term( $post_id, 'program-personnel-placeholders', 'admin_tag', true );
		}*/
		
	}
	
	$arr_info['info'] = $person_role;
	if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
	
	return $arr_info;
            
}

//
function get_personnel_person ( $args = array() ) {
	
	// TS/logging setup
	if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
    sdg_log( "function called: get_personnel_person", $do_log );

	// Init vars
	$arr_info = array();
	$person = "";
    $ts_info = "";
    
    // Defaults
	$defaults = array(
		'index'   		=> null,
		'post_id' 		=> null,
		'row'			=> null,
		'program_type'	=> 'service_order', // other possible values include: "concert_program", "???"
		'run_updates'   => false,
		'display'    	=> "",
		//'person_role'    	=> "", // WIP
	);
	
	// Parse & Extract args
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	//$ts_info .= "get_personnel_person";
	//$ts_info .= "get_personnel_person -- row: ".print_r($row, true);
	
	if ( isset($row['person']) && is_array($row['person']) ) {
	
		foreach ($row['person'] AS $person_id ) {
		
			$person_name = ""; // init
			
			// Set up display args to pass to fcn get_person_display_name
			$name_abbr = "full";
			$override = "none";
			$show_dates = false;
			$styled = true;
		
			if ( $program_type == "concert_program" ) {
				$show_prefix = false;
				$show_suffix = false;
				$show_job_title = false;
			} else {
				$show_prefix = true;
				$show_suffix = true;
				$show_job_title = false;
				// Check to see if this is a clergy person >> show prefix and lastname only, i.e. abbr
				if ( has_term( 'clergy', 'person_category', $person_id ) ) { 
					$name_abbr = "abbr";
				}				
				$override = "special_name";
			}
		
			$display_args = array( 'person_id' => $person_id, 'override' => $override, 'name_abbr' => $name_abbr, 'show_prefix' => $show_prefix, 'show_suffix' => $show_suffix, 'show_job_title' => $show_job_title, 'show_dates' => $show_dates, 'styled' => $styled );
		
			// Get URL for person/group, if any
			$personnel_url = null; // init
			$personnel_post_type = get_post_type( $person_id );
			if ( $program_type == "concert_program" ) {
				if ( isset($row['personnel_url']) && $row['personnel_url'] != "" ) { 
					$personnel_url = $row['personnel_url'];
				} else {
					$personnel_url = get_post_meta( $person_id, 'website', true );
				}
				$ts_info .= "personnel_url for $personnel_post_type [$person_id]: ".$personnel_url."<br />";
			} else {
				// And/or link to person page on sdg site listing events, sermons, &c.? TBD/TODO
			}
			$display_args['url'] = $personnel_url;
			
			$ts_info .= "display_args for get_person_display_name: ".print_r($display_args, true)."<br />";
			
			// Get the display_name
        	$arr_person_name = get_person_display_name( $display_args );
        	$person_name = $arr_person_name['info']."<br />";
        	$ts_info .= $arr_person_name['ts_info'];
        	
        	$person .= $person_name;
        
		}
	
		// Trim trailing <br />
		$person = substr($person, 0, -6);
		
	}
	
	if ( empty($person) ) {
		
		if ( isset($row['group'][0]) ) { 
			$group_obj = $row['group'][0];
			if ($group_obj) { 
				$person = $group_obj->post_title;
			}
		}
		
		if ( empty($person) ) {
			if ( isset($row['person_txt']) && $row['person_txt'] != "" && $row['person_txt'] != "x" ) { 
				
				$ts_info .= "person is empty -> use placeholder person_txt";
				$person = $row['person_txt'];
				
				// Fill in Placeholder -- see if a matching record can be found to fill in a proper person_name
				// TMP(?) disabled to avoid redundancy with event_program_row_cleanup function
				/*if ( $run_updates == true ) {
					$title_to_match = $person_name;
					// TODO: deal w/ junk values like title_to_match == 'x'
					$ts_info .= "seeking match for placeholder value: '$title_to_match'<br />";
					$match_args = array('index' => $index, 'post_id' => $post_id, 'item_title' => $title_to_match, 'item_label' => $person_role, 'repeater_name' => 'personnel', 'field_name' => 'person', 'taxonomy' => false, 'display' => $display );
					$match_result = match_placeholder( $match_args );
					$ts_info .= $match_result;
					//$ts_info .= sdg_add_post_term( $post_id, 'program-personnel-placeholders', 'admin_tag', true );
				}*/
				
			}
		}
			
	}
	
	$arr_info['info'] = $person;
	if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
	
	return $arr_info;
}

/***  Program items per Event CPT & ACF ***/

//
add_shortcode('display_event_program_items', 'get_event_program_items');
function get_event_program_items( $atts = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = false;
    sdg_log( "divline2", $do_log );
    
	$args = shortcode_atts( array(
		'post_id'        => get_the_ID(),
        'run_updates' => false,
        'display' => 'table'       
    ), $atts );
    
    // Extract
	extract( $args );
    
    // Init vars
    $arr_info = array(); // wip 06/27/23
    $info = "";
    $ts_info = "";
    $ts_info .= "===== get_event_program_items =====<br />";
    
    if ( $display == 'table' ) { $table = ""; }
    
    // TODO: deal more thoroughly w/ non-table display option, or eliminate that parameter altogether.
	
	if ($post_id == null) { $post_id = get_the_ID(); }
	$ts_info .= "Event Program Items for post_id: $post_id<br />";
    //$ts_info .= "display: $display<br />";
    
    // What type of program is this? Service order or concert program?
    $program_type = get_post_meta( $post_id, 'program_type', true );
    $ts_info .= "program_type: $program_type<br />";
    
    // Program Layout -- left or centered?
    $program_layout = get_post_meta( $post_id, 'program_layout', true );
	
    /*** WIP ***/
    //if ( devmode_active() || is_dev_site() ) { $run_updates = true; } // TMP(?) disabled 03/25/22
    //if ( devmode_active() || ( is_dev_site() && devmode_active() )  ) { $run_updates = true; } // ???
    
	// Get the program item repeater field values (ACF)
    $program_rows = get_field('program_items', $post_id); // ACF function: https://www.advancedcustomfields.com/resources/get_field/ -- TODO: change to use have_rows() instead?
    /*
    if ( have_rows('program_items', $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/have_rows/
        while ( have_rows('program_items', $post_id) ) : the_row();
            $XXX = get_sub_field('XXX'); // ACF function: https://www.advancedcustomfields.com/resources/get_sub_field/
        endwhile;
    } // end if
    */    
    if ( empty($program_rows) ) { $program_rows = array(); }    
    if ( is_array($program_rows) ) { $ts_info .= count($program_rows)." program_items/program_rows<br />"; } else { $ts_info .= "program_rows: ".print_r($program_rows, true); }
    
    //TODO: Determine whether program as a whole contains grouped rows
    /*
    if ( program_contains_groupings($rows) ) {
    	//
    }
    */
    
    // WIP: streamlining
    
	$authorship_display_settings = null;
	$program_composer_ids = array(); // TMP -- deprecated
    $deletion_count = 0;
    $table_rows = array();
    
    if ( is_array($program_rows) ) {
	
		// Check to see if ANY of the rows contains items with post_type == 'repertoire'
		if ( program_contains_repertoire($program_rows) ) { // TODO: generalize the following to apply to any items with authorship, not just rep/composers
	
			$ts_info .= "program contains repertoire items<br />";
			// If so, then get all the program composers
			$program_item_ids = get_program_item_ids($program_rows);
			$ts_info .= "program_item_ids: ".print_r($program_item_ids, true)."<br />";
			//
			$authorship_display_settings = set_row_authorship_display($program_item_ids);
			$ts_info .= "authorship_display_settings: <pre>".print_r($authorship_display_settings, true)."</pre><br />";
		
		}
	
		// Translate program_rows into table_rows (separate row per item)
		//////
    
		foreach( $program_rows as $r => $row ) {
		
			// TODO: check if row is empty >> next
		
			// Initialize variables
			$row_info = ""; // Info for troubleshooting
			$row_info .= "-------------------- [row $r] --------------------<br />";
		
				$grouped_row = false; // For multiple-item rows
				//
			$placeholder_label = false;
			$placeholder_item = false;
				//
			$arr_item_label = array();
			$arr_item_name = array();
				//
			$program_item_label = null;
			$program_item_name = null;
				//
				$show_person_dates = true;
		
			//
			$label_update_required = false;
			$delete_row = false;
		
			//$row_info .= "get_event_program_items ==> program row [$r]: ".print_r($row, true)."<br />";
			
			// Is a row_type set? WIP -- working on phasing out deprecated fields like 'show_item_label' in favor of simple row_types setup
			if ( isset($row['row_type']) ) { $row_type = $row['row_type']; } else { $row_type = null; }
			$row_info .= "get_event_program_items ==> row_type: ".$row_type."<br />";
			
			// ROW TYPES WIP: 
			/*
				Program item rows types:
				default : Standard two-column row
				header : Header row
				program_note : Program note
				label_only : Item label only
				title_only : Item title only
				//
				Personnel row types:
				default : Standard two-column row
				header : Header row
				role_only : Person role only
				name_only : Person name only
				//
				Additional option to consider, TBD:
				split : Title left/Authorship right
				//
				if ( $row_type == 'title_only' ) {
					
					$arr_item_name = get_rep_info( $program_item_obj_id, 'display', $show_item_authorship, true );
					$item_name = $arr_item_name['info'];
					$ts_info .= $arr_item_name['ts_info'];
			
				} else if ( empty($program_item_label) ) {

					$ts_info .= "program_item_label is empty >> use title in left col<br />";
				
				}
			*/
			
			// Is this a header row? (Check for deprecated field values)
			if ( $row_type != "header" && isset($row['is_header']) && $row['is_header'] == 1 ) { $row_type = "header"; } // TODO: update the row_type in the DB
		
			// Should this row be displayed on the front end?
			// TODO: modify to simplify as below -- set to true/false based on stored value, if any
			if ( isset($row['show_row']) && $row['show_row'] != "" ) { 
				$show_row = $row['show_row'];
				$row_info .= "get_event_program_items ==> show_row = '".$row['show_row']."'<br />";
			} else { 
				$show_row = 1; // Default to 'Yes'/true/show the row if no zero value has been saved explicitly
				$row_info .= "get_event_program_items ==> show_row = 1 (default)<br />";
			}
		
			// Should we display the item label for this row?
			// TODO: streamline/eliminate this deprecated field and simply update row_type
			if ( isset($row['show_item_label']) && $row['show_item_label'] == "0" ) { 
				$show_item_label = false;
				$row_info .= "get_event_program_items ==> show_item_label FALSE<br />";
			} else { 
				$show_item_label = true; // Default to 'Yes'/true/show the row if no zero value has been saved explicitly
				$row_info .= "get_event_program_items ==> show_item_label TRUE (default)<br />";
			}
				
			// Should the item title for this row be displayed on the front end?
			// TODO: streamline/eliminate this deprecated field and simply update row_type
			if ( isset($row['show_item_title']) && $row['show_item_title'] == "0" ) { 
				$show_item_title = false;
				$row_info .= "show_item_title = 0, i.e. false<br />";
			} else { 
				$show_item_title = true; // Default to 'Yes'/true/show the row if no zero value has been saved explicitly
				$row_info .= "default: show_item_title = true<br />";
			}
		
			// Get the item label
			// --------------------
			// TODO/WIP: maybe figure out how to skip this for rep items in $program_type == "concert_program" where title is used in left col instead of label			
			if ( $show_item_label == true && $row_type != 'title_only' ) {			
				$row_info .= "get_program_item_label<br />";
				$arr_item_label = get_program_item_label($row);
				$program_item_label = $arr_item_label['item_label'];
				$placeholder_label = $arr_item_label['placeholder'];
				$row_info .= $arr_item_label['ts_info'];
				$row_info .= ">> program_item_label: $program_item_label<br />";
			}
		
			// Program item(s)
			$program_items = array();
			$num_items = 0;
			if ( $show_item_title == true && $row_type != 'header' ) {
				// Does the row contain one or more item objects?			
				//$num_items = 1; // default: Single-item program_row translates to single table_row
				if ( isset($row['program_item']) && is_array($row['program_item']) ) {
					//$ts_info .= "program_item: ".print_r($row['program_item'], true)."<br />";
					$num_items = count($row['program_item']);
					$program_items = $row['program_item'];
				}
			}
		
			//if ( $num_items == 0 ) {
			if ( empty($program_items) ) {
		
				// TODO: eliminate redundancy
			
				// No actual items in this row -- just placeholders
				$row_info .= 'Zero-item program_row -- single table_row with placeholders<br />';
				$tr = array();
				$tds = array();
				$tr_class = "program_objects";
				if ( $show_row == "0" ) { $tr_class .= " hidden"; }
				$i = 0;
				$tr['tr_id'] = "tr-".$r.'-'.$i;
			
				if ( !empty($program_item_label) ) {
					$td_class = "zero_item_row";
					if ( $placeholder_label ) { $td_class .= " placeholder"; }
					$td_content = $program_item_label;
				
					if ( $row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only" ) {
					
						// Single wide column row
						$td_colspan = 2;					
						$row_content = "";
					
						if ( $row_type == "header" ) { 
							$td_class = "header";
							$td_content = $program_item_label;
						} else if ( $row_type == "program_note" ) {
							$td_class = "program_note";
							$td_content = $program_item_name;
						} else if ( $row_type == "label_only" ) {
							$td_class = "label_only";
							$td_content = $program_item_label;
						} else if ( $row_type == "title_only" ) {
							$td_class = "title_only";
							$td_content = $program_item_name;
						}
					} else {
						$td_colspan = 1;
					}
				
					$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
				}
			
				// Get the program item name
				// --------------------
				$arr_item_name = get_program_item_name( array( 'row' => $row, 'program_type' => $program_type, 'row_type' => $row_type, 'program_item_label' => $program_item_label ) );
				// Title as label?
				if ( !empty($arr_item_name['title_as_label']) ) {
					$row_info .= ">> use title_as_label<br />";
					$title_as_label = $arr_item_name['title_as_label'];
					$td_content = $title_as_label;
					$td_class = "title_as_label";
					$td_colspan = 1;
					$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
				} else {
					$title_as_label = null;
				}
			
				// Item Name
				if ( $arr_item_name['item_name'] ) { $program_item_name = $arr_item_name['item_name']; }
				if ( !empty($program_item_name) ) {
					$td_class = "program_item placeholder"; // placeholder because this is a zero-item row
					if ( $title_as_label ) { $td_class .= " authorship"; }
					$td_content = $program_item_name;
					$td_colspan = 1;
					// Add this item as a table cell only for two-column rows -- WIP
					if ( ! ($row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only") ) {
						$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
					}
				}
			
				//
				$tr['tr_class'] = $tr_class;
				$tr['tds'] = $tds;
			
				$table_rows[] = $tr;
			
			} else if ( $num_items == 1 ) {
				// Single-item program_row translates to single table_row
				$row_info .= 'Single-item program_row -- single table_row<br />';
			} else if ( $num_items > 1 ) {
				// Multiple items per program row -- display settings per row_type, program_type... -- WIP
				$row_info .= "*** $num_items program_items found for this row! ***<br />";
			}
		
			$row_info .= " >>>>>>> START foreach program_item <<<<<<<<br />";

			// Loop through the program items for this row (usually there is only one)
			foreach ( $program_items as $i => $program_item_obj_id ) {

				$row_info .= "<br />+~+~+~+~+ program_item #$i +~+~+~+~+<br />";
					
				$tr = array();
				$tds = array();
				$tr_class = "program_objects";
				if ( $show_row == "0" ) { $tr_class .= " hidden"; }
				if ( $num_items > 1 ) { $tr_class .= " row_group"; }
				if ( $num_items > 1 && $i == $num_items-1 ) { $tr_class .= " last_in_group"; }
				$tr['tr_id'] = "tr-".$r.'-'.$i;
			
				//
				$row_info .= "program_item_obj_id: $program_item_obj_id<br />";
				$item_post_type = get_post_type( $program_item_obj_id );
				$row_info .= "item_post_type: $item_post_type<br />";
			
				// If there's a program label set for the row, and if this is the first 
				if ( !empty($program_item_label) ) {
				
					$td_class = "test_td_class_1";
					if ( $placeholder_label ) { $td_class .= " placeholder"; }
				
					if ( $row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only" ) {
					
						// Single column row
						$td_colspan = 2;					
						$row_content = "";
					
						if ( $row_type == "header" ) { 
							$td_class = "header";
							$td_content = $program_item_label;
						} else if ( $row_type == "program_note" ) {
							$td_class = "program_note";
							$td_content = $program_item_name;
						} else if ( $row_type == "label_only" ) {
							$td_class = "label_only";
							$td_content = $program_item_label;
						} else if ( $row_type == "title_only" ) {
							$td_class = "title_only";
							$td_content = $program_item_name;
						}
					} else {
						$td_colspan = 1;
						if ( $i == 0 ) { $td_content = $program_item_label; } else { $td_content = ""; } //$td_content = "***"; }
					}			
				
					$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
				}
				
				if ( $authorship_display_settings && isset($authorship_display_settings[$r.'-'.$i]) ) {
					$display_settings = $authorship_display_settings[$r.'-'.$i];
					$row_info .= "[".$r.'-'.$i."] program_row >> display_settings: ".print_r($display_settings, true)."<br />";
					if ( isset($display_settings['show_name']) && $display_settings['show_name'] ) { $tr_class .= " show_authorship"; } else { $tr_class .= " hide_authorship"; }
					if ( isset($display_settings['show_dates']) && $display_settings['show_dates'] ) { $tr_class .= " show_person_dates"; } else { $tr_class .= " hide_person_dates"; }
				} else {
					$row_info .= "[".$r.'-'.$i."] program_row >> display_settings not set<br />";
				}
			
				// Get the program item name
				// --------------------
				// WIP
				$arr_item_name = get_program_item_name( array( 'row' => $row, 'program_type' => $program_type, 'row_type' => $row_type, 'program_item_obj_id' => $program_item_obj_id, 'program_item_label' => $program_item_label ) );
				// Title as label?
				if ( !empty($arr_item_name['title_as_label']) ) {
					$row_info .= ">> use title_as_label<br />";
					$title_as_label = $arr_item_name['title_as_label'];
					$td_content = $title_as_label;
					$td_class = "title_as_label";
					$td_colspan = 1;
					$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
				} else {
					$title_as_label = null;
				}
			
				$row_info .= "START arr_item_name['ts_info']<br />";
				$row_info .= $arr_item_name['ts_info']; // ts_info is already commented
				$row_info .= "END arr_item_name['ts_info']<br />";
				//$row_info .= "arr_item_name['info']: <pre>".$arr_item_name['info']."</pre>";
			
				// Program item_name
				if ( $arr_item_name['item_name'] ) { $program_item_name = $arr_item_name['item_name']; }
				if ( !empty($program_item_name) ) {
					$td_class = "program_item";
					if ( $title_as_label ) { $td_class .= " authorship"; }
					$td_content = $program_item_name;
					$td_colspan = 1;
					// Add this item as a table cell only for two-column rows -- WIP
					if ( ! ($row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only") ) {
						$tds[] = array( 'td_class' => $td_class, 'td_colspan' => $td_colspan, 'td_content' => $td_content );
					}
				}
			
				$tr['tr_class'] = $tr_class;
				$tr['tds'] = $tds;
					
				$table_rows[] = $tr;
				
			}
			
			$row_info .= '--------------------<br />';
		
				// Get the program item name
				// --------------------
				//$item_name_args = array();
				//$item_name_args['show_item_authorship'] = null; // wip -- get value true/false based on $program_composers array
				//$arr_item_name = get_program_item_name($item_name_args);
			
			
				// Match Placeholders
				//if ( $run_updates == true ) { match_placeholders($row); }
			
				// Cleanup/Deletion of extra/empty program rows
				// --------------------
				/*
				// Check for extra/empty rows -- prep to delete them
				if ( empty($program_item_label) && empty($program_item_name) ) {
					// Empty row -- no label, no item
					$delete_row = true;
					$row_info .= "[$i] delete the row, because everything is empty.<br />";
				} else if ( ( $program_item_label == "x" || $program_item_label == "")
					&& ( $program_item_label == "x" || $program_item_label == "*NULL*" || $program_item_label == "" ) 
					&& ( $program_item_name == "x" || $program_item_name == "*NULL*" || $program_item_name == "" ) 
				   ) {
					// Both label and item are either placeholder junk or empty
					$delete_row = true;
					$row_info .= "[$i] delete the row, because everything is meaningless.<br />";
				} else if ( $program_item_label == "*NULL*" || $program_item_name == "*NULL*" ) {
					// TODO: ???
					if ( $program_item_label == "*NULL*" ) { $program_item_label = ""; }
					if ( $program_item_name == "*NULL*" ) { $program_item_name = ""; }
				}
			
				if ( $run_updates == true ) { $do_deletions = true; } else { $do_deletions = false; }
				$do_deletions = false; // tft -- failsafe!
			
			
				// If the row is empty/x-filled and needs to be deleted, then do so
				if ( $delete_row == true ) {
				
					//sdg_log( "divline1", $do_log );
					//sdg_log( "program row to be deleted:", $do_log );
					//sdg_log( print_r($row, true), $do_log );
					$row_info .= "row: ".print_r($row, true)."<br />";
					$row_info .= "[$i] program row to be deleted<br />";
					$row_info .= "[$i] program row: item_label_txt='".$row['item_label_txt']."'; item_label='".$row['item_label']."'; program_item_txt='".$row['program_item_txt']."'<br />";
					//$row_info .= "[$i] program row: program_item='".print_r($row['program_item'], true)."'<br />";
				
					// ... but only run the action if this is the first deletion for this post_id in this round
					// ... because if a row has already been deleted then the row indexes will have changed for all the personnel rows
					// ... and though it would likely not be so difficult to reset the row index accordingly, for now let's proceed with caution...
					if ( $deletion_count == 0 && $do_deletions == true ) {

						if ( delete_row('program_items', $i, $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/delete_row/ -- syntax: delete_row($selector, $row_num, $post_id) 
							$row_info .= "[program row $i deleted]<br />";
							$deletion_count++;
							//sdg_log( "[program row $i deleted successfully]", $do_log );
						} else {
							$row_info .= "[deletion failed for program row $i]<br />";
							//sdg_log( "[failed to delete program row $i]", $do_log );
						}
					
					} else {
					
						if ( $do_deletions == true ) {
							$row_info .= "[$i] row to be deleted on next round due to row_index issues.<br />";
							//sdg_log( "row to be deleted on next round due to row_index issues.", $do_log );
						} else {
							$row_info .= "[$i] row to be deleted when do_deletions is re-enabled.<br />";
							//sdg_log( "row to be deleted when do_deletions is re-enabled.", $do_log );
						}
					}
				
				}
				*/
			
			
				// Display the row if it's a header, or if BOTH item_label and item_name are not empty
				// --------------------
			
				// Set up the table row
				/*if ( $display == 'table' && $delete_row != true ) {
					$tr_class = "program_objects";
					if ( $show_row == '0' || $show_row == 0 ) { $tr_class .= " hidden"; } else { $tr_class .= " show_row"; }
					if ( $show_person_dates == false ) { $tr_class .= " hide_person_dates"; } else { $tr_class .= " show_person_dates"; }
					if ( $num_row_items > 1 || $grouped_row == true ) { $tr_class .= " grouping"; }
					$table .= '<tr class="'.$tr_class.'">';
				}*/
			
				// Insert row_info for troubleshooting
				if ( devmode_active( array("whx4", "events") ) ) {
					if ( $display == 'table' ) {
						//$table .= '<div class="troubleshooting">row_info:<br />'.$row_info.'</div>'; //$row_info; // Display comments w/ in row for ease of parsing dev notes
					} else {
						//$ts_info .= 'row_info:<br />'.$row_info;
					}
					$ts_info .= $row_info; //$ts_info .= 'row_info:<br />'.$row_info;
				}
			
				// Add the table cells and close out the row
				/*if ( $display == 'table' && $delete_row != true ) {
				
					if ( $row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only" ) {
					
						// Single column row
						$row_content = "";
						if ( $row_type == "header" ) { 
							$td_class = "header";
							$row_content = $program_item_label;
						} else if ( $row_type == "program_note" ) {
							$td_class = "program_note";
							$row_content = $program_item_name;
						} else if ( $row_type == "label_only" ) {
							$td_class = "label_only";
							$row_content = $program_item_label;
						} else if ( $row_type == "title_only" ) {
							$td_class = "title_only";
							$row_content = $program_item_name;
						}
						if ( $placeholder_label == true ) { $td_class .= " placeholder"; }
						$table .= '<td class="'.$td_class.'" colspan="2">'.$row_content.'</td>';
					
					} else {
					
						// Two column standard row
					
						$td_class = "program_label";
					
						if ( $show_item_label != true || empty($program_item_label) ) { $td_class .= " no_label"; }
						if ( $placeholder_label == true ) { $td_class .= " placeholder"; }
						if ( $label_update_required == true ) { $td_class .= " update_required"; }
					
						$table .= '<td class="'.$td_class.'">'.$program_item_label.'</td>';
						$td_class = "program_item";
						if ( $placeholder_item == true ) { $td_class .= " placeholder"; }
						$table .= '<td class="'.$td_class.'">'.$program_item_name.'</td>';
					
					}
					$table .= '</tr>';
				}*/
			
				// Data Cleanup -- WIP
				// ...figuring out how to sync repertoire related_events w/ updates to program items -- display some TS info to aid this process
				/*if ( devmode_active() ) {
					$arr_row_info = event_program_row_cleanup ( $post_id, $i, $row, "program_items" );								
					$ts_info .= $arr_row_info['info'];
					$row_errors = $arr_row_info['errors'];
					//if ( $row_errors ) { $post_errors = true; }
					if ( isset($row['program_item'][0]) ) {
						foreach ( $row['program_item'] as $program_item_obj_id ) {						
							$item_post_type = get_post_type( $program_item_obj_id );						
							if ( $item_post_type == 'repertoire' ) {
								// Update the repertoire_events field for this rep record, as needed
								$ts_info .= update_repertoire_events( $program_item_obj_id, false, array($post_id) );							
							}					
						}
					}
				}*/

				// --------------------
			
			//$i++;
		
		
		} // END foreach( $program_rows as $row )
    
    }
    
    /////
    
    // Build the table based on the table_rows array
    if ( $display == 'table' && count($table_rows) > 0 ) {
        
        if ( $display == 'table' ) {
        	$table_classes = "event_program program ".$program_layout;
            $table = '<table class="'.$table_classes.'">';
            $table .= '<tbody>';
        }

        // Has a Program Items header been designated? If so, then display it.
        $program_items_header = get_post_meta( $post_id, 'program_items_header', true );
        if ( $display == 'table' && !empty($program_items_header) ) {
            $table .= '<tr><th colspan="2"><h2>'.$program_items_header.'</h2></th></tr>'; //class=""
        } else {
        	// TBD display of header for non-table display
        }
        
        // --------------------
        
        foreach( $table_rows as $tr ) {
        
        	//$ts_info .= "tr: <pre>".print_r($tr, true)."</pre><br />";
        	
        	$table .= '<tr id="'.$tr['tr_id'].'" class="'.$tr['tr_class'].'">';
        	foreach ( $tr['tds'] as $td ) {
        		$table .= '<td class="'.$td['td_class'].'" colspan="'.$td['td_colspan'].'">'.$td['td_content'].'</td>';
        	}
        	$table .= '</tr>';
        	
        	/*
        
        	//$tr;
        	//$show_row
        	//$row_type
        	//$td_class
        	//$td_class1
        	//$td_class2
        	//$show_item_authorship = true;
			//$show_person_dates = true;
        	
        	
        	// Set up the table row
			$tr_class = "program_objects";
			if ( $show_row == '0' || $show_row == 0 ) { $tr_class .= " hidden"; } else { $tr_class .= " show_row"; }
			if ( $show_person_dates == false ) { $tr_class .= " hide_person_dates"; } else { $tr_class .= " show_person_dates"; }
            if ( $num_row_items > 1 || $grouped_row == true ) { $tr_class .= " grouping"; }
            
			$table .= '<tr class="'.$tr_class.'">';
			
			if ( $row_type == "header" || $row_type == "program_note" || $row_type == "label_only" || $row_type == "title_only" ) {
                    
				// Single column row
				
				$row_content = "";
				if ( $row_type == "header" ) { 
					$td_class = "header";
					$row_content = $program_item_label;
				} else if ( $row_type == "program_note" ) {
					$td_class = "program_note";
					$row_content = $program_item_name;
				} else if ( $row_type == "label_only" ) {
					$td_class = "label_only";
					$row_content = $program_item_label;
				} else if ( $row_type == "title_only" ) {
					$td_class = "title_only";
					$row_content = $program_item_name;
				}
				if ( $placeholder_label == true ) { $td_class .= " placeholder"; }
				$table .= '<td class="'.$td_class.'" colspan="2">'.$row_content.'</td>';
				
			} else {
				
				// Two column standard row
				
				$td_class = "program_label";
				
				if ( $show_item_label != true || empty($program_item_label) ) { $td_class .= " no_label"; }
				if ( $placeholder_label == true ) { $td_class .= " placeholder"; }
				if ( $label_update_required == true ) { $td_class .= " update_required"; }
				
				$table .= '<td class="'.$td_class.'">'.$program_item_label.'</td>';
				$td_class = "program_item";
				if ( $placeholder_item == true ) { $td_class .= " placeholder"; }
				$table .= '<td class="'.$td_class.'">'.$program_item_name.'</td>';
				
			}
				
			$table .= '</tr>';
			*/
        }
		
		// --------------------
		
		// Close the table
        if ( $display == 'table' ) {
            $table .= '</tbody>';
            $table .= '</table>';
        }
        
        $info .= $table;
        
    } // end if $rows
    
    $arr_info['info'] = $info;
    $arr_info['ts_info'] = $ts_info;
	return $arr_info;
	
}

//
function get_program_item_label ( $row = null ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
	
	// Init vars
	$arr_info = array();
	$ts_info = "";
	$item_label = "";
	$placeholder = false;
	
	// Parse & Extract args
	//$args = wp_parse_args( $args, $defaults );
	//extract( $args );
	        
	if ( isset($row['item_label'][0]) ) { 
	
		$item_label = get_the_title($row['item_label'][0]);
		$ts_info .= "program_item_label = row['item_label'][0]<br />";

	} else if ( isset($row['item_label']) && !empty($row['item_label']) ) { 

		$term = get_term( $row['item_label'] );
		if ( !empty($term) ) { 
			$item_label = $term->name;
			$ts_info .= "program_item_label = row['item_label']: ".$row['item_label']."<br />";
		} else {
			$ts_info .= "no term found for: ".$row['item_label']."<br />";
		}
	}
    
	if ( !empty($item_label)) {
		
		// TODO: if a proper item_label is set, delete item_label_old
		
	} else {
		
		// Program item label is empty -- look for a placeholder value
		$ts_info .= "program_item_label is empty -> look for placeholder<br />";
		
		if ( isset($row['item_label_old'][0]) && $row['item_label_old'][0] != "" ) {
			
			$label_update_required = true;
			$item_label = get_the_title($row['item_label_old'][0]);
			$ts_info .= "item_label_old[0]: ".$row['item_label_old'][0]."<br />";
			
		} else if ( isset($row['item_label_old'] ) && $row['item_label_old'] != "" ) { 
			
			$label_update_required = true;
			$item_label = get_the_title($row['item_label_old']);
			$ts_info .= "item_label_old: ".print_r($row['item_label_old'], true)."<br />";
			
		} else if ( isset($row['item_label_txt']) && $row['item_label_txt'] != "" && $row['item_label_txt'] != "x" ) { 
			
			$placeholder = true;
			$item_label = $row['item_label_txt'];
			$ts_info .= "item_label_txt: ".print_r($row['item_label_txt'], true)."<br />";
			
		}
		
		if ( empty($item_label)) {
			$ts_info .= "program_item_label is still empty -- use title as label?<br />";
		}
		
	}
    
	//
	$arr_info['item_label'] = $item_label;
	$arr_info['placeholder'] = $placeholder;
	if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
	
	return $arr_info;
	
}

//
function get_program_item_name ( $args = array() ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = false;
    sdg_log( "divline2", $do_log );

	// Init vars
	$arr_info = array();
	$ts_info = "";
	//
	$item_name = "";
	$title_as_label = "";
    
    $ts_info .= ">>> get_program_item_name <<<<br />";
    //$ts_info .= "args as passed to get_program_item_name: <pre>".print_r($a,true)."</pre>";
    
    // TODO: move placeholder matching outside of this function to a separate fcn for ACF program row updates
    
    // Defaults
	$defaults = array(
		'program_type'	=> 'default',
		'row_type'		=> 'default', // other possible values include: "header", ...?
		'row'			=> null,
		'program_item_obj_id' => null,
		'program_item_label' => null, // used for match args and to determine title_as_label >> do this some other way before calling this fcn?
	);
	
	// Parse & Extract args
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
    
    // TODO: add option to display all movements/sections of a musical work
    
    //$ts_info .= "row: <pre>".print_r($row, true)."</pre>";
			
	if ( $program_item_obj_id ) {

		$ts_info .= "program_item_obj_id: $program_item_obj_id<br />";
			
		$item_post_type = get_post_type( $program_item_obj_id );
		$ts_info .= "item_post_type: $item_post_type<br />";
		
		// defaults -- tft?
		$show_item_authorship = true;
		$show_item_title = true;
		
		if ( $item_post_type == 'repertoire' ) {
			
			// TODO: check to see if the work is excerpted from another work. The goal is to show the opus/cat num and composer only once per excerpted work per program.
					
			// Get the name of the Musical Work using get_rep_info fcn
			// (for row_type title_only, get rep info as item_name. For standard two-col, get title as item_label and authorship as item_name)
				
			if ( $row_type == 'title_only' ) {
			
				//$show_item_authorship = false;
				$arr_item_name = get_rep_info( $program_item_obj_id, 'display', $show_item_authorship, true );
				$item_name = $arr_item_name['info'];
				$ts_info .= $arr_item_name['ts_info'];
			
			} else if ( empty($program_item_label) ) {

				$ts_info .= "program_item_label is empty >> use title in left col<br />";

				// If the label is empty, use the title of the musical work in the left-col position and use the composer name/dates in the right-hand column.
				$arr_item_name = get_rep_info( $program_item_obj_id, 'display', false, true ); // item name WITHOUT authorship info
				$title_as_label = $arr_item_name['info'];
				$ts_info .= $arr_item_name['ts_info'];
				
				if ( $program_type == "concert_program" ) { $authorship_format = 'concert_item'; } else { $authorship_format = 'title_as_label'; }
				$ts_info .= "program_type: ".$program_type."<br />";
				$ts_info .= "authorship_format: ".$authorship_format."<br />";
				//if ( $program_type == "service_order" ) { $authorship_format = 'service_order'; } else { $authorship_format = 'concert_item'; } // wip
				$authorship_args = array( 'data' => array( 'post_id' => $program_item_obj_id ), 'format' => $authorship_format, 'abbr' => false );
				$arr_authorship_info = get_authorship_info ( $authorship_args );
				$item_name = $arr_authorship_info['info'];
				$ts_info .= $arr_authorship_info['ts_info'];

			} else {
				
				$arr_item_name = get_rep_info( $program_item_obj_id, 'display', $show_item_authorship, $show_item_title );
				$item_name = $arr_item_name['info'];
				$ts_info .= $arr_item_name['ts_info'];

			}

			//$ts_info .= "item_name: ".$item_name."<br />";

		} else if ( $item_post_type == 'sermon' ) {

			$sermon_author_ids = get_post_meta( $program_item_obj_id, 'sermon_author', true );
			$ts_info .= "sermon_author_ids: ".print_r($sermon_author_ids, true)."<br />";
			// TODO: deal w/ possibility of multiple authors
		
			$sermon_author = get_the_title( $sermon_author_ids[0] );
			if ( $sermon_author ) { $item_name = $sermon_author.": "; }
			$item_name .= make_link( get_permalink($program_item_obj_id), get_the_title($program_item_obj_id) );

		} else if ( $item_post_type == 'reading' ) {

			$ts_info .= "item_post_type: reading<br />";

			$post_title = get_the_title($program_item_obj_id);
			if ( preg_match('/\[(.*)\]/',$post_title) ) {
				$item_name = do_shortcode( $post_title ); // wip
			} else {
				$item_name = $post_title;
			}

			$ts_info .= "post_title: '$post_title'<br />";

		} else { // Not of posttype repertoire, sermon, or reading

			$post_title = get_the_title($program_item_obj_id);
			if ( preg_match('/\[(.*)\]/',$post_title) ) {
				$item_name = do_shortcode( $post_title );
			} else {
				$item_name = $post_title;
			}

		}

	}
	
	// Is there a program note for this item? If so, append it to the item name
	if ( isset($row['program_item_note']) && $row['program_item_note'] != "" ) {
		if ( $title_as_label != "" ) { 
			$title_as_label .= "<br /><em>".$row['program_item_note']."</em>";
		} else if ( $item_name != "" ) { 
			$item_name .= "<br /><em>".$row['program_item_note']."</em>";
		}            
	}
    
    if ( empty($item_name) ) {        
        $ts_info .= "item_name is empty >> use placeholder<br />";        
        if ( isset($row['program_item_txt']) && $row['program_item_txt'] != "" && $row['program_item_txt'] != "x" ) {
            $item_name = $row['program_item_txt'];
        }
    }
    
	$ts_info .= "item_name: '$item_name'<br />";
	$ts_info .= "title_as_label: '$title_as_label'<br />";
    
	//
	$arr_info['title_as_label'] = $title_as_label;
	$arr_info['item_name'] = $item_name;
	if ( $do_ts ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
	
	return $arr_info;

}

//
function program_contains_repertoire ( $rows = array() ) {
	foreach( $rows as $row ) {
		if ( isset($row['program_item']) && is_array($row['program_item']) ) {
			// Loop through the program items for this row (usually there is only one)
			foreach ( $row['program_item'] as $program_item ) {
				$program_item_obj_id = $program_item; // ACF is now set to return ID for relationship field, not object
				if ( $program_item_obj_id ) {
					$item_post_type = get_post_type( $program_item_obj_id );
					if ( $item_post_type == 'repertoire' ) { return true; }
				}
			}
		}
	}
	// If we've gotten this far, then no repertoire items were found
	return false;
}

function compile_program_rows () {
	// TBD
}

//
function get_program_item_ids ( $rows = array() ) {

	$arr_ids = array();
	
	foreach( $rows as $r => $row ) {
		if ( isset($row['program_item']) && is_array($row['program_item']) ) {
			// Loop through the program items for this row (usually there is only one)
			foreach ( $row['program_item'] as $i => $program_item ) {
				$program_item_obj_id = $program_item; // ACF is now set to return ID for relationship field, not object
				if ( $program_item_obj_id ) {
					$arr_ids[$r.'-'.$i] = $program_item_obj_id;
				}
			}
		} else {
			$arr_ids[$r] = null;
		}
	}
	
	return $arr_ids;

}

//
//function get_program_composers ( $item_ids = array() ) {
function set_row_authorship_display ( $item_ids = array() ) {

	$arr_row_settings = array();
	$arr_items = array();
	//$ts_info = "";
	
	$prev_row = null;
	$prev_num = null;
	
	foreach( $item_ids as $x => $item_id ) {
		$item_post_type = get_post_type( $item_id );
		if ( $item_post_type == 'repertoire' ) {
			$item_composer_ids = get_composer_ids( $item_id );
			if ( is_array($item_composer_ids) ) {
				foreach ( $item_composer_ids as $composer_id ) {
					$arr_items[$composer_id][$x] = $item_id;
				}
			}
		}
	}
	
	$num_composers = count($arr_items);
	
	foreach ( $arr_items as $composer_id => $placements ) {
	
		// Determine visibility of composer name/dates
		$i = 0;
		foreach ( $placements as $x => $item_id ) {
			
			list($row, $num) = explode('-', $x); //list($row, $num, $item_id) = explode('-', $x);
			$show_name = false;
			$show_dates = false;
				
			if ( count($placements) == 1 ) {
			
				// If a composer appears only once in the program, then do show name and dates
				$show_name = true;
				$show_dates = true;
			
			} else {
				
				if ( $i == 0 ) {
					// Show name snd dates for first instance of composer in program
					$show_name = true;
					$show_dates = true;
				} else if ( $num_composers > 1 && ( $prev_row != $row || ( $prev_row == $row && $num != $prev_num+1 ) ) ) {
					// If it's a mixed-composer program, then show name if this is a new row or non-consecutive in same row
					$show_name = true;
				}
				
			}
			
			// Finally, check to see if this is a chant record or other special rep type for which person_dates are NEVER to be shown.
			if ( has_term( 'psalms', 'repertoire_category', $item_id ) || has_term( 'anglican-chant', 'repertoire_category', $item_id ) && ( !has_term( 'motets', 'repertoire_category', $item_id ) && !has_term( 'anthems', 'repertoire_category', $item_id ) ) ) {			
				$show_dates = false;
				//$ts_info .= "item is in psalms or anglican-chant category, therefore set show_person_dates to false<br />";				
			}
			
			//
			$arr_row_settings[$x] = array( 'composer_id' => $composer_id, 'show_name' => $show_name, 'show_dates' => $show_dates );
			//$arr_row_settings[$x] = array( 'r' => $row, 'n' => $num, 'composer_id' => $composer_id, 'show_name' => $show_name, 'show_dates' => $show_dates );				
				
			$prev_row = $row;
			$prev_num = $num;
			
			$i++;
		
		}
		
	}
		
	//$arr['ids'] = $arr_items;
	//$arr['info'] = $ts_info;
	//return $arr;
	
	//return $arr_items;
	
	return $arr_row_settings;

}


/* obsolete -- to be deleted, most likely...
function get_program_composer_ids ( $item_ids = array() ) {

	$arr_ids = array();
	
	foreach( $item_ids as $item_id ) {	
		$item_post_type = get_post_type( $item_id );
		if ( $item_post_type == 'repertoire' ) {
			$item_composer_ids = get_composer_ids( $item_id );
			$arr_ids = array_merge($arr_ids, $item_composer_ids);
		}
	}
	
	$arr_ids = array_unique($arr_ids);
	return $arr_ids;	

}
*/

/*********** ADMIN FUNCTIONS ***********/

// WIP

/* ***
 * 
 * 
 */

function event_program_row_cleanup ( $post_id = null, $i = null, $row = null, $repeater_name = null ) {

	if ( $post_id === null || $i === null || $row === null || $repeater_name === null ) {
		return "Insufficient info to run row cleanup<br />";
	}
	
	// Init vars
	$arr_info = array();
	$ts_info = "";
	
	// With the introduction of the `row_type` field to the ACF repeater fields `personnel` and `program_items`, many posts need to be updated retroactively and several other fields (e.g. is_header) became obsolete and need to be phased out.
	$row_type_update = false;
	$arr_field_updates = array();
	$arr_field_deletions = array();
	$placeholders = false;
	// TODO: better error handling
	$errors = false;
	
	$ts_info .= "post_id: ".$post_id."/row [$i]<br />";	
	$ts_info .= "repeater_name: ".$repeater_name."<br />";
	
	$row_as_txt = "<pre>".print_r($row, true)."</pre>";
	//$ts_info .= $row_as_txt;
	//$exp_args = array( 'text' => $row_as_txt, 'preview_text' => "Show row..." );
	//$ts_info .= expandable_text( $exp_args ); // Not working yet
	//
	//$ts_info .= ": <pre>".print_r($row, true)."</pre>";
	//$ts_info .= "<pre>".print_r($row, true)."</pre>";
						
	// Is a row_type set?
	if ( isset($row['row_type']) && $row['row_type'] != "" ) {	
		$row_type = $row['row_type'];
		$ts_info .= "row_type: ".$row_type."<br />";		
	} else {	
		$row_type = null;
		$ts_info .= "row_type not set<br />";		
	}
	
	// Personnel
	// +~+~+~+~+~+~+~+~+~+~+~
	if ( $repeater_name == "personnel" ) {		
		
		// Handle role and person/group, if any
		
		// Role
		if ( isset($row['role']) && $row['role'] != "" ) {
			$role = $row['role'];
			//$info .= "role: ".print_r($role, true)."<br />";
		} else {
			$role = null;
		}
		// TBD: do we need to keep role_old for X-check?
		if ( isset($row['role_old']) && $row['role_old'] != "" ) {
			$role_old = $row['role_old'];
			// If the role is properly set, then we can get rid of the old value, right?
			//if ( $role && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_role_old' ) ) { $arr_field_deletions[] = "role_old"; }
		} else {
			$role_old = null;
		}
		if ( isset($row['role_txt']) && $row['role_txt'] != "" ) {
			$role_txt = $row['role_txt'];
			//$ts_info .= "Placeholder role_txt: $role_txt<br />";
			$placeholders = true;
		} else {
			$role_txt = null;
		}
		// If we've got both role and role_txt OR role_old, take note... Action TBD. 
		// Add get_the_title for role, role_old to compare with placeholder value?
		if ( $role && ( $role_txt || $role_old ) ) {
			$ts_info .= "role: ".print_r($role, true)." // role_txt: ".$role_txt." // role_old: ".print_r($role_old, true)."<br />";
		}
		// TODO: ?
		
		// Person or group
		if ( isset($row['person']) && $row['person'] != "" ) {
			$person = $row['person'];
			//$ts_info .= "person: ".print_r($person, true)."<br />";
		} else {
			$person = null;
		}
		if ( isset($row['person_txt']) && $row['person_txt'] != "" ) { 
			$person_txt = $row['person_txt'];
			$placeholders = true;
		} else {
			$person_txt = null;
		}
		// If we've got both person and person_txt, take note... Action TBD.
		// Add get_the_title for person to compare with placeholder value?
		if ( $person && $person_txt ) {
			$ts_info .= "person: ".print_r($person, true)." // person_txt: ".$person_txt."<br />";
		}
		
		// Match placeholders
	
		// If role is empty and role_txt is NOT, try to match the placeholder
		if ( $role_txt && !($role) ) {
			$title_to_match = $role_txt;
			$field_name = "role";
			// TODO: deal w/ junk values like title_to_match == 'x'
			$ts_info .= ">> seeking match for ROLE placeholder value: '$title_to_match'<br />";
			$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => $repeater_name, 'field_name' => $field_name, 'taxonomy' => true );
			$arr_matches = match_placeholder( $match_args );
			$matches = $arr_matches['matches'];
			$match_info = $arr_matches['info'];
			$ts_info .= $match_info;
			// If no match was found, tag the post accordingly
			if ( empty($matches) ) {				
				$ts_info .= sdg_add_post_term( $post_id, 'unmatched-placeholder-only', 'admin_tag', true );
			}
		}
		
		// If person is empty and person_txt is NOT, try to match the placeholder
		if ( $person_txt && !($person) ) {
			$title_to_match = $person_txt;
			$field_name = "person";
			// TODO: deal w/ junk values like title_to_match == 'x'
			$ts_info .= ">> seeking match for PERSON placeholder value: '$title_to_match'<br />";
			$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => $repeater_name, 'field_name' => $field_name, 'taxonomy' => false );
			$arr_matches = match_placeholder( $match_args );
			$matches = $arr_matches['matches'];
			$match_info = $arr_matches['info'];
			$ts_info .= $match_info;
			// If no match was found, tag the post accordingly
			if ( empty($matches) ) {				
				$ts_info .= sdg_add_post_term( $post_id, 'unmatched-placeholder-only', 'admin_tag', true );
			}
		}
		
		// +~+~+~+~+~+~+~+~+~+~+~
		// Deal w/ row settings and obsolete fields
		
		// header_txt -- for header rows only (equivalent to program_item_txt)
		if ( isset($row['header_txt']) ) {
			if ( $row['header_txt'] != "" ) {
				if ( empty($row_type) ) {
					// If the header_txt is not empty and the row_type isn't set yet, set it
					$row_type = "header";
					$row_type_update = true;
				}
			} else if ( metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_header_txt' ) ) {
				// If header_txt is empty, delete the empty meta row
				$arr_field_deletions[] = "header_txt";
			}		
		}
		
		// If the row_type is STILL empty, set it to the default
		if ( empty($row_type) ) {
			$row_type = "default";
			$row_type_update = true;
		}
		
		// Clear out empty fields not needed per row_type
		if ( $row_type == "role_only" || $row_type == "header" ) {
			// If row_type == "role_only" and person/person_txt is/are empty, delete the empty meta rows
			if ( empty($person) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_person' ) ) { $arr_field_deletions[] = "person"; }
		}
		if ( $row_type == "name_only" || $row_type == "program_note" ) {
			// If row_type == "name_only" and role/role_txt is/are empty, delete the empty meta rows
			if ( empty($role) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_role' ) ) { $arr_field_deletions[] = "role"; }
		}
		// Delete empty placeholder fields, whatever the row_type
		if ( empty($role_txt) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_role_txt' ) ) { $arr_field_deletions[] = "role_txt"; }
		if ( empty($person_txt) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_person_txt' ) ) { $arr_field_deletions[] = "person_txt"; }
		
		// Now that we've dealt with the obsolete field values, we can delete/clear them
		// TODO: check to see if these metadata actually exist in the DB before trying to delete them
		// Note that fields with defaults will appear to exist in $row array, but may not actually be in the DB
		//if ( metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_role_old' ) ) { $arr_field_deletions[] = "role_old"; }
		
		// Delete the personnel_url meta record, if it exists and is empty
		if ( isset($row['personnel_url']) && empty($row['personnel_url']) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_personnel_url' ) ) { $arr_field_deletions[] = "personnel_url"; }
			
		
	}
	
	// Program Items
	if ( $repeater_name == "program_items" ) {
	
		//$arr_obsolete_fields = array( "is_header", "show_item_label", "show_item_title" );
		//$arr_placeholder_fields = array( "item_label" => "item_label_txt", "program_item" => "program_item_txt" );
		
		// +~+~+~+~+~+~+~+~+~+~+~
		// Handle program item label and program item, if any
		
		// Item label
		if ( isset($row['item_label']) && $row['item_label'] != "" ) { 
			$item_label = $row['item_label'];
			//$ts_info .= "item_label: ".print_r($item_label, true)."<br />"; //$ts_info .= "item_label: $item_label<br />";
		} else {
			$item_label = null;
		}
		if ( isset($row['item_label_txt']) && $row['item_label_txt'] != "" ) {
			$item_label_txt = $row['item_label_txt'];
			//$ts_info .= "Placeholder item_label_txt: $item_label_txt<br />";
			$placeholders = true;
		} else {
			$item_label_txt = null;
		}
		// If we've got both $item_label and $item_label_txt, take note... Action TBD.
		// Add get_the_title for item_label to compare with placeholder value?
		if ( $item_label && $item_label_txt ) {
			$ts_info .= "item_label: ".print_r($item_label, true)." // item_label_txt: ".$item_label_txt."<br />";
		}
		
		// Program item
		if ( isset($row['program_item']) && $row['program_item'] != "" ) { 
			$program_item = $row['program_item'];
			//$ts_info .= "program_item: ".print_r($program_item, true)."<br />";
		} else {
			$program_item = null;
		}
		if ( isset($row['program_item_txt']) && $row['program_item_txt'] != "" ) { 
			$program_item_txt = $row['program_item_txt'];
			$placeholders = true;
		} else {
			$program_item_txt = null;
		}
		// If we've got both program_item and program_item_txt, take note... Action TBD.
		// Add get_the_title for program_item to compare with placeholder value?
		if ( $program_item && $program_item_txt ) {
			$ts_info .= "program_item: ".print_r($program_item, true)." // program_item_txt: ".$program_item_txt."<br />";
		}
	
		// If values are saved for both program_item AND program_item_txt, then clear out the placeholder value -- ???
		// WIP/TODO/TBD
	
		// Match placeholders
	
		// If item_label is empty and item_label_txt is NOT, try to match the placeholder
		if ( $item_label_txt && !($item_label) ) {
			$title_to_match = $item_label_txt;
			$field_name = "item_label";
			// TODO: deal w/ junk values like title_to_match == 'x'
			$ts_info .= ">> seeking match for LABEL placeholder value: '$title_to_match'<br />";
			$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => $repeater_name, 'field_name' => $field_name, 'taxonomy' => true ); // , 'display' => $display
			$arr_matches = match_placeholder( $match_args );
			$matches = $arr_matches['matches'];
			$match_info = $arr_matches['info'];
			$ts_info .= $match_info;
			// If no match was found, tag the post accordingly
			if ( empty($matches) ) {				
				$ts_info .= sdg_add_post_term( $post_id, 'unmatched-placeholder-only', 'admin_tag', true );
			}
		}
		
		// If program_item is empty and program_item_txt is NOT, try to match the placeholder
		if ( $program_item_txt && !($program_item) ) {
			$title_to_match = $program_item_txt;
			$field_name = "program_item";
			// TODO: deal w/ complex cases like Psalms where
			// e.g. program_item_txt = "22" and program_item_title_for_matching = "Psalm"...
			$ts_info .= ">> seeking match for ITEM placeholder value: '$title_to_match'<br />";
			$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => $repeater_name, 'field_name' => $field_name, 'taxonomy' => false ); // , 'display' => $display
			$arr_matches = match_placeholder( $match_args );
			$matches = $arr_matches['matches'];
			$match_info = $arr_matches['info'];
			$ts_info .= $match_info;
			// If no match was found, tag the post accordingly
			if ( empty($matches) ) {				
				$ts_info .= sdg_add_post_term( $post_id, 'unmatched-placeholder-only', 'admin_tag', true );
			}
		}
		
		// +~+~+~+~+~+~+~+~+~+~+~
		// Deal w/ row settings and obsolete fields
		
		// Check for is_header value
		if ( isset($row['is_header']) && $row['is_header'] == 1 ) {		
			// if is_header == 1 && row_type is empty/DN exist for the post, then update row_type to "header" (later we'll also remove is_header meta record)
			if ( empty($row_type) || $row_type == "default" ) {
				$ts_info .= "Field is_header is set to TRUE >> Set row_type to 'header'<br />";
				$row_type = "header";
				$row_type_update = true;
			}
		}
		
		// Set row type based on whether item label and/or title are set to display
		// show_item_label?
		if ( isset($row['show_item_label']) && $row['show_item_label'] == 0 ) { $show_item_label = false; } else { $show_item_label = null; }
		// show_item_title?
		if ( isset($row['show_item_title']) && $row['show_item_title'] == 0 ) { $show_item_title = false; } else { $show_item_title = null; }
		
		// Which combo of fields? Both is the default.
		// TODO: Check to see if the field settings are contradictory -- e.g. row_type == "default" but show_item_title is set to false
		if ( $show_item_label && $show_item_title && $row_type !== "default" && $row_type !== "header" && $row_type !== "program_note" ) {
		
			// If the row_type isn't already set to "default", prep for the update
			$ts_info .= "Fields show_item_label AND show_item_title are set to TRUE >> Set row_type to 'default'<br />";
			$row_type = "default";
			$row_type_update = true;
			
		} else if ( $show_item_label && !$show_item_title && $row_type !== "label_only" && $row_type !== "header" && $row_type !== "program_note" ) {
		
			// If the row_type isn't already set to "label_only", prep for the update
			$ts_info .= "Field show_item_label == true / show_item_title == false >> Set row_type to 'label_only'<br />";
			$row_type = "label_only";
			$row_type_update = true;
			
		} else if ( $show_item_title && !$show_item_label && $row_type !== "title_only" && $row_type !== "header" && $row_type !== "program_note" ) {
		
			// If the row_type isn't already set to "title_only", prep for the update
			$ts_info .= "Field show_item_title == true / show_item_label == false >> Set row_type to 'title_only'<br />";
			$row_type = "title_only";
			$row_type_update = true;
			
		}
		
		// If the row_type is STILL empty, set it to the default
		if ( empty($row_type) ) {
			$row_type = "default";
			$row_type_update = true;
		}
		
		// Clear out empty fields not needed per row_type
		if ( $row_type == "label_only" || $row_type == "program_note" ) {
			// If row_type == "label_only" and program_item/program_item_txt is/are empty, delete the empty meta rows
			if ( empty($program_item) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_program_item' ) ) { $arr_field_deletions[] = "program_item"; }
		}
		if ( $row_type == "title_only" || $row_type == "program_note" ) {
			// If row_type == "title_only" and item_label/item_label_txt is/are empty, delete the empty meta rows
			if ( empty($item_label) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_item_label' ) ) { $arr_field_deletions[] = "item_label"; }
		}
		// Delete empty placeholder fields, whatever the row_type
		if ( empty($item_label_txt) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_item_label_txt' ) ) { $arr_field_deletions[] = "item_label_txt"; }
		if ( empty($program_item_txt) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_program_item_txt' ) ) { $arr_field_deletions[] = "program_item_txt"; }
		
		// Now that we've dealt with the obsolete field values, we can delete/clear them
		// TODO: check to see if these metadata actually exist in the DB before trying to delete them
		// Note that fields with defaults will appear to exist in $row array, but may not actually be in the DB
		if ( metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_is_header' ) ) { $arr_field_deletions[] = "is_header"; }
		if ( metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_show_item_label' ) ) { $arr_field_deletions[] = "show_item_label"; }
		if ( metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_show_item_title' ) ) { $arr_field_deletions[] = "show_item_title"; }
		
		// Delete the program_item_note meta record, if it exists and is empty
		if ( isset($row['program_item_note']) && empty($row['program_item_note']) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_program_item_note' ) ) { $arr_field_deletions[] = "program_item_note"; }
		
		// Delete the program_item_title_for_matching meta record, if it exists and is empty
		// TODO: phase out this field altogether? Do we need it for anything?
		if ( isset($row['program_item_title_for_matching']) && empty($row['program_item_title_for_matching']) && metadata_exists( 'post', $post_id, $repeater_name.'_'.$i.'_program_item_title_for_matching' ) ) { $arr_field_deletions[] = "program_item_title_for_matching"; }
		
	}
	
	if ( ! ($row_type_update || $arr_field_updates || $arr_field_deletions ) ) {
		
		$ts_info .= "No updates required for this row.<br />";
		$ts_info .= $row_as_txt."<br />";
		
	} else {
	
		// Display the original row info if we're making changes
		$ts_info .= $row_as_txt;
		
		// Prepare to do the updates and deletions

		if ( $row_type_update ) {
			$arr_field_updates["row_type"] = $row_type;
			//$ts_info .= "do row_type_update<br />";
		}
		
		// TODO: figure out how not to save empty meta rows in the first place...

		// Do the updates
		if ( $arr_field_updates ) { $ts_info .= "+++++ Do meta updates +++++<br />"; }
		foreach ( $arr_field_updates as $field_name => $field_value ) {
			$ts_info .= "update $field_name = $field_value ([$i] update_sub_field [$repeater_name/$field_name]) >>> ".'<span class="nb">';
			if ( update_sub_field( array($repeater_name, $i, $field_name), $field_value, $post_id ) ) { $ts_info .= "SUCCESS!"; } else { $ts_info .= "FAILED!"; $errors = true; }
			$ts_info .= '</span><br />';
		}
		if ( $arr_field_updates && $arr_field_deletions ) { $ts_info .= "-----------<br />"; }
	
		// Do the deletions
		if ( $arr_field_deletions ) { $ts_info .= "+++++ Do meta deletions +++++<br />"; }
		foreach ( $arr_field_deletions as $field_name ) {			
			$ts_info .= "delete $field_name ([$i] delete_sub_field [$repeater_name/$field_name]) >>> ".'<span class="nb">';
			if ( delete_sub_field( array($repeater_name, $i, $field_name), $post_id ) ) { $ts_info .= "SUCCESS!"; } else { $ts_info .= "FAILED!"; $errors = true; }
			$ts_info .= '</span><br />';
		}
		//if ( $arr_field_deletions ) { $info .= "-----------<br />"; }
		
	}
	
	$ts_info .= "+~+~+~+~+~+~+~+~+~+~+~<br />";
	
	$arr_info['ts_info'] = $ts_info;
	$arr_info['errors'] = $errors;
	
	return $arr_info;
}


// WIP
function match_program_placeholders( $row_category = null, $row = null ) { // match_program_placeholders
    
    // (1) Personnel: person_roles
    // (2) Personnel: persons
    // (3) Program Items: item_labels
    // (4) Personnel: program_items
    
    
    /*
    Fields -- personnel:
    -------------------
    *row_type
    *show_row
    header_txt -- for row_type == header >> add this to program_items repeater also?
    role
    role_txt
    role_old
    person
    person_txt
    person_url
    ++++++++++
    Fields -- program_items:
    -------------------
    *row_type
    (is_header)
    *show_row
    show_item_label
    show_item_title
    item_label
    item_label_txt
    program_item
    program_item_txt
    program_item_title_for_matching
    program_item_note
    /*
    
    // item label
	if ( isset($row['item_label_old'][0]) && $row['item_label_old'][0] != "" ) {

		$label_update_required = true;
		$item_label = get_the_title($row['item_label_old'][0]);
		$ts_info .= "item_label_old[0]: ".$row['item_label_old'][0]."<br />";

	} else if ( isset($row['item_label_old'] ) && $row['item_label_old'] != "" ) { 

		$label_update_required = true;
		$item_label = get_the_title($row['item_label_old']);
		$ts_info .= "item_label_old: ".print_r($row['item_label_old'], true)."<br />";

	}
	// Fill in Placeholder -- see if a matching record can be found to fill in a proper item_label
	if ( ($label_update_required == true || $placeholder_label == true) && $run_updates == true ) {
		$title_to_match = $item_label;
		$ts_info .= "seeking match for placeholder value: '$title_to_match'<br />";
		$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'repeater_name' => 'program_items', 'field_name' => 'item_label', 'taxonomy' => true, 'display' => $display );
		$match_result = match_placeholder( $match_args );
		$ts_info .= $match_result;
	} else {
		$ts_info .= "NO match_placeholder for program_item_label<br />";
		$ts_info .= sdg_add_post_term( $post_id, 'program-item-placeholders', 'admin_tag', true ); // $post_id, $arr_term_slugs, $taxonomy, $return_info
	}
	
	// program item
	
	// Fill in Placeholder -- see if a matching record can be found to fill in a proper program_item
	

		if ( isset($row['program_item_title_for_matching']) && $row['program_item_title_for_matching'] != "" ) {
			$title_to_match = $row['program_item_title_for_matching'];
			//$row_info .= "title_to_match = program_item_title_for_matching<br />";
		} else {
			$title_to_match = $program_item_name;
			//$row_info .= "title_to_match = program_item_name<br />";
		}
		//$row_info .= "title_to_match: [$title_to_match]<br />";

		$ts_info .= "seeking match for placeholder value: '$title_to_match'<br />";
		$match_args = array('index' => $i, 'post_id' => $post_id, 'item_title' => $title_to_match, 'item_label' => $program_item_label, 'repeater_name' => 'program_items', 'field_name' => 'program_item' ); // , 'display' => $display
		$match_result = match_placeholder( $match_args );
		$ts_info .= $match_result;

	// WIP
	if ( $match_result == true ) {
		//
	} else {
		$ts_info .= "NO match_placeholder for program_item_name<br />";
		$ts_info .= sdg_add_post_term( $post_id, 'program-item-placeholders', 'admin_tag', true ); // $post_id, $arr_term_slugs, $taxonomy, $return_info
	}
	*/
    
}


// Phasing this out in favor of the subsequent more general fcn
// Clean up Event Personnel: update row_type; fill in real values from placeholders; remove obsolete/orphaned postmeta
add_shortcode('event_personnel_cleanup', 'event_personnel_cleanup');
function event_personnel_cleanup( $atts = array() ) {
	
    // Not an admin? Don't touch my database!
    if ( !current_user_can('administrator') ) { return false; }
    
    // Parse shortcode attributes
	$args = shortcode_atts( array(
		'post_id'        => null, //get_the_ID(),
        'num_posts' => 5,
    ), $atts );
    
	// Extract attribute values into variables
    extract($args);
    
    $info = "";
    
    // Get all posts w/ personnel rows
    $wp_args = array(
		'post_type'   => 'event',
		'post_status' => 'publish',
        'posts_per_page' => $num_posts,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key'     => 'personnel',
                'compare' => 'EXISTS'
            ),
            array(
                'key'     => 'personnel',
                'compare' => '!=',
                'value'   => 0,
            ),
            array(
                'key'     => 'personnel',
                'compare' => '!=',
                'value'   => '',
            )
        ),
        'orderby'   => 'ID meta_key',
        'order'     => 'ASC',
        'tax_query' => array(
            //'relation' => 'AND', //tft
            array(
                'taxonomy' => 'admin_tag',
                'field'    => 'slug',
                'terms'    => array( 'program-personnel-placeholders' ),
                //'terms'    => array( 'program-placeholders' ),
                //'terms'    => array( $admin_tag_slug ),
                //'operator' => 'NOT IN',
            ),
            /*
            array(
                'taxonomy' => 'event-categories',
                'field'    => 'slug',
                'terms'    => 'choral-services',//'terms'    => 'worship-services',
                
            )*/
        ),
    );
    $result = new WP_Query( $wp_args );
    $posts = $result->posts;
    
    if ( $posts ) {
        
        $info .= "Found ".count($posts)." event posts with personnel postmeta.<br /><br />";
        //$info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
        //$info .= "Last SQL-Query: <pre>".$result->request."</pre>";
        
        foreach ( $posts AS $post ) {
        
            setup_postdata( $post );
            $post_id = $post->ID;
            $meta = get_post_meta( $post_id );
            //$post_info .= "post_meta: <pre>".print_r($meta, true)."</pre>";
            $post_info = ""; // init
            $num_repeater_rows = 0;
            $arr_repeater_rows_indices = array();
            
            $info .= '<div class="code">';
            $info .= "post_id: $post_id<br />";
            
            foreach ( $meta as $key => $value ) {
                
                if (strpos($key, 'personnel') == 0) { // meta_key starts w/ 'personnel' (no underscore)
                    $post_info .= "<code>$key => ".$value[0]."</code><br />";
                    if ($key == 'personnel') {
                        $num_repeater_rows = $value[0];
                    } else { // if (strpos($key, 'personnel_') == 0) -- meta_key starts w/ 'personnel_' (with underscore)
                        // Get the int indicating the row index, and add it to the array if it isn't there already
                        $int_str = preg_replace('/[^0-9]+/', '', $key);
                        if ( $int_str != "" && ! in_array($int_str, $arr_repeater_rows_indices) ) { $arr_repeater_rows_indices[] = $int_str; }
                    }
                }
                
                if ( empty($value) || $value == "x" ) {
                    // Delete empty or placeholder postmeta
                    //delete_post_meta( int $post_id, string $meta_key, mixed $meta_value = '' )
                    //delete_post_meta( $post_id, $key, $value );
                    /*if ( delete_post_meta( $post_id, $key, $value ) ) {
                        $post_info .= "delete_post_meta ok for post_id [$post_id], key [$key], post_id [$key]<br />";
                    } else {
                        $post_info .= "delete_post_meta FAILED for post_id [$post_id], key [$key], post_id [$key]<br />";
                    }*/
                }
                
            }
            
            // Check to see if 'personnel' int val matches number of rows indicated by postmeta fields
            if ( count($arr_repeater_rows_indices) != $num_repeater_rows ) {
                $post_info .= "<code>".print_r($arr_repeater_rows_indices, true)."</code><br />";
                $post_info .= "personnel official count [$num_repeater_rows] is ";
                if ( $num_repeater_rows < count($arr_repeater_rows_indices) ) {
                    $post_info .= "LESS than";
                } else if ( $num_repeater_rows > count($arr_repeater_rows_indices)) {
                    $post_info .= "GREATER than";
                }
                $post_info .= " num of repeater rows in postmeta [".count($arr_repeater_rows_indices)."] => cleanup required!<br />";
                $post_info .= sdg_add_post_term( $post_id, 'cleanup-required', 'admin_tag', true ); // $post_id, $arr_term_slugs, $taxonomy, $return_info
            }
                    
                // Remove row via ACF function -- ???
                /*if ( delete_row('personnel', $i, $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/delete_row/ -- syntax: delete_row($selector, $row_num, $post_id) 
                        $row_info .= "[personnel row $i deleted]<br />";
                        $deletion_count++;
                        sdg_log( "[personnel row $i deleted successfully]", $do_log );
                    } else {
                        $row_info .= "[deletion failed for personnel row $i]<br />";
                        sdg_log( "[failed to delete personnel row $i]", $do_log );
                    }*/
            
            $post_info .= "<br />";
            // TODO: figure out how to show info ONLY if changes have been made -- ??
            // Get personnel
    		$arr_personnel = get_event_personnel( $post_id, true, 'dev' ); // get_event_personnel( $post_id, $run_updates )
			$personnel = $arr_personnel['info'];
			$ts_info .= $arr_personnel['ts_info'];
            $post_info .= $personnel;
            // Get program_items?
            //$arr_program_items .= get_event_program_items( $post_id, true, 'dev' );
            
            $info .= $post_info;
            $info .= '</div>'; // class="code"
            
        }
        
    } else {
        
        $info .= "No matching posts found.<br />";
        $info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
        $info .= "Last SQL-Query: <pre>".$result->request."</pre>";
        
    }
    
    return $info;
    
}

// Clean up Event Program Items: update row_type; fill in real values from placeholders; remove obsolete/orphaned postmeta
add_shortcode('event_program_cleanup', 'event_program_cleanup');
function event_program_cleanup( $atts = array() ) {

	// Not an admin? Don't touch my database!
    if ( !current_user_can('administrator') ) { return false; }
    
    // Parse shortcode attributes
	$args = shortcode_atts( array(
		'ids'   => null, //get_the_ID(),
        'num_posts' => 1, // default is one post at a time because most have multiple program rows and these meta queries are SLOW!
        'scope'		=> 'both', // personnel, program_items, or both
        'field_check'	=> 'all', // other options include: role_old, header_txt, placeholders, row_type
    ), $atts );
    
	// Extract attribute values into variables
    extract($args);
    
    // Init vars
    $info = "";
    $ts_info = "";
    $posts = array();
    $wp_args = array();
    
    // If an ID or IDs have been submitted, handle both personnel and program_items, whatever the submitted scope setting
    if ( !empty($ids) ) { $scope = "both"; }
    
    $info .= "scope: ".$scope."<br />";
    $info .= "field_check (initial): ".$field_check."<br />";
    $info .= "num_posts: ".$num_posts."<br />";
    if ( !empty($ids) ) {
    	$info .= "ids: ".$ids."<br />";
    	$post_ids = array_map( 'intval', birdhive_att_explode( $ids ) );
		$field_check = "N/A";
    } else {
    	$post_ids = null;
    }
    $info .= "++++++++++++++++++++++++++++++++++++++<br />";
    
    // Personnel
    if ( $scope == "personnel" || $scope == "both" ) {
    
    	$ts_info_personnel = "";
    
    	if ( !empty($ids) || ( $field_check != "all" && $field_check != "placeholders" && $field_check != "N/A" ) ) {
    	
			// First, a quick search to find posts with obsolete or empty meta, or by ID

			// Set up the query arguments
			$wp_args = array(
				'post_type' => 'event', // 'any'
				'post_status' => 'publish',
				'posts_per_page' => $num_posts,
				'orderby'   => 'ID meta_key',
				'order'     => 'ASC',
				'fields'	=> 'ids',
			);

			// Posts by ID
			if ( $post_ids ) { $wp_args['post__in'] = $post_ids; }
		
			// First round query -- the quick ones
			// Define meta_key and meta_value
			$meta_key = "personnel_XYZ_".$field_check;
			$meta_value = " ";
			$wp_args['meta_key'] = $meta_key;
			$wp_args['meta_value'] = $meta_value;
		
			$result = new WP_Query( $wp_args );
			$posts = $result->posts;
			$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			
		}
		
		if ( empty($posts) ) {
		
			//$ts_info .= "No matching posts found in initial quick query for personnel.<br />";
			//$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			//$ts_info .= "Last SQL-Query: <pre>".$result->request."</pre>";
		
			// No posts? Try a more expensive query...
			
			// Get all posts w/ personnel rows
			$wp_args = array(
				'post_type'   => 'event',
				'post_status' => 'publish',
				'posts_per_page' => $num_posts,
				'orderby'   => 'ID meta_key',
				'order'     => 'ASC',
				'fields'	=> 'ids',
				// Use admin_tag to filter out posts that have already been processed
				/*'tax_query' => array(
					array(
						'taxonomy' => 'admin_tag',
						'field'    => 'slug',
						'terms'    => array( 'program-rows-cleaned' ),
						'operator' => 'NOT IN',
					),
				)*/
			);
			
			// Posts by ID
			if ( $post_ids ) { 
				$wp_args['post__in'] = $post_ids;
			} else {				
				// No IDs? Then use admin_tag to filter out posts that have already been processed
				$wp_args['tax_query'] = array(
					array(
						'taxonomy' => 'admin_tag',
						'field'    => 'slug',
						'terms'    => array( 'program-rows-cleaned' ),
						'operator' => 'NOT IN',
					),
				);
			}
        
			// field_check?
			// Default to "all" for row_type and is_header, because check for row_type NOT EXISTS doesn't work, and is_header is for program_items only
			if ( $field_check == "all" || $field_check == "row_type" || $field_check == "is_header" ) {
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'personnel',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => 'personnel',
						'compare' => '!=',
						'value'   => 0,
					),
					array(
						'key'     => 'personnel',
						'compare' => '!=',
						'value'   => ' ',
					)
				);
			} else if ( $field_check == "header_txt" ) {
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'personnel',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => 'personnel_XYZ_header_txt',
						'value'   => 1,
					),
				);
			} else if ( $field_check == "placeholders" ) {
				// TODO: fix this -- yields no results, which can't be right
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'personnel',
						'compare' => 'EXISTS'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'personnel_XYZ_role_txt',
							'compare' => '!=',
							'value'   => ' ',
						),
						array(
							'key'     => 'personnel_XYZ_person_txt',
							'compare' => '!=',
							'value'   => ' ',
						),
					),
				);
			}
			
			$result = new WP_Query( $wp_args );
			$posts = $result->posts;
			$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
		
		}
		
		if ( !empty($posts) ) {
        
        	$info .= "<h2>Personnel</h2>";
			$repeater_name = "personnel";
			$info .= "field_check: ".$field_check."<br />";
			//if ( $ids ) { $info .= "ids: ".$ids."<br />"; }
        	//
        	//$ts_info_personnel .= "personnel TS:<br />";
			//$ts_info_personnel .= "Found ".count($posts)." event post(s) with program postmeta.<br /><br />"; //$info .= "Found ".count($posts)." event post(s) with personnel postmeta.<br /><br />";
			//$ts_info_personnel .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			//$ts_info_personnel .= "Last SQL-Query: <pre>".$result->request."</pre>";
			//$ts_info .= $ts_info_personnel;
			//
			$info .= "---------------------<br />";
			
			foreach ( $posts AS $post_id ) {
			
				// Init
				$info .= '<div class="post">';
				$post_info = "";
				$post_errors = false;
				
				$post_info .= "post_id: ".$post_id."<br />";
				
				// Get the program item repeater field values (ACF)
				$rows = get_field('personnel', $post_id);
				if ( empty($rows) ) { $rows = array(); }
				
				$post_info .= count($rows)." personnel row(s)<br />";
				//$post_info .= "+~+~+~+~+~+~+~+~+~+~+~<br />";
				$post_info .= "+~+~+~+~+~+~+~+~+~+~+~<br /><br />";
    
				if ( count($rows) > 0 ) {
					$i = 0;
					foreach ( $rows as $row ) {
						$row_errors = false;
						$post_info .= '<div class="program_row" style="border: 1px solid green; padding: 1rem; font-size: 0.9rem;">';
						$arr_row_info = event_program_row_cleanup ( $post_id, $i, $row, "personnel" );							
						$post_info .= $arr_row_info['info'];
						$row_errors = $arr_row_info['errors'];
						if ( $row_errors ) { $post_errors = true; $post_info .= "row_errors!<br />"; } //else { $post_info .= "( no row_errors )<br />"; }
						$post_info .= '</div>'; // program_row
						$i++;				
					}
				} else {
					$post_info .= "No matching personnel rows found.<br />";
					$post_info .= $ts_info_personnel;
				}
				
				// If there were no errors, add an admin_tag to indicate that this row has been cleaned up
				// TODO: figure out how to handle subsequent rounds of cleanup, if/when needed
				if ( $post_errors == false) { //if ( !$post_errors ) {
					$post_info .= sdg_add_post_term( $post_id, 'program-rows-cleaned', 'admin_tag', true );
					//$post_info .= "( no post_errors )<br />";
				} else {
					// Since there were errors that must be resolved, remove the program-rows-cleaned tag, if it was already added
					$post_info .= sdg_remove_post_term( $post_id, 'program-rows-cleaned', 'admin_tag', true );
					//$post_info .= "( post_errors! )<br />";
				}
		
				$info .= $post_info;
				$info .= '</div>'; // post
			
			}
			
			/*
			foreach ( $posts AS $post ) {
		
				setup_postdata( $post );
				$post_id = $post->ID;
				
				// Get rows... loop... update_row_type ( $row )
				
				// WIP 06/22/23
				
				$meta = get_post_meta( $post_id );
				//$post_info .= "post_meta: <pre>".print_r($meta, true)."</pre>";
				$post_info = ""; // init
				$num_repeater_rows = 0;
				$arr_repeater_rows_indices = array();
			
				$info .= '<div class="code">';
				$info .= "post_id: $post_id<br />";
			
				foreach ( $meta as $key => $value ) {
				
					if (strpos($key, 'personnel') == 0) { // meta_key starts w/ 'personnel' (no underscore)
						$post_info .= "<code>$key => ".$value[0]."</code><br />";
						if ($key == 'personnel') {
							$num_repeater_rows = $value[0];
						} else { // if (strpos($key, 'personnel_') == 0) -- meta_key starts w/ 'personnel_' (with underscore)
							// Get the int indicating the row index, and add it to the array if it isn't there already
							$int_str = preg_replace('/[^0-9]+/', '', $key);
							if ( $int_str != "" && ! in_array($int_str, $arr_repeater_rows_indices) ) { $arr_repeater_rows_indices[] = $int_str; }
						}
					}
				
					if ( empty($value) || $value == "x" ) {
						// Delete empty or placeholder postmeta
						//delete_post_meta( int $post_id, string $meta_key, mixed $meta_value = '' )
						//delete_post_meta( $post_id, $key, $value );
						//if ( delete_post_meta( $post_id, $key, $value ) ) {
							//$post_info .= "delete_post_meta ok for post_id [$post_id], key [$key], post_id [$key]<br />";
						//} else {
							//$post_info .= "delete_post_meta FAILED for post_id [$post_id], key [$key], post_id [$key]<br />";
						//}
					}
				
				}
			
				// Check to see if 'personnel' int val matches number of rows indicated by postmeta fields
				if ( count($arr_repeater_rows_indices) != $num_repeater_rows ) {
					$post_info .= "<code>".print_r($arr_repeater_rows_indices, true)."</code><br />";
					$post_info .= "personnel official count [$num_repeater_rows] is ";
					if ( $num_repeater_rows < count($arr_repeater_rows_indices) ) {
						$post_info .= "LESS than";
					} else if ( $num_repeater_rows > count($arr_repeater_rows_indices)) {
						$post_info .= "GREATER than";
					}
					$post_info .= " num of repeater rows in postmeta [".count($arr_repeater_rows_indices)."] => cleanup required!<br />";
					$post_info .= sdg_add_post_term( $post_id, 'cleanup-required', 'admin_tag', true ); // $post_id, $arr_term_slugs, $taxonomy, $return_info
				}
					
					// Remove row via ACF function -- ???
						//if ( delete_row('personnel', $i, $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/delete_row/ -- syntax: delete_row($selector, $row_num, $post_id) 
						//	$row_info .= "[personnel row $i deleted]<br />";
						//	$deletion_count++;
						//	sdg_log( "[personnel row $i deleted successfully]", $do_log );
						//} else {
						//	$row_info .= "[deletion failed for personnel row $i]<br />";
						//	sdg_log( "[failed to delete personnel row $i]", $do_log );
						//}
			
				$post_info .= "<br />";
				// TODO: figure out how to show info ONLY if changes have been made -- ??
				$post_info .= get_event_personnel( $post_id, true, 'dev' ); // get_event_personnel( $post_id, $run_updates )
				//$post_info .= get_event_program_items( $post_id, true, 'dev' );
			
				$info .= $post_info;
				$info .= '</div>';
			
			}*/
		
		} else {
		
			$info .= "No matching posts found.<br />";
			$info .= '<div class="troubleshooting">';
			$info .= "field_check: ".$field_check."<br />";
			$info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			$info .= "Last SQL-Query: <pre>".$result->request."</pre>";
			$info .= '</div>';
		
		}
    
    	// .....
    
    }
    
    // Program Items
    if ( $scope == "program_items" || $scope == "both" ) {
    
    	$ts_info_program_items = "";
    	
    	// TODO: revise to search more specifically for posts with problem meta -- e.g. is_header, show_item_label, show_item_title
    	
    	// If we're only looking at program_items, or if no posts were found in the personnel query, then start fresh
    	if ( $scope == "program_items" || empty($posts) ) {
    	
			// First round query -- the quick one			
			if ( !empty($ids) || ( $field_check != "all" && $field_check != "placeholders" && $field_check != "N/A" ) ) {
    	
				// First, a quick search to find posts with obsolete or empty meta, or by ID

				// Set up the query arguments
				$wp_args = array(
					'post_type' => 'event', // 'any'
					'post_status' => 'publish',
					'posts_per_page' => $num_posts,
					'orderby'   => 'ID meta_key',
					'order'     => 'ASC',
					'fields'	=> 'ids',
				);

				// Posts by ID
				if ( $post_ids ) { $wp_args['post__in'] = $post_ids; }
		
				// First round query -- the quick ones
				// Define meta_key and meta_value
				$meta_key = "program_items_XYZ_".$field_check;
				$meta_value = " ";
				$wp_args['meta_key'] = $meta_key;
				$wp_args['meta_value'] = $meta_value;
		
				$result = new WP_Query( $wp_args );
				$posts = $result->posts;
				$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			
			}
		
    	} else {
    		// Otherwise we'll continue with the posts we found in the personnel query, above
    	}
		
		if ( empty($posts) ) {
			
			// STILL no posts? Try a more expensive query...
		
			$info .= "No matching posts found in initial quick query for program_items.<br />";			
			
			// Get all posts w/ personnel rows
			$wp_args = array(
				'post_type'   => 'event',
				'post_status' => 'publish',
				'posts_per_page' => $num_posts,
				'orderby'   => 'ID meta_key',
				'order'     => 'ASC',
				'fields'	=> 'ids',
				// Use admin_tag to filter out posts that have already been processed
				/*'tax_query' => array(
					array(
						'taxonomy' => 'admin_tag',
						'field'    => 'slug',
						'terms'    => array( 'program-rows-cleaned' ),
						'operator' => 'NOT IN',
					),
				)*/
			);
			
			// Posts by ID
			if ( $post_ids ) { 
				$wp_args['post__in'] = $post_ids;
			} else {				
				// No IDs? Then use admin_tag to filter out posts that have already been processed
				$wp_args['tax_query'] = array(
					array(
						'taxonomy' => 'admin_tag',
						'field'    => 'slug',
						'terms'    => array( 'program-rows-cleaned' ),
						'operator' => 'NOT IN',
					),
				);
			}
				
			// field_check?
			// Default to "all" for row_type and header_txt, because check for row_type NOT EXISTS doesn't work, and header_txt is for personnel only
			if ( $field_check == "all" || $field_check == "row_type" || $field_check == "header_txt" ) {
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'program_items',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => 'program_items',
						'compare' => '!=',
						'value'   => 0,
					),
					array(
						'key'     => 'program_items',
						'compare' => '!=',
						'value'   => '',
					)
				);
			} else if ( $field_check == "mismatch" ) {
				// Check to see if row_type doesn't match show/hide settings
				// TODO: revised to check for other incorrect row_type possibilities?
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'    => 'program_items_XYZ_row_type',
						'value'  => 'default'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'program_items_XYZ_show_item_label',
							'value'   => 0,
						),
						array(
							'key'     => 'program_items_XYZ_show_item_title',
							'value'   => 0,
						),
					),
				);
			} else if ( $field_check == "is_header" ) {
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'program_items',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => 'program_items_XYZ_is_header',
						'value'   => 1,
					),
				);
			} else if ( $field_check == "placeholders" ) {
				// TODO: fix this -- yields no results, which can't be right
				$wp_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'program_items',
						'compare' => 'EXISTS'
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'program_items_XYZ_item_label_txt',
							'compare' => '!=',
							'value'   => ' ',
						),
						array(
							'key'     => 'program_items_XYZ_program_item_txt',
							'compare' => '!=',
							'value'   => ' ',
						),
					),
				);
			}
			
			$result = new WP_Query( $wp_args );
			$posts = $result->posts;
			$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			
		}
		
		if ( $posts ) {
        
        	$info .= "<h2>Program Items</h2>";
			$repeater_name = "program_items";			
			$info .= "field_check: ".$field_check."<br />";
			//if ( $ids ) { $info .= "ids: ".$ids."<br />"; }
			//$info .= "Found ".count($posts)." event post(s) with program_items postmeta.<br /><br />";
			//
        	//$ts_info_program_items .= "program_items TS:<br />";
			//$ts_info_program_items .= "Found ".count($posts)." event post(s) with program postmeta.<br /><br />"; //$info .= "Found ".count($posts)." event post(s) with personnel postmeta.<br /><br />";
			//$ts_info_program_items .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
			//$ts_info_program_items .= "Last SQL-Query: <pre>".$result->request."</pre>";
			if ( $scope == "program_items" ) {
				$info .= '<div class="troubleshooting">'.$ts_info_program_items.'</div>';
			}			
			//
			$info .= "---------------------<br />";
			
			foreach ( $posts AS $post_id ) {
				
				// Init
				$info .= '<div class="post">';
				$post_info = "";
				$post_errors = false;
				
				$post_info .= "post_id: ".$post_id."<br />";
				
				// Get the program item repeater field values (ACF)
				$rows = get_field('program_items', $post_id);
				if ( empty($rows) ) { $rows = array(); }
				
				$post_info .= count($rows)." program_items row(s)<br />";
				//$post_info .= "+~+~+~+~+~+~+~+~+~+~+~<br />";
				$post_info .= "+~+~+~+~+~+~+~+~+~+~+~<br />"; //<br />
    
				if ( count($rows) > 0 ) {
					$i = 0;
					foreach ( $rows as $row ) {
						$row_errors = false;
						$post_info .= '<div class="program_row" style="border: 1px solid green; padding: 1rem; font-size: 0.9rem;">';				
						$arr_row_info = event_program_row_cleanup ( $post_id, $i, $row, "program_items" );								
						$post_info .= $arr_row_info['info'];
						$row_errors = $arr_row_info['errors'];
						if ( $row_errors ) { $post_errors = true; $post_info .= "row_errors!<br />"; } //else { $post_info .= "( no row_errors )<br />"; }
						$post_info .= '</div>';					
						$i++;				
					}
				} else {
					$post_info .= "No matching program rows found.<br />";
					//$post_info .= $ts_info_program_items;
					//$post_info .= $ts_info;
				}
				
				// If there were no errors, add an admin_tag to indicate that this row has been cleaned up
				// TODO: figure out how to handle subsequent rounds of cleanup, if/when needed
				if ( $post_errors == false) { //if ( !$post_errors ) {
					$post_info .= sdg_add_post_term( $post_id, 'program-rows-cleaned', 'admin_tag', true );
					//$post_info .= "( no post_errors )<br />";
				} else {
					// Since there were errors that must be resolved, remove the program-rows-cleaned tag, if it was already added
					$post_info .= sdg_remove_post_term( $post_id, 'program-rows-cleaned', 'admin_tag', true );
					//$post_info .= "( post_errors! )<br />";
				}
				
				/*
				$meta = get_post_meta( $post_id );
				//$post_info .= "post_meta: <pre>".print_r($meta, true)."</pre>";
				$num_repeater_rows = 0;
				$arr_repeater_rows_indices = array();
			
				$info .= '<div class="code">';
				$info .= "post_id: $post_id<br />";
			
				foreach ( $meta as $key => $value ) {
				
					if (strpos($key, 'personnel') == 0) { // meta_key starts w/ 'personnel' (no underscore)
						$post_info .= "<code>$key => ".$value[0]."</code><br />";
						if ($key == 'personnel') {
							$num_repeater_rows = $value[0];
						} else { // if (strpos($key, 'personnel_') == 0) -- meta_key starts w/ 'personnel_' (with underscore)
							// Get the int indicating the row index, and add it to the array if it isn't there already
							$int_str = preg_replace('/[^0-9]+/', '', $key);
							if ( $int_str != "" && ! in_array($int_str, $arr_repeater_rows_indices) ) { $arr_repeater_rows_indices[] = $int_str; }
						}
					}
				
					if ( empty($value) || $value == "x" ) {
						// Delete empty or placeholder postmeta
						//delete_post_meta( int $post_id, string $meta_key, mixed $meta_value = '' )
						//delete_post_meta( $post_id, $key, $value );
						//if ( delete_post_meta( $post_id, $key, $value ) ) {
						//	$post_info .= "delete_post_meta ok for post_id [$post_id], key [$key], post_id [$key]<br />";
						//} else {
						//	$post_info .= "delete_post_meta FAILED for post_id [$post_id], key [$key], post_id [$key]<br />";
						//}
					}
				
				}
			
				// Check to see if 'personnel' int val matches number of rows indicated by postmeta fields
				if ( count($arr_repeater_rows_indices) != $num_repeater_rows ) {
					$post_info .= "<code>".print_r($arr_repeater_rows_indices, true)."</code><br />";
					$post_info .= "personnel official count [$num_repeater_rows] is ";
					if ( $num_repeater_rows < count($arr_repeater_rows_indices) ) {
						$post_info .= "LESS than";
					} else if ( $num_repeater_rows > count($arr_repeater_rows_indices)) {
						$post_info .= "GREATER than";
					}
					$post_info .= " num of repeater rows in postmeta [".count($arr_repeater_rows_indices)."] => cleanup required!<br />";
					$post_info .= sdg_add_post_term( $post_id, 'cleanup-required', 'admin_tag', true ); // $post_id, $arr_term_slugs, $taxonomy, $return_info
				}
					
					// Remove row via ACF function -- ???
						//if ( delete_row('personnel', $i, $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/delete_row/ -- syntax: delete_row($selector, $row_num, $post_id) 
						//	$row_info .= "[personnel row $i deleted]<br />";
						//	$deletion_count++;
						//	sdg_log( "[personnel row $i deleted successfully]", $do_log );
						//} else {
						//	$row_info .= "[deletion failed for personnel row $i]<br />";
						//	sdg_log( "[failed to delete personnel row $i]", $do_log );
						//}
			
				$post_info .= "<br />";
				// TODO: figure out how to show info ONLY if changes have been made -- ??
				$post_info .= get_event_personnel( $post_id, true, 'dev' ); // get_event_personnel( $post_id, $run_updates )
				//$post_info .= get_event_program_items( $post_id, true, 'dev' );
				*/
				$info .= $post_info;
				$info .= '</div>';
			
			}
		
		} else {
		
			if ( $scope == "program_items" ) {
				$info .= "No matching posts found.<br />";
				/*
				$ts_info .= "field_check: ".$field_check."<br />";
				$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
				$ts_info .= "Last SQL-Query: <pre>".$result->request."</pre>";
				*/
				if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
			}
		
		}
    	// .....
    
    }    
    
    // .....
    
    
    $info = '<div class="info">'.$info.'</div>';
    return $info;
    
}

function get_event_programs_containing_post( $post_id = null ) { // formerly get_program_containing_post
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
    
    global $post;	
    $info = ""; // init
    $arr_event_ids = array(); // init
	if ($post_id == null) { $post_id = get_the_ID(); }
        
    // Go straight to the DB and get ONLY the post IDs of relevant related event posts...
    global $wpdb;
    
    $sql = "SELECT `post_id` 
            FROM $wpdb->postmeta
            WHERE `meta_key` LIKE 'program_items_%_program_item'
            AND `meta_value` LIKE '%".'"'.$post_id.'"'."%'";
    
    /*$sql = "SELECT `post_id` 
            FROM $wpdb->postmeta, $wpdb->posts
            WHERE $wpdb->postmeta.`meta_key` LIKE 'program_items_%_program_item'
            AND $wpdb->postmeta.`meta_value` LIKE '%".'"'.$post_id.'"'."%'
            AND $wpdb->postmeta.`post_id`=$wpdb->posts.`ID`
            AND $wpdb->posts.`post_type`='event'";*/
    
    /*$sql = "SELECT `post_id` 
            FROM $wpdb->postmeta, $wpdb->posts
            WHERE `meta_key` LIKE 'program_items_%_program_item'
            AND `meta_value` LIKE '%".'"'.$post_id.'"'."%'
            AND `post_id`=`ID`
            AND `post_type`='event'";*/

    $arr_ids = $wpdb->get_results($sql);
    
    // Flatten the array by a layer; remove non-event posts
    foreach( $arr_ids as $arr ) {
        
        $related_post_id = $arr->post_id;
        if ( get_post_type( $related_post_id ) == 'event' ) {
            $arr_event_ids[] = $related_post_id;
        }        
            
        /*
        //$related_post = get_post( $related_post_id );
        //$related_post_type = $related_post->post_type;

        // if it is a legit published event, then show the info
        if ( $related_post_type == 'event' ) {
            $arr_event_ids[] = $related_post_id;
        }
        
        //$arr_event_ids[] = $arr->post_id;
        */
    }
    
    /*foreach( $arr_ids as $arr ) {
        $arr_event_ids[] = $arr->post_id;
    }*/
    
    /* OLD approach -- very very slow
        
    $wp_args = array(
        'posts_per_page'=> -1,
        'post_type'		=> 'event',
        'meta_query'	=> array(
            array(
                'key'		=> "program_items_XYZ_program_item", // name of custom field, with XYZ as a wildcard placeholder (must do this to avoid hashing)
                'compare' 	=> 'LIKE',
                'value' 	=> '"' . $post_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
            )
        )
    );

    $query = new WP_Query( $wp_args );
    $arr_posts = $query->get_posts();
    //$info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>"; // tft
    //$info .= "Last SQL-Query: <pre>".$query->request."</pre>";
    //$info .= "arr_posts: <pre>".print_r($arr_posts, true)."</pre>"; // tft

    wp_reset_query();
        
    }*/
    
    return $arr_event_ids;
	
}


/*** EVENT BOOKINGS ***/

// See sdg_placeholders


/*** EVENTS/WEBCASTS ***/

add_shortcode( 'display_webcasts', 'display_webcast_events' );
function display_webcast_events() {
	
	// Ensure the global $post variable is in scope
	//global $post; // ??? Is this actually necessary here?
	$info = "";
	
    // Query Events Manager [EM] posts
    // TODO: test this...
    $wp_args = array(
        'post_type'         => 'event',
        'posts_per_page'    => 5,
        'scope'   	        => 'future',
        'tax_query'         => array(
            array(
                'taxonomy' 	=> 'event-categories',
                'field' 	=> 'slug',
                'terms' 	=> 'webcasts'
            )
        )
    );

    $result = new WP_Query( $wp_args );
    $upcoming_events = $result->posts;

	// Loop through the events: set up each one as
	// the current post then use template tags to
	// display the title and content
	if (count($upcoming_events) > 0) { $info .= "<h2>Upcoming</h2>"; }
	foreach ( $upcoming_events as $post ) {
        setup_postdata( $post );

        // This time, let's throw in an event-specific
        // template tag to show the date after the title!
        $info .= '<h4>' . $post->post_title . '</h4>';
        $event_date = get_post_meta( $post->ID, '_event_start_date', true );
        $info .= '<p>' . $event_date . '</p>';
        //$event_date = get_post_meta( $event_id, '_event_start_date', true );
	}
    
    // Query Events Manager [EM] posts
    // TODO: test this...
    $wp_args = array(
        'post_type'         => 'event',
        'posts_per_page'    => 5,
        'scope'   	        => 'past',
        'tax_query'         => array(
            array(
                'taxonomy' 	=> 'event-categories',
                'field' 	=> 'slug',
                'terms' 	=> 'webcasts'
            )
        )
    );

    $result = new WP_Query( $wp_args );
    $past_events = $result->posts;
	
	if (count($past_events) > 0) { $info .= "<h2>Past</h2>"; }
	foreach ( $past_events as $post ) {
        setup_postdata( $post );

        // This time, let's throw in an event-specific
        // template tag to show the date after the title!
        $info .= '<h3><a href="'. get_permalink($post->ID) . '">' . $post->post_title . '</a></h3>';
        $event_date = get_post_meta( $post->ID, '_event_start_date', true );
        $info .= '<p>' . $event_date . '</p>';
	}
	
	return $info;
}


/*** EM Events Manager Customizations ***/

// Function to modify default #_XXX placeholders
add_filter('em_event_output_placeholder','whx4_placeholders',1,3);
function whx4_placeholders( $replace, $EM_Event, $result ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = true;
    sdg_log( "divline2", $do_log );
    
    // Init vars
    $ts_info = "";
    if ($EM_Event instanceof EM_Event) {
    	$post_id = $EM_Event->post_id;
	} else {
		sdg_log( "EM_Event var NOT an instanceof EM_Event", $do_log );
		$post_id = null;
	}
    //$event_id = $EM_Event->ID;
    //$ts_info .= "[sdgp] EM  $result for post_id: $post_id<br />"; // breaks layout?
    //$ts_info .= "EM result: $result<br />";
    
    // Get the formatted event title
    if ( $result == '#_EVENTLINK' ) { $make_link = true; } else { $make_link = false; }
    // Make sure not to include any extra info -- this is for the mini-cal
    if ( $result == '#_EVENTNAMETXT' ) { $do_ts = false; $show_subtitle = false; $hlevel = null; $hlevel_sub = null; } else { $show_subtitle = true; $hlevel = null; $hlevel_sub = null; }
    //
    // Get the event title formatted using our special function
	$title_args = array( 'post' => $post_id, 'link' => $make_link, 'line_breaks' => false, 'show_subtitle' => $show_subtitle, 'echo' => false, 'hlevel' => $hlevel, 'hlevel_sub' => $hlevel_sub, 'called_by' => 'whx4_placeholders', 'do_ts' => $do_ts );
    if ( $result == '#_EVENTLINK' && !is_singular('event_series') ) { $title_args['show_series_title'] = "append"; }
    $event_title = sdg_post_title( $title_args );
    $ts_info .= "title_args: ".print_r($title_args,true)."<br />"; // breaks layout?
    
    if ( $result == '#_EVENT_LIST_ITEM' ) {
    
    	$replace = $EM_Event->output(get_option('dbem_event_list_item_format'));
    
    } else if ( $result == '#_EVENTNAME' ) {
    
    	$replace = $event_title;
    	//$ts_info .= " [_EVENTNAME] >> ".$event_title." << ";
    
    } else if ( $result == '#_EVENTNAMETXT' ) {
    
    	$replace = $event_title;
    
    } else if ( $result == '#_EVENTNAMESHORT' ) {
        
        // Get the short_title, if any
        if ( $post_id && $post_id != "" ) { 
            $short_title = get_post_meta( $post_id, 'short_title', true );
            // If a short_title is set, use it
            if ( $short_title ) { $event_title = $short_title; }
        }
        $replace = $event_title;
        
    } else if ( $result == '#_EVENTLINK' ) {
        
        $replace = $event_title;
        //$ts_info .= " [_EVENTLINK] >> ".$event_title." << ";
        
    } else if ( $result == '#_EDITEVENTLINK' ) {
        
        if ( $EM_Event->can_manage('edit_events','edit_others_events') ){
            $link = esc_url($EM_Event->get_edit_url());
            $link .= "&post_type=event";
            $replace = '<a href="'.$link.'">'.esc_html(sprintf(__('Edit Event','events-manager'))).'</a>';
        }
        
    } else if ( $result == '#_EVENTTIMES' || $result == '#_12HSTARTTIME' ) {
        
        // If #_EVENTTIMES and no end time, then use #_12HSTARTTIME
        if ( $result == '#_EVENTTIMES' ) {
        	//$start_time = $EM_Event->output('#_12HSTARTTIME');
        }
        
        // Format am/pm to add periods
        // TODO: make this a plugin option, or at least for now limit it per domain/site name, since it's just a weird STC preference
        //if ( str_contains($replace, "pm" ) ) { $replace .= "*"; }
        //$replace = str_replace('am','a.m.',$replace);
        //$replace = str_replace('pm','p.m.',$replace);
        $replace = str_replace(array('am','pm'),array('a.m.','p.m.'),$replace);
        
    } else if ( $result == '#_EVENTIMAGE' || $result == '#_EVENTIMAGE{250,250}' ) {
        
        // Modified version of default to actually show image & caption only under certain circumstances        
        $show_image = true;
        
        $featured_image_display = get_field('featured_image_display', $post_id);
        //$ts_info .= "[sdgp] featured_image_display: ".$featured_image_display."<br />";
        
        if ( !is_archive() && !is_page() ) {
        
        	//$ts_info .= "[sdgp] !is_archive() && !is_page()<br />";
        	
        	if ( $featured_image_display == "thumbnail" ) {
        	
        		$show_image = false;
        		//$ts_info .= "[sdgp] featured_image_display: $featured_image_display<br />";
        		
        	} else if ( is_singular('event') ) { //&& function_exists('post_is_webcast_eligible') && post_is_webcast_eligible( $post_id )
				
				//$ts_info .= "[sdgp] is_singular('event')<br />";
				$mp_args = array('post_id' => $post_id, 'status_only' => true, 'position' => 'above', 'media_type' => 'video' );
				$player_status = get_media_player( $mp_args );
				$ts_info .= "player_status: ".print_r($player_status, true)."<br />"; //$ts_info .= "player_status: ".$player_status."<br />";
				
				if ( $player_status == "ready" ) {
					$show_image = false;
					$ts_info .= "[sdgp] show video, not image<br />";
				} else {
					// If player_status is NOT ready, get some TS info -- tft					
					$mp_args = array('post_id' => $post_id, 'position' => 'above' );
					$media_info = get_media_player( $mp_args );
					//$media_info = get_media_player( $post_id, false, 'above' );
					$ts_info .= "[sdgp] media_player ts: ".$media_info['ts_info'];
				}
								
			}
        }
        
        if ( $show_image == true ) {
            
            //$ts_info .= "[sdgp] show_image is TRUE<br />";
            
            // Is there in fact an image? If not, try to find one some other way
            // TODO: generalize from STC to something more widely applicable
            if ( function_exists('sdg_post_thumbnail') ) { // empty($replace) && 
            	
            	//$ts_info .= "[sdgp] get image using sdg_post_thumbnail<br />";
            	
            	if ( is_singular('event') ) {
            		$img_size = "full";
            		$format = "singular";
            	} else {
            		$img_size = array( 250, 250);
            		$format = "excerpt";
            	}
            	// Get img via sdg_post_thumbnail fcn
            	$img_args = array( 'post_id' => $post_id, 'format' => $format, 'img_size' => $img_size, 'sources' => "all", 'echo' => false );
            	$img_tag = sdg_post_thumbnail ( $img_args );
            	
            	//if ( empty($img_tag) ) { $ts_info .= "img_tag is EMPTY! for post_id: $post_id: format: $format; img_size: $img_size; sources: all; echo: false<br />"; }
            	///if ( empty($img_tag) ) { $ts_info .= "[sdgp] img_tag is EMPTY! for img_args: ".print_r($img_args, true)."<br />"; } else { $ts_info .= "[sdgp] img_tag found<br />"; }
            	
            	//if ( !empty($img_tag) && $result == '#_EVENTIMAGE{250,250}' ) { $classes .= " float-left"; }
            	
            } else {
            
            	$img_tag = $replace;
            	
            }
            
            //$caption = sdg_featured_image_caption($EM_Event->ID);
            //if ( !empty($caption) && $caption != '<p class="zeromargin">&nbsp;</p>' ) { $classes .= " has_caption"; }
            
            $replace = $img_tag;
            //$replace = '<div class="'.$classes.'">'.$img_tag.'</div>';
            //$replace .= $caption;
            //$replace .= "[sdgp] sdg_placeholders<br />";
            
        } else {
        	
        	$ts_info .= "[sdgp] show_image is FALSE<br />";        	        	
            $replace = "<br />"; // If there's no featured image, add a line break to keep the spacing
            //$ts_info .= "[sdgp] sdg-calendar >> sdg_placeholders<br />";
            
        }
        
    } else if ( $result == '#_DAYTITLE' ) {
        
        //$replace = "day_title for".$EM_Event->start_date; // tft
        $atts = array('the_date'=>$EM_Event->start_date);
        $replace = get_day_title($atts);
        
    } else if( preg_match('/^#_DAYTITLE\{?/', $result) ){
        
        //$replace = "day_title for".$EM_Event->start_date; // tft
        $args = explode(',', preg_replace('/#_DAYTITLE\{(.+)\}/', '$1', $result));
        $atts = array('the_date'=>$EM_Event->start_date);
        $replace = get_day_title($atts);
        
    } else if ( $result == '#_BOOKINGINFO' ) {
    
    	$booking_form_display = get_field( 'booking_form_display', $post_id );
		$booking_type = get_field( 'booking_type', $post_id );
		$info = "";
		
		//$submit_button_text = get_field( 'booking_button_text', $post_id ); // dbem_bookings_submit_button
		$booking_button_text = get_field( 'booking_form_button_text', $post_id ); //
		
    	if ( $booking_type == "application" ) {
    		if ( !$booking_button_text ) { $booking_button_text = "Submit an Application"; }
    		//if ( !$submit_button_text ) { $submit_button_text = "Apply"; }    		
    		$header_text = "Application for <em>".$EM_Event->output('#_EVENTNAME')."</em>, ".$EM_Event->output('#_EVENTDATES');
    	} else {
    		if ( !$booking_button_text ) { $booking_button_text = "Register for this Event"; }
    		//if ( !$submit_button_text ) { $submit_button_text = "Submit Your Registration"; }
    		$header_text = "Registration for <em>".$EM_Event->output('#_EVENTNAME')."</em>, ".$EM_Event->output('#_EVENTDATES');
    	}
		
		$booking_form = $EM_Event->output('#_BOOKINGFORM');
		
		$info .= '<div class="single_event_registration">'; //  style="margin-top:1rem;"
		
		if ( $booking_form_display == "modal" ) {
			
			$info .= '<a href="#!" id="dialog_handle_'.$post_id.'" class="dialog_handle button">'.$booking_button_text.'</a>';
			$info .= '<div id="dialog_content_'.$post_id.'" class="dialog dialog_content booking_form">';
			$info .= '<h2 autofocus class="modal_header" style="text-transform: none;">'.$header_text.'</h2>';
			$info .= $booking_form;
			$info .= '</div>';
			
		} else {
		
			//$info .= '<h2 class="em_booking_header">'.$header_text.'</h2>';
			$info .= $booking_form;
			
		}
		
		$info .= '</div>'; // div class="single_event_registration"
		
    	$replace = $info;
    
    } else {
    
    	//$replace .= "result: ".print_r($result,true)."<br />";
    	
    }
    
    if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $replace .= '<div class="troubleshooting whx4_placeholders">'.$ts_info.'</div>'; }
    
    return $replace;
}


// Custom Conditional Placeholder(s)
/*
add_action('em_event_output_show_condition', 'sdg_custom_conditional_placeholders', 1, 4);
function sdg_custom_conditional_placeholders($show, $condition, $full_match, $EM_Event){
    
    if ( !empty( $EM_Event->styles ) && preg_match('/^is_category_(.+)$/',$condition, $matches) ){
        if( is_array($EM_Event->styles) && in_array($matches[1],$EM_Event->styles) ){
            $show = true;
        }
    }
    
    //if( !empty( $EM_Event->styles ) && preg_match('/^has_style_(.+)$/',$condition, $matches) ){
       // if( is_array($EM_Event->styles) && in_array($matches[1],$EM_Event->styles) ){
       //     $show = true;
       // }
    //}
    return $show;
}*/

// Custom category placeholder(s)
add_filter('em_category_output_placeholder','cat_em_placeholder_mod',1,3); // may cause issues w/ latest version of EM (6.x)
function cat_em_placeholder_mod($replace, $EM_Category, $result){
	
	if ( $result == '#_CATEGORYEVENTS') {
    	
    	// STC defaults
    	// TODO: build in options for other sites via plugin options page
    	if ( $EM_Category->slug == 'webcasts' ) {
    		//$replace = "This is the webcasts category...";
    		$replace = '<h2 class="em_events">Up Next</h2>';
    		$replace .= '<div class="sdg_em_events">'.$EM_Category->output("#_CATEGORYNEXTEVENT").'</div>';
    		$replace .= '<h2 class="em_events">Past Events</h2>';
    		$replace .= '<div class="sdg_em_events">'.$EM_Category->output("#_CATEGORYPASTEVENTS").'</div>';
    	} else {
    		$replace = ""; // reset
    		//$replace = "This is NOT the webcasts category... It is the '".$EM_Category->slug."' category.";
    		//$replace = '<h2 class="em_events">Upcoming Events</h2>';
    		$replace .= '<div class="sdg_em_events">'.$EM_Category->output("#_CATEGORYNEXTEVENTS").'</div>';
    	}		
    	
    } else if ( $result == "#_CATEGORYPASTEVENTS" ) {
    	
    	// Rebuild call to output function for past events so as to be able to set correct DESC order
    	$args = array( 'category' => $EM_Category->slug, 'scope'=> 'past', 'pagination'=>1, 'ajax'=>0 );
    	$args['format_header'] = get_option('dbem_category_event_list_item_header_format');
		$args['format_footer'] = get_option('dbem_category_event_list_item_footer_format');
		$args['format'] = get_option('dbem_category_event_list_item_format');
		$args['no_results_msg'] = get_option('dbem_category_no_events_message'); 
		$args['limit'] = get_option('dbem_category_event_list_limit');
		$args['orderby'] = get_option('dbem_category_event_list_orderby');
		$args['order'] = 'DESC';
		$args['page'] = (!empty($_REQUEST['pno']) && is_numeric($_REQUEST['pno']) )? $_REQUEST['pno'] : 1;
		/*if( $target == 'email' ){
			$args['pagination'] = 0;
			$args['page'] = 1;
		}*/
    	$replace = EM_Events::output($args);
    	
    }
    
    // Set order of display to reverse chronological for event category archives
	// https://wordpress.org/support/topic/set-event-ordering-for-_categorypastevents-placeholder/
	/*if ( $result == '#_CATEGORYPASTEVENTS' || $result == '#_CATEGORYNEXTEVENTS' ) {
        $args['tag'] = "-unlisted"; // exclude unlisted
        $args['format'] = get_option('dbem_category_event_list_item_format');
        $args['format_header'] = get_option('dbem_category_event_list_item_header_format');
        $args['format_footer'] = get_option('dbem_category_event_list_item_footer_format');
        $replace = EM_Events::output($args);
    }*/
    /*if ( $result == '#_CATEGORYPASTEVENTS' ) {
        $em_termID = $EM_Category->term_id;
        $args = array('category'=>$em_termID,'order'=>'DESC','scope'=>'past','pagination'=>1, 'limit'=>20);
        $args['format'] = get_option('dbem_category_event_list_item_format');
        $args['format_header'] = get_option('dbem_category_event_list_item_header_format');
        $args['format_footer'] = get_option('dbem_category_event_list_item_footer_format');
        $replace = EM_Events::output($args);
	}*/
	return $replace;
}

// Filter to force the mini-cal in the sidebar to match the month/year of the individual event [or archive scope? wip]
//add_filter( 'em_widget_calendar_get_args', 'whx4_custom_em_calendar_widget',1,3 ); // old version
//function whx4_custom_em_calendar_widget ( $instance ) {
add_filter( 'em_calendar_get_args', 'whx4_custom_em_calendar_widget',1,3 ); // new version, to work with snippets
function whx4_custom_em_calendar_widget ( $args ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
	
	sdg_log( "function called: whx4_custom_em_calendar_widget", $do_log );
	sdg_log( "args: ".print_r($args,true), $do_log);
	//sdg_log( "instance: ".print_r($instance,true)."");
	
	global $post;
	$post_id = get_the_ID();
	$post_type = get_post_type( $post_id );
    //sdg_log( "post_id: ".$post_id, $do_log );
    //sdg_log( "post_type: ".$post_type, $do_log );
	
	// The month/year args effect which dates are shown in the calendar grid and for which events are sought for linking
    if ( $post_type == 'event' ) {
    	sdg_log( "setting month/year args from event_start_date", $do_log );
    	$event_date = get_post_meta( $post_id, '_event_start_date', true );
        $date = explode('-', $event_date);
		$args['month'] = $date[1];
		$args['year'] = $date[0];
		//sdg_log( "set instance month/year to ".$date[1]."/".$date[0], $do_log );
    } else {
    	sdg_log( "Set the month/year args [wip]", $do_log );
    	//$args = whx4_custom_em_query_args ($args);
    	//$args['month'] = 1; // tft
		//$args['year'] = 2021; // tft
		/*
		if ( isset($args['scope']) ) {
			$scope = $args['scope'];
			sdg_log( "[secsc] args['scope']: ". print_r($args['scope'],true), $do_log );
		} else {
			$scope = null;
		}
		*/
    }
    if ( isset($args['month']) ) { sdg_log( "em_calendar_get_args -> args['month']: ".$args['month'], $do_log ); }
    if ( isset($args['year']) ) { sdg_log( "em_calendar_get_args -> args['year']: ".$args['year'], $do_log ); }
    //
    return $args;
}

// Functions to check for scope and category in query vars if not already set otherwise
add_filter( 'em_content_events_args', 'whx4_em_scope_via_query_arg' );
function whx4_em_scope_via_query_arg ( $args ) {

	// TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    if ( function_exists('devmode_active') ) { $do_log = devmode_active( array("whx4", "events") ); } else { $do_log = null; }
    sdg_log( "divline2", $do_log );
    sdg_log( "function called: whx4_em_scope_via_query_arg", $do_log );
    //if ( is_admin() ) { sdg_log( "is_admin", $do_log ); } else { sdg_log( "NOT is_admin", $do_log ); }
    
    // scope
    if ( isset($_REQUEST['scope']) ) { //!isset($args['scope']) && 
    	sdg_log( "request_scope is set: ".print_r($_REQUEST['scope'],true), $do_log );
    	$args['scope'] = $_REQUEST['scope'];
    	sdg_log( "scope set via query_var", $do_log );
    } else {
    	sdg_log( "request_scope is NOT set", $do_log );
    }
    
    return $args;
}
    
add_filter( 'em_object_build_sql_conditions_args', 'whx4_custom_em_query_args',10,1);
add_filter( 'em_content_events_args', 'whx4_custom_em_query_args' );
function whx4_custom_em_query_args ( $args ) {

	// TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = false;
    sdg_log( "divline2", $do_log );
    sdg_log( "function called: whx4_custom_em_query_args", $do_log );
    
    if ( is_admin() ) { sdg_log( "is_admin", $do_log ); } else { sdg_log( "NOT is_admin", $do_log ); }
    
    // scope
    /*if ( isset($_REQUEST['scope']) ) { //!isset($args['scope']) && 
    	sdg_log( "request_scope is set: ".print_r($_REQUEST['scope'],true), $do_log );
    	//$args['scope'] = $_REQUEST['scope'];
    	//sdg_log( "scope set via query_var", $do_log );
    } else {
    	sdg_log( "request_scope is NOT set", $do_log );
    }*/
    
    // category    
    if ( isset($_REQUEST['category']) ) { //!isset($args['category']) && 
    	$args['category'] = $_REQUEST['category'];
    	sdg_log( "category set via query_var", $do_log );
    }
    // Exclude unlisted events according to tag
    if ( isset($args['category']) ) {
    	sdg_log( "[cca] args['category']: ".$args['category'], $do_log );
    }
    
    // Exclude unlisted events
    $args['tag'] = "-unlisted"; // 3066 (stc-live)
    if ( !isset($args['category']) ) {
    	$args['category'] = "-special-notice";
    } else {
    	if ( !is_array($args['category']) && !empty($args['category']) ) {
			$args['category'] .= ", -special-notice";
		} else if ( is_array($args['category']) ) {
			$args['category'][] = "-special-notice";
		} else {
			$args['category'] = "-special-notice";
		}
    }
    if ( is_array($args['category']) ) {
    	sdg_log( "[cca] final args['category']: ".print_r($args['category'],true), $do_log );
    } else {
    	sdg_log( "[cca] final args['category']: ".$args['category'], $do_log );
    }
    //sdg_log( "EM args: ".print_r($args, true), $do_log );
    
    return $args;
}

add_filter( 'em_events_build_sql_conditions', 'whx4_em_custom_query_conditions',10,2);
function whx4_em_custom_query_conditions( $conditions, $args ){

	// TS/logging setup
	if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = true;
	sdg_log( "divline2", $do_log );
    sdg_log( "function called: whx4_em_custom_query_conditions", $do_log );
    
    // Init
    $start_date = null;
    $end_date = null;
    
    sdg_log( "[weqc] conditions: ".print_r($conditions,true), $do_log );
    //sdg_log( "[weqc] args: ".print_r($args,true), $do_log );
    
    // Scope
    if ( isset($conditions['scope']) ) { sdg_log( "[weqc] conditions['scope']: ".print_r($conditions['scope'],true), $do_log ); }
    if ( isset($args['scope']) ) { sdg_log( "[weqc] args['scope']: ".print_r($args['scope'],true), $do_log ); }
    
    // Category
    //if ( isset($conditions['category']) ) { sdg_log( "[weqc] conditions['category']: ". print_r($conditions['category'],true), $do_log ); }
    
    // WIP: resolve interference with em-calendar functions in case of shortcode/snippet -- figure out how to check context for query
    if ( !isset($args['context']) || $args['context'] != "snippet" ) {
    	sdg_log( "[weqc] args['context'] NOT set", $do_log);
    	sdg_log( "[weqc] (check query vars)", $do_log );
    	$args = whx4_custom_em_query_args ($args);
    } else {
    	sdg_log( "[weqc] args['context']: ".$args['context'], $do_log );
    }
    
    if ( isset($args['scope']) ) {
    	$scope = $args['scope'];
    	sdg_log( "[weqc] args['scope']: ".print_r($args['scope'],true), $do_log );
    } else {
    	//$scope = null;
    }
    /*if ( isset($args['category']) ) {
    	$category = $args['category'];
    	sdg_log( "[secsc] args['category']: ".$category, $do_log );
    	$conditions['category'] = $category;
    }*/
    
    //if( is_admin() ) { sdg_log( "is_admin", $do_log ); } else { sdg_log( "NOT is_admin", $do_log ); }
	 
    // If this is the main admin events page...
    //sdg_log( "args: ". print_r($args, true), $do_log );
    //https://dev.saintthomaschurch.org/wp-admin/edit.php?s&post_status=all&post_type=event
        
	// TODO: figure out how to eliminate redundancy of array declaration w/ sdg_em_scopes
	$my_scopes = array( 'today-onward', 'upcoming', 'this-week', 'this-season', 'next-season', 'this-year', 'next-year');
	
	if ( in_array($scope, $my_scopes) ) {		
		
		sdg_log("[weqc] ".$scope." is a CUSTOM scope.", $do_log);
		$arr_dates = whx4_em_custom_scopes($scope);
	
		if ( $arr_dates) {
			$start_date = $arr_dates['start'];
			$end_date 	= $arr_dates['end'];
		}
		
	} else if ( $scope && !is_array($scope) ) {
	
		sdg_log("[weqc] ".$scope." is a STANDARD scope.", $do_log);
		
		// Is this a date or date pair? i.e. a string containing commas or hyphens?
		if ( strpos($scope,",") || strpos($scope,"-") ) {
		
			$scope_dates = explode(",",$scope);
			$start_date = $scope_dates[0];
			if ( count($scope_dates) > 1 ) {
				$end_date = $scope_dates[1];
			}
			
		} else {
		
			// Not a date or date pair? Check standard scope date ranges
			$ranges = whx4_em_get_range_dates();
			//sdg_log( "[weqc] ranges: ". print_r($ranges,true), $do_log );
			
			if ( $ranges && isset($ranges[$scope]) ) {
				
				sdg_log( "[weqc] ranges[$scope]: ". print_r($ranges[$scope],true), $do_log );
				
				if ( is_array($ranges[$scope]) ) {
					$start_date = $ranges[$scope][0];
					if ( $ranges[$scope][1] ) {
						$end_date = $ranges[$scope][1];
					}
				} else {
					$start_date = $end_date = $ranges[$scope];
				}
				
				sdg_log("[weqc] start_date: ".$start_date, $do_log);
				sdg_log("[weqc] end_date: ".$end_date, $do_log);
				
			} else {
				//sdg_log( "[weqc] ranges: ". print_r($ranges,true), $do_log );
			}
		
		}
		
	}
	
	// If start_date is set, but not end date, then set end_date same as start
	if ( !empty($start_date) && empty($end_date) ) {
		$end_date = $start_date;
	}
	
	// If both start and end dates are properly set, then query the scope accordingly
	if ( !empty($start_date) && !empty($end_date) ) {
		sdg_log("[weqc] Resetting conditions scope.", $do_log);
		$conditions['scope'] = " (event_start_date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE))";
	}
    
    if ( isset($conditions['scope']) ) { sdg_log( "final conditions['scope']: ".$conditions['scope'], $do_log ); }
    
    //return $args;
    return $conditions;
}


// TODO: combine this with above (whx4_em_custom_query_conditions filter function)?
add_filter( 'em_events_build_sql_conditions', 'whx4_custom_event_search_build_sql_conditions',1,2);
function whx4_custom_event_search_build_sql_conditions ($conditions, $args) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = true;
    sdg_log( "divline2", $do_log );
    sdg_log( "function called: whx4_custom_event_search_build_sql_conditions", $do_log );
    
    //sdg_log( "[sdg_custom_event_search...] conditions: ".print_r($conditions, true), $do_log );
    //sdg_log( "[sdg_custom_event_search...] args: ".print_r($args, true), $do_log );
    
    global $wpdb;
    
    if( !empty($args['series']) && is_numeric($args['series']) ){
        
        sdg_log( "[sdg_custom_event_search...] series is set and valid: ".$args['series'], $do_log );
        $meta_value = '%"'.$args['series'].'"%';
        $sql = $wpdb->prepare(
            "SELECT `event_id` FROM ".EM_EVENTS_TABLE.", `wpstc_postmeta` WHERE `meta_value` LIKE %s AND `meta_key`='series_events' AND ".EM_EVENTS_TABLE.".`post_id` = `wpstc_postmeta`.`post_id`", $meta_value
        ); // 
        //$sql = $wpdb->prepare("SELECT post_id FROM `wpstc_postmeta` WHERE meta_value=%s AND meta_key='event_series'", $args['event_series']);
        //$sql = $wpdb->prepare("SELECT object_id FROM ".EM_META_TABLE." WHERE meta_value=%s AND meta_key='event_series'", $args['event_series']);
        $conditions['series'] = "event_id IN ($sql)";
        
    }
    
    // The following seems to effect only front-end display. Look into affecting back-end display, also.
    if( !empty($args['scope']) ) {
		
        sdg_log( "[whx4_custom_event_search...] scope: ".print_r( $args['scope'],true ), $do_log );
        
		$scope = $args['scope'];
		$arr_dates = whx4_em_custom_scopes($scope);
		
		if ( $arr_dates) {
			$start_date = $arr_dates['start'];
			$end_date 	= $arr_dates['end'];
			if ( !empty($start_date) && !empty($end_date) ) {
				$conditions['scope'] = " (event_start_date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE)) OR (event_end_date BETWEEN CAST('$end_date' AS DATE) AND CAST('$start_date' AS DATE))";
			}
		} else {
			//$conditions['scope'] = $scope;
		}
		
	}
    
    //sdg_log( "[sdg_custom_event_search...] modified conditions: ".print_r($conditions, true), $do_log );
    
    return $conditions;
}
/*
// Get the scope from the REQUEST array, if any.
if ( isset($_REQUEST['scope']) ) {
	$request_scope = $_REQUEST['scope'];
    $ts_info .= "REQUEST scope: ".print_r($_REQUEST['scope'], true)."<br />";
    if ( is_array($_REQUEST['scope']) && isset($_REQUEST['scope'][0]) && $_REQUEST['scope'][0] ) {
        $date_selected = $_REQUEST['scope'][0]; // start date of scope
        $ts_info .= ">> date_selected via array _REQUEST['scope']: [".print_r($_REQUEST['scope'],true)."]<br />";
    } else {
    	$ts_info .= ">> cannot set date_selected from _REQUEST['scope']<br />";
    }
}
*/

// WIP -- alt approach is via custom code in events-manager/classes/em-events.php -- search for "atc" (v 3.2.1...)
//add_filter('em_events_output_grouped_args','em_args_mod',1,3);
function em_args_mod($args){
    
    /*
    $header_str = apply_shortcodes( str_replace('#s', $EM_DateTime->modify($date)->i18n($format), $args['header_format']) ); // atc
	echo $header_str;
	*/

    // WIP because day_title shortcode isn't working in context of filtered events_list_grouped
    if ( isset($args['format_header']) ) { // && ! is_page('events')
        //$args['format_header'] = apply_shortcodes( $args['format_header'] );
        //$args['format_header'] = "***".$args['format_header']."***"; // tft
        $args['format_header'] .= "[fh]";
    }
    
    if ( isset($args['header_format']) ) { // && ! is_page('events')
              
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', 'TBD: day_title<br />', $args['header_format']); // ok for testing
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', do_shortcode('[day_title the_date="2020-11-22"]'), $args['header_format']); // tft -- ok -- but not very useful
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', "do_shortcode('[day_title the_date=\"#s\"]')", $args['header_format']); // nope
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', do_shortcode('[day_title the_date="#s"]'), $args['header_format']); // almost -- but shortcode can't get actual val of #s
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', '#_DAYTITLE{#s}', $args['header_format']); // nope -- just outputs placeholder as string with translated date  
        
        //$header_format = "do_shortcode('[day_title the_date=\"#s\"]')";
        //$args['header_format'] = str_replace('[day_title the_date="#s"]', $header_format, $args['header_format']); // ??
        
        //$args['header_format'] = apply_shortcodes( $args['header_format'] );
        
        //// For now, just hide the day_title shortcode -- can't get it to run except on main calendar page
        
        $args['header_format'] .= "[hf]"; // tft
        
	}
    //sdg_log( "em_events_output_grouped_args: ".print_r($args, true), $do_log );
	return $args;
}


// Create custom scopes: "Upcoming", "This Week", "This Season", "Next Season", "This Year", "Next Year"
// TODO: refashion after get_ranges fcn?
function whx4_em_custom_scopes( $scope = null ) {
    
    // TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = false;
    sdg_log( "divline2", $do_log );
	
	if ( empty($scope) ) {
		return null;
	}
	
	// Init vars
	$dates = array();
	$start_date = null;
	$end_date = null;
	
    // get info about today's date
    $today = time(); //$today = new DateTime();
	$year = date_i18n('Y');
	
	// Define basic season parameters
	$season_start = strtotime("September 1st");
	$season_end = strtotime("July 1st");
	
	if ( $scope == 'today-onward' ){
        
        $start_date = date_i18n("Y-m-d"); // today
        $decade = strtotime($start_date." +10 years");
        $end_date = date_i18n("Y-m-d",$decade);
    
    } else if ( $scope == 'upcoming' ) {
    
    	// Get start/end dates of today plus six
        
        $start_date = date_i18n("Y-m-d"); // today
        $seventh_day = strtotime($start_date." +6 days");
        $end_date = date_i18n("Y-m-d",$seventh_day);
    
    } else if ( $scope == 'this-week' ) {
    
    	// Get start/end dates for the current week
        
        $sunday = strtotime("last sunday");
        $sunday = date_i18n('w', $sunday)==date('w') ? $sunday+7*86400 : $sunday;
        $saturday = strtotime(date("Y-m-d",$sunday)." +6 days");
        $start_date = date_i18n("Y-m-d",$sunday);
        $end_date = date_i18n("Y-m-d",$saturday);
    
    } else if ( $scope == 'this-season' ) {
    
    	// Get actual season start/end dates
		if ($today < $season_start){
			$season_start = strtotime("-1 Year", $season_start);
		} else {
			$season_end = strtotime("+1 Year", $season_end);
		}
		
		$start_date = date_i18n('Y-m-d',$season_start);
		$end_date = date_i18n('Y-m-d',$season_end);
    
    } else if ( $scope == 'next-season' ) {
    
		// Get actual season start/end dates
		if ($today > $season_start){
			$season_start = strtotime("+1 Year", $season_start);
			$season_end = strtotime("+2 Year", $season_end);
		} else {
			$season_end = strtotime("+1 Year", $season_end);
		}
		
		$start_date = date_i18n('Y-m-d',$season_start);
		$end_date = date_i18n('Y-m-d',$season_end);
    
    } else if ( $scope == 'this-year' ) {
    
    	$start = strtotime("January 1st, {$year}");
    	$end = strtotime("December 31st, {$year}");
		
		$start_date = date_i18n('Y-m-d',$start);
		$end_date = date_i18n('Y-m-d',$end);
    
    } else if ( $scope == 'next-year' ) {
    
    	$year = $year+1;
    	$start = strtotime("January 1st, {$year}");
    	$end = strtotime("December 31st, {$year}");
		
		$start_date = date_i18n('Y-m-d',$start);
		$end_date = date_i18n('Y-m-d',$end);
    
    } else {
    
    	// WIP
    	// See if this is a non-custom, standard EM scope
    	/*$ranges = get_range_dates(); // nope -- DN work in this context
    	if ( $ranges ) {
    		if ( isset($ranges[$scope]) ) {
    			$start = $ranges[$scope][0];
    			$start_date = date_i18n('Y-m-d',$start);
    			if ( $ranges[$scope][1] ) {
    				$end = $ranges[$scope][1];
    				$end_date = date_i18n('Y-m-d',$end);
    			}
    		}
    	}*/
    	    	
    }
	
	$dates['start'] = $start_date;
	$dates['end'] 	= $end_date;
	
	return $dates;
	
}
// SEE ..._build_sql_conditions

// Version of: public static function get_range_dates()
function whx4_em_get_range_dates(){

	// TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; } 
    $do_log = true;
    sdg_log( "divline2", $do_log );
    sdg_log( "function called: whx4_em_get_range_dates", $do_log );
    
    $today = date('Y-m-d');
    $day = 0;
    
	$ranges = array(
		'all' => array('1970-01-01', $today),
		'future' => array(date_i18n("Y-m-d"),date_i18n("Y-m-d",strtotime("+10 years"))), //onward
		'today'=> $today,
		'yesterday'=> date('Y-m-d', strtotime('yesterday')),
		'this month'=> array( date('Y-m-01'), $today ),
		'last month'=> array( date('Y-m-01', strtotime('last month')), date('Y-m-t', strtotime('last month')) ),
		'3 months'=> array( date('Y-m-d', strtotime('-3 months')+$day), $today ),
		'6 months'=> array( date('Y-m-d', strtotime('-6 months')+$day), $today ),
		'12 months'=> array( date('Y-m-d', strtotime('-12 months')+$day), $today ),
		'this year'=> array( date('Y-01-01'), $today ),
		'last year'=> array( date('Y-01-01', strtotime('last year')), date('Y-12-31', strtotime('last year')) ),
	);
	
	return $ranges;
	
}
	
// Convert custom scopes to array to allow for proper filtered results to display in Admin
/*add_filter( 'em_events_build_sql_conditions', 'my_em_scope_conditions',1,2);
function my_em_scope_conditions($conditions, $args){
    if( !empty($args['scope']) && $args['scope']=='today-tomorrow' ){
        $start_date = date('Y-m-d',current_time('timestamp'));
        $end_date = date('Y-m-d',strtotime("+1 day", current_time('timestamp')));
        $conditions['scope'] = " (event_start_date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE)) OR (event_end_date BETWEEN CAST('$end_date' AS DATE) AND CAST('$start_date' AS DATE))";
    }
    return $conditions;
}*/


// Register custom scopes
// TODO: figure out why this isn't working. New scopes show up in EM dropdown in CMS, but don't have any effect
add_filter( 'em_get_scopes', 'whx4_em_scopes', 10, 1);
function whx4_em_scopes($scopes){
    $my_scopes = array(
		'today-onward' => 'Today Onward',
		'upcoming' => 'Upcoming',
		'this-week' => 'This Week',
        //'next-month' => __('Events next month','events-manager'),
        'this-season' => 'This Season',
		//'this-season' => __('Events this season','events-manager'),
        'next-season' => 'Next Season',
        'this-year' => 'This Year',
        'next-year' => 'Next Year'
    );
	//$scopes = array_merge($scopes, $my_scopes);
    return $scopes + $my_scopes;
	//return $scopes;
	
}

/*
 * This snippet makes recurring events public 
 * eg. allow custom sidebars "Default Sidebar" metabox to appear when creating recurring event
 */
add_filter('em_cp_event_recurring_public','__return_true');


/***** wip *****/

// Event archives -- top-of-page content

// Special Date Content
function get_special_date_content( $the_date = null ) {
	
	// TS/logging setup
    if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
    $do_log = false;
    sdg_log( "divline2", $do_log );
    
	$info = "";
	$ts_info = "";
	$content = "";
	
	if ( empty($the_date) ) { return null; }
	
	if ( !preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $the_date) ) {
		$the_date = date_i18n('Y-m-d', strtotime($the_date) ); // format the date, as needed
	}
	
	/*
	$ts_info = "get_special_date_content<br />";
    $ts_info .= "the_date: '$the_date'<br />";
    $ts_info .= "print_r the_date: '".print_r($the_date, true)."'<br />"; // tft
    */
    
    // NB: set event record to "All day" and assign 'special-notice' event category
    
    // Build query args
    $wp_args = array(
        'posts_per_page'=> 1, // get one event only
        'post_type'		=> 'event',
        'meta_query'	=> array(
            array(
                'key'     => '_event_start_date',
                'value'   => $the_date,
            )
        ),
        'tax_query'	=> array(
            array(
                'taxonomy' => 'event-categories',
                'field'    => 'slug',
                'terms'    => 'special-notice',
            )
        ),
    );
    
    $query = new WP_Query( $wp_args );
    $posts = $query->posts;
    
    if ( $posts ) {
    	
    	$timestamp = strtotime($the_date);
        $fixed_date_str = date("F d", $timestamp ); // day w/ leading zeros
        $ts_info .= "timestamp: '$timestamp'<br />";
        
        foreach ( $posts as $post ) {
    		//$info .= "<pre>".print_r($post, true)."</pre>"; // tft
    		$post_id = $post->ID;
    		if ( $post_id ) {
    			$notice_text = $post->post_content;
    			$content .= $notice_text;
    		}
    	}
    	
    	$classes = "message centered special-notice";
    	if ( str_word_count($content) < 75 ) { $classes .= " scalloped"; }
    	$ts_info .= str_word_count($content)." words";
    	
    	// TS issue w/ paragraphs
    	$content = wpautop($content);
    	
        $info .= '<div class="'.$classes.'">';
    	$info .= $content;
        $info .= '</div>';
        
    } else {
    
    	$ts_info .= "No posts found by fcn get_special_date_content for date $the_date<br />";
    	
    }
    
    if ( $ts_info != "" && ( $do_ts === true || $do_ts == "events" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
    	
	return $info;
	
}

// Add "series" to acceptable EM search parameters (attributes)
add_filter('em_events_get_default_search','whx4_custom_event_search_parameters',1,2);
add_filter('em_calendar_get_default_search','whx4_custom_event_search_parameters',1,2);
function whx4_custom_event_search_parameters($args, $array){
    
    $args['series'] = false; // registers 'series' (ID) as an acceptable value, set to false by default
    if ( !empty($array['series']) && is_numeric($array['series']) ) {
        $args['series'] = $array['series'];
    }
    //
    $args['context'] = false; // registers 'context' as an acceptable value, set to false by default (for snippet e.g.)
    if ( !empty($array['context']) ) {
        $args['context'] = $array['context'];
    }
    return $args;
    
}

// Program/Event info via Event CPT & ACF -- for Admin use/Troubleshooting
add_shortcode('display_event_stats', 'display_event_stats');
function display_event_stats( $atts = array() ) {
	
	if ( function_exists('devmode_active') ) { $do_ts = devmode_active( array("whx4", "events") ); } else { $do_ts = null; }
	$info = ""; // init
    
    // Extract args
	$args = shortcode_atts( array(
		'post_id'	=> get_the_ID(),
    ), $atts );
	extract( $args );
    
    $info .= 'ID: <span class="nb">'.$post_id.'</span>; ';
	$post   = get_post( $post_id );
    $post_meta = get_post_meta( $post_id );
    
    $recurrence_id = get_post_meta( $post_id, '_recurrence_id', true );
    if ( $recurrence_id ) { $info .= 'RID: <span class="nb">'.$recurrence_id.'</span>; '; }
    
    $parent_id = $post->post_parent;
    if ( $parent_id ) { $info .= 'parent_id: <span class="nb">'.$parent_id.'</span>; '; }
    
	// Get the personnel & program_items repeater field values (ACF)
	$personnel = get_field('personnel', $post_id);
    if ( is_array($personnel) && count($personnel) > 0 ) { $info .= '<span class="nb">'.count($personnel).'</span>'." pers.; "; }
	
	$program_items = get_field('program_items', $post_id);
    if ( is_array($program_items) && count($program_items) > 0 ) { $info .= '<span class="nb">'.count($program_items).'</span>'." prog.; "; }
	
	//Variable: Additional characters which will be considered as a 'word'
	$char_list = ""; /** MODIFY IF YOU LIKE.  Add characters inside the single quotes. **/
	//$char_list = '0123456789'; /** If you want to count numbers as 'words' **/
	//$char_list = '&@'; /** If you want count certain symbols as 'words' **/
	$word_count = str_word_count(strip_tags($post->post_content), 0, $char_list);
	$info .= '[<span class="nb">'.$word_count.'</span> words]';
    
    //$info .= "<pre>".print_r($post,true)."</pre>";
    //$info .= "<pre>".print_r($post_meta,true)."</pre>";    
    //$info .= "Delete"; // add delete link...
    
    $info = '<span class="troubleshooting inline">'.$info.'</span>';
    
	if ( $do_ts === true ) { return $info; } else { return null; }
}

// Tidier slugs for recurring event instances
/*
function append_slug($data) {
    global $post_ID;

    //if (empty($data['post_name'])) {
    if (!empty($data['post_name']) && $data['post_status'] == "publish" && $data['post_type'] == "post") {
    
        if( !is_numeric(substr($data['post_name'], -4)) ) {
              $random = rand(1111,9999);
              $data['post_name'] = sanitize_title($data['post_title'], $post_ID);
              $data['post_name'] .= '-' . $random;
          }
          
        $data['post_name'] = sanitize_title($data['post_title'], $post_ID);
        $data['post_name'] .= '-' . generate_arbitrary_number_here();
    }

    return $data;
}

add_filter('wp_insert_post_data', 'append_slug', 10); 
*/

?>