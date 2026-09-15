<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin file, not much I can do when called directly.';
	exit;
}

/*********** CPT: GROUP ***********/

// TODO: consider folding this in to the display-content plugin as a special content structure (group/subgroup)
// AND generalize it so as to be able to use it for links and other content types...
// Display the titles and personnel for a given subgroup or groups
function display_group_personnel ( $args = array() )
{
	$logCtx = ['whx4', 'people'];

	// Init vars
	$info = "";
	
	// Defaults
	$defaults = array(
		'group_id'		=> null,
		'subgroup_ids'	=> array(),
		'display_format' => 'links', // other options: list; excerpts; archive (full post content); grid; table
		//
        'show_content' => 'full', // wip -- options to include 'full', 'excerpts', 'none?
		//
		// TODO/WIP: add display options -- e.g. list, table, &c. -- OR -- do this via display_content functions...
		// For table display_format -- WIP
        'fields'  => null, // ***
        'headers'  => null, // ***
        //
	);

	// Parse & Extract args
	$args = wp_parse_args( $args, $defaults );
	extract( $args );	
	wxc_log("args", $args, $logCtx);
	
	// Get args from array
	if ( $group_id ) {
		wxc_log("display_format: $display_format", null, $logCtx);
		wxc_log("group_id: $group_id", null, $logCtx);
		wxc_log("fields", $fields, $logCtx);
		
    	$subgroups = get_field('subgroups', $group_id); // ACF collection item repeater field values
		
		if ( $subgroup_ids ) {
		    wxc_log("subgroup_ids", $subgroup_ids, $logCtx);
		}
		
		foreach ( $subgroups as $i => $subgroup ) {
		    wxc_log("i: $i", null, $logCtx);
			
			// NB: subgroup_ids are passed starting with "1" instead of zero
			if ( $subgroup_ids && !in_array($i+1, $subgroup_ids) ) {
				continue; // don't show this subgroup; continue on to the next in the array
			}
			
			//$subgroup_id = $subgroups[$subgroup_id];
			$subgroup_name = $subgroup['name'];
			$subgroup_personnel = $subgroup['personnel'];			
			$subgroup_info = ""; // init
			//
			
			// WIP
			foreach ( $subgroup_personnel as $group_person ) {			
				//$info .= "group_person: <pre>".print_r($group_person, true)."</pre>";
				$title_id = $group_person['title'];
				$title_term = get_term($title_id);
				if ( $title_term ) {				
					$group_title = $title_term->name;
					$group_title = '<span class="group_title">'.$group_title.'</span>'; // WIP/TBD
					
					// Get all persons matching this group_id and title_id which are current
					//...
					// TODO: would it be better to do this via a bidirectional field along the lines of repertoire_events rather than trying to query ACF repeater rows?
					//...
					
					$wp_args = array(
						'post_type'   => 'person',
						'post_status' => 'publish',
						//'posts_per_page' => 1,
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key'		=> "titles_XYZ_group", // name of custom field, with XYZ as a wildcard placeholder (must do this to avoid hashing)
								//'compare' 	=> 'LIKE',
								//'value' 	=> '"' . $group_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
								'value' 	=> $group_id,
							),
							array(
								'key'		=> "titles_XYZ_title", // name of custom field, with XYZ as a wildcard placeholder (must do this to avoid hashing)
								//'compare' 	=> 'LIKE',
								//'value' 	=> '"' . $title_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
								'value' 	=> $title_id,
							),
						),
						'fields' => 'ids',
					);
	
					$query = new WP_Query( $wp_args );
					$persons = $query->posts;
					wxc_log("wp_args", $wp_args, $logCtx);
					wxc_log("persons", $persons, $logCtx);
					//wxc_log("Last SQL-Query (query)", $query->request, $logCtx);
					
					// WIP -- this needs work -- if there's only one person, append the group_title to the item_title? if multiple, then -- ???
					//if ( $persons ) { $subgroup_info .= $group_title.": "; }
					
					// If the display-content plugin is active, then use its functionality to display the subgroup personnel
					// ??? this is more than we need -- instead just use the build_item_arr and display_post_item fcns?
					// WIP!
					if ( function_exists( 'birdhive_display_collection' ) ) { // TBD: check instead if plugin_exists display-content?
						foreach ( $persons as $person_id ) {
							
							// Assemble the array of styling parameters
							$arr_styling = array( 'item_type' => 'post', 'display_format' => $display_format, 'show_content' => $show_content ); // wip
							
							$item_title = get_the_title( $person_id ).", ".$group_title;
							$item = array( 'post_id' => $person_id, 'item_title' => $item_title );
							
							// Assemble the arr_item
							$arr_item = build_item_arr ( $item, $arr_styling );
							
							$subgroup_info .= display_item( $arr_item, $arr_styling );
							$subgroup_info .= "<br />";
						}
					} else {
						foreach ( $persons as $person_id ) {
							$person_name = get_the_title($person_id);
							$subgroup_info .= $person_name."<br />";
						}
					}					
				}
			}
			
			if ( !empty($subgroup_info) ) {
				//$info .= $subgroup_name."<br />"; // TBD
				$info .= $subgroup_info;
			}
		}    	
	} else {
	    wxc_log("No group_id set.", null, $logCtx);
	}
	
	// Return info for display
	return $info;
	
} // END function display_group_personnel ( $args = array() ) 


add_shortcode('group_personnel', 'whx4_group_personnel');
function whx4_group_personnel ( $atts = array() )
{
    $logCtx = ['whx4', 'people'];	
	$info = "";
	
	$args = shortcode_atts( array(
        'id' => null,
        'subgroup_ids' => array(),
		'display_format' => 'links', // other options: list; excerpts; archive (full post content); grid; table
    ), $atts );
    
    // Extract
	extract( $args );
    
	// Turn the list of subgroup_ids (if any) into a proper array
	if ( $subgroup_ids ) { $subgroup_ids = array_map( 'intval', birdhive_att_explode( $subgroup_ids ) ); }
    
    $info .= display_group_personnel( array('group_id' => $id, 'subgroup_ids' => $subgroup_ids, 'display_format' => $display_format ) );
    
    return $info;    
}
