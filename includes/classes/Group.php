<?php

namespace atc\WHx4;

class Group {

	/*public function __construct() {
        add_shortcode('shortcode_name', array($this, 'shortcode'));
    }
     
    public function shortcode() {
        // Contents of this function will execute when the blogger 
        // uses the [shortcode_name] shortcode. 
    }*/

	/*
	//public $event_id;
	public $post_id;
	public $post_parent;
	public $post_slug;
	public $post_owner;
	public $post_name;
	public $post_content;
	protected $last_name;
	protected $first_name;
	*/

	// TODO: consider folding this in to the display-content plugin as a special content structure (group/subgroup)
	// AND generalize it so as to be able to use it for links and other content types...
	// Display the titles and personnel for a given subgroup or groups
	protected function display_group_personnel ( $args = array() ) {
	
		// TS/logging setup
		$do_ts = devmode_active( array("whx4", "people") );
		$do_log = false;
		sdg_log( "divline2", $do_log );
	
		// Init vars
		$info = "";
		$ts_info = "";
		
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
		
		$ts_info .= "args: <pre>".print_r($args, true)."</pre>";
		
		// Get args from array
		if ( $group_id ) {
			
			$ts_info .= "display_format: $display_format<br />";
			$ts_info .= "group_id: $group_id<br />";
			$ts_info .= "fields: ".print_r($fields, true)."<br />";
			
			$subgroups = get_field('subgroups', $group_id); // ACF collection item repeater field values
			
			if ( $subgroup_ids ) {
				$ts_info .= "subgroup_ids: <pre>".print_r($subgroup_ids, true)."</pre>";
				//$ts_info .= "subgroup_id: $subgroup_id<br />";
			}
			
			foreach ( $subgroups as $i => $subgroup ) {
			
				$ts_info .= "i: $i<br />";
				
				// NB: subgroup_ids are passed starting with "1" instead of zero
				if ( $subgroup_ids && !in_array($i+1, $subgroup_ids) ) {
					continue; // don't show this subgroup; continue on to the next in the array
				}
				
				//$subgroup_id = $subgroups[$subgroup_id];
				$subgroup_name = $subgroup['name'];
				$subgroup_personnel = $subgroup['personnel'];			
				$subgroup_info = ""; // init
				//
				//$info .= "[$i] ".$subgroup_name."<br />";
				
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
						
						$ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
						$ts_info .= "persons: <pre>".print_r($persons, true)."</pre>";
						//$ts_info .= "Last SQL-Query (query): <pre>{$query->request}</pre>";
						
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
		
			$ts_info .= "No group_id set<br />";
			
		}
		
		if ( $do_ts === true || $do_ts == "whx4" ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
		
		// Return info for display
		return $info;
		
	} // END function display_group_personnel ( $args = array() ) 
	
	
	add_shortcode('group_personnel', 'whx4_group_personnel');
	protected function whx4_group_personnel ( $atts = array() ) {
	
		// TS/logging setup
		$do_ts = devmode_active( array("whx4", "people") );
		
		$info = "";
		$ts_info = "";
		
		$args = shortcode_atts( array(
			'id' => null,
			'subgroup_ids' => array(),
			'display_format' => 'links', // other options: list; excerpts; archive (full post content); grid; table
		), $atts );
		
		// Extract
		extract( $args );
		
		// Turn the list of subgroup_ids (if any) into a proper array
		//if ( $subgroup_ids ) { $subgroup_ids = birdhive_att_explode( $subgroup_ids ); }
		if ( $subgroup_ids ) { $subgroup_ids = array_map( 'intval', birdhive_att_explode( $subgroup_ids ) ); }
		
		$info .= display_group_personnel( array('group_id' => $id, 'subgroup_ids' => $subgroup_ids, 'display_format' => $display_format ) );
		
		if ( $do_ts === true || $do_ts == "whx4" ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
		
		return $info;
		
	}
	
	}

?>