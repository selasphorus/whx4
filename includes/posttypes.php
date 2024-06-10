<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}


// Get plugin options to determine which modules are active
$options = get_option( 'whx4_settings' );
if ( get_field('whx4_active_modules', 'option') ) { $active_modules = get_field('whx4_active_modules', 'option'); } else { $active_modules = array(); }
//if ( isset($options['whx4_active_modules']) ) { $active_modules = $options['whx4_active_modules']; } else { $active_modules = array(); }

if ( !function_exists( 'custom_caps' ) ) {
	function custom_caps() {
		$use_custom_caps = false;
		if ( isset($options['use_custom_caps']) && !empty($options['use_custom_caps']) ) {
			$use_custom_caps = true;
		}
		return $use_custom_caps;
	}
}

// TODO: change "person" to "individual", to better include plants and animals? w/ ACF field groups based on category/species
if ( in_array('people', $active_modules ) ) { // && !post_type_exists('person')
	// Person
	function register_post_type_person() {

		//if ( custom_caps() ) { $caps = array('person', 'people'); } else { $caps = "post"; }
		if ( custom_caps() ) { $caps = "person"; } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'People', 'whx4' ),
			'singular_name' => __( 'Person', 'whx4' ),
			'add_new' => __( 'New Person', 'whx4' ),
			'add_new_item' => __( 'Add New Person', 'whx4' ),
			'edit_item' => __( 'Edit Person', 'whx4' ),
			'new_item' => __( 'New Person', 'whx4' ),
			'view_item' => __( 'View Person', 'whx4' ),
			'search_items' => __( 'Search People', 'whx4' ),
			'not_found' =>  __( 'No People Found', 'whx4' ),
			'not_found_in_trash' => __( 'No People found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'show_in_menu'     	=> true,
			'query_var'        	=> true,
			'rewrite'			=> array( 'slug' => 'people' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			'menu_icon'			=> 'dashicons-groups',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ),
			'taxonomies'		=> array( 'person_category', 'person_title', 'admin_tag' ), //, 'person_tag', 'people_category'
			'show_in_rest'		=> false, // false = use classic, not block editor
			'delete_with_user' 	=> false,
		);

		register_post_type( 'person', $args );
	
	}
	add_action( 'init', 'register_post_type_person' );
	
}

// TODO: Figure out how to allow for individual sites to customize labels -- e.g. "Ensembles" for STC(?)
//if ( in_array('groups', $active_modules ) ) {
if ( in_array('people', $active_modules ) ) {
	// Group
	function register_post_type_group() {

		if ( custom_caps() ) { $caps = "group"; } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'Groups', 'whx4' ),
			'singular_name' => __( 'Group', 'whx4' ),
			'add_new' => __( 'New Group', 'whx4' ),
			'add_new_item' => __( 'Add New Group', 'whx4' ),
			'edit_item' => __( 'Edit Group', 'whx4' ),
			'new_item' => __( 'New Group', 'whx4' ),
			'view_item' => __( 'View Group', 'whx4' ),
			'search_items' => __( 'Search Groups', 'whx4' ),
			'not_found' =>  __( 'No Groups Found', 'whx4' ),
			'not_found_in_trash' => __( 'No Group found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'show_in_menu'     	=> true,
			'query_var'        	=> true,
			'rewrite'			=> array( 'slug' => 'groups' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap' 		=> true,
			'has_archive'  		=> true,
			'hierarchical' 		=> true,
			//'menu_icon'			=> 'dashicons-groups',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), // 
			'taxonomies'		=> array( 'admin_tag', 'group_category' ),
			'show_in_rest'		=> false,    
		);

		register_post_type( 'group', $args );
	
	}
	add_action( 'init', 'register_post_type_group' );
}



/*** VENUES ***/

// TBD: do we need this? or just groups -- buildings -- addresses...?
//if ( in_array('venues', $active_modules ) ) {
if ( in_array('places', $active_modules ) ) {

	// Venue
	function register_post_type_venue() {

		if ( custom_caps() ) { $caps = array('venue', 'venues'); } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'Venues', 'whx4' ),
			'singular_name' => __( 'Venue', 'whx4' ),
			'add_new' => __( 'New Venue', 'whx4' ),
			'add_new_item' => __( 'Add New Venue', 'whx4' ),
			'edit_item' => __( 'Edit Venue', 'whx4' ),
			'new_item' => __( 'New Venue', 'whx4' ),
			'view_item' => __( 'View Venue', 'whx4' ),
			'search_items' => __( 'Search Venues', 'whx4' ),
			'not_found' =>  __( 'No Venues Found', 'whx4' ),
			'not_found_in_trash' => __( 'No Venues found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'show_in_menu'     	=> true,
			'query_var'        	=> true,
			'rewrite'			=> array( 'slug' => 'venues' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			'menu_icon'			=> 'dashicons-admin-multisite',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies'		=> array( 'admin_tag', 'venue_category' ),
			'show_in_rest'		=> false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'venue', $args );
	
	}
	//add_action( 'init', 'register_post_type_venue' );

}

/*** ADDRESSES ***/
// *** TODO: rename as locations? for use when EM is not active? TBD...
// Check for EM activation...
// Fields to add via ACF to EM location: cross_street, neighborhood, location_status, notes... related_entities....
//if ( in_array('addresses', $active_modules ) ) {
if ( in_array('places', $active_modules ) ) {

	// Address
	function register_post_type_address() {

		if ( custom_caps() ) { $caps = array('location', 'locations'); } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'Addresses', 'whx4' ),
			'singular_name' => __( 'Address', 'whx4' ),
			'add_new' => __( 'New Address', 'whx4' ),
			'add_new_item' => __( 'Add New Address', 'whx4' ),
			'edit_item' => __( 'Edit Address', 'whx4' ),
			'new_item' => __( 'New Address', 'whx4' ),
			'view_item' => __( 'View Address', 'whx4' ),
			'search_items' => __( 'Search Addresses', 'whx4' ),
			'not_found' =>  __( 'No Addresses Found', 'whx4' ),
			'not_found_in_trash' => __( 'No Addresses found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui'  			=> true,
			'show_in_menu' 		=> 'edit.php?post_type=venue',
			'query_var'			=> true,
			'rewrite'			=> array( 'slug' => 'addresses' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			//'menu_icon'			=> 'dashicons-welcome-write-blog',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies'		=> array( 'admin_tag' ),
			'show_in_rest'		=> false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'address', $args );
	
	}
	//add_action( 'init', 'register_post_type_address' ); // NB: redundant w/ EM locations -- disabled for venues module 08/20/22
	
}


// TBD: does it make sense to have a separate post type for buildings?
// Consider possibilities: multiple buildings at same address/location over time; buildings re-purposed, e.g. synagogue turned into church, etc.
// Fields to include: location_id, related_entities, building_status (e.g. extant; raised), date_built, date_demolished, building_history (renovations etc.)
//if ( in_array('locations', $active_modules ) ) {
if ( in_array('places', $active_modules ) ) {

	// Building -- change to "Structure"? TBD
	function register_post_type_building() {

		if ( custom_caps() ) { $caps = array('location', 'locations'); } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'Buildings', 'whx4' ),
			'singular_name' => __( 'Building', 'whx4' ),
			'add_new' => __( 'New Building', 'whx4' ),
			'add_new_item' => __( 'Add New Building', 'whx4' ),
			'edit_item' => __( 'Edit Building', 'whx4' ),
			'new_item' => __( 'New Building', 'whx4' ),
			'view_item' => __( 'View Building', 'whx4' ),
			'search_items' => __( 'Search Buildings', 'whx4' ),
			'not_found' =>  __( 'No Buildings Found', 'whx4' ),
			'not_found_in_trash' => __( 'No Buildings found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui'  			=> true,
			'show_in_menu' 		=> 'edit.php?post_type=venue',
			'query_var'			=> true,
			'rewrite'			=> array( 'slug' => 'buildings' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			//'menu_icon'			=> 'dashicons-welcome-write-blog',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
			'taxonomies'		=> array( 'admin_tag' ),
			'show_in_rest'		=> false, // i.e. false = use classic, not block editor
		);

		register_post_type( 'building', $args );
	
	}
	//add_action( 'init', 'register_post_type_building' ); // NB: redundant w/ EM locations -- disabled for venues module 08/20/22
	// Fields to add via ACF to EM location: cross_street, neighborhood, location_status, notes... related_entities....
	
	
}


/*** EVENTS (extended EM) ***/

if ( in_array('events', $active_modules ) ) {

	// Event Series
	function register_post_type_event_series() {

		if ( custom_caps() ) { $caps = array('event', 'events'); } else { $caps = "post"; }
		
		$labels = array(
			'name' => __( 'Event Series', 'whx4' ),
			'singular_name' => __( 'Event Series', 'whx4' ),
			'add_new' => __( 'New Event Series', 'whx4' ),
			'add_new_item' => __( 'Add New Event Series', 'whx4' ),
			'edit_item' => __( 'Edit Event Series', 'whx4' ),
			'new_item' => __( 'New Event Series', 'whx4' ),
			'view_item' => __( 'View Event Series', 'whx4' ),
			'search_items' => __( 'Search Event Series', 'whx4' ),
			'not_found' =>  __( 'No Event Series Found', 'whx4' ),
			'not_found_in_trash' => __( 'No Event Series found in Trash', 'whx4' ),
		);
	
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui'  			=> true,
			'show_in_menu' 		=> 'edit.php?post_type=event',
			'query_var'			=> true,
			'rewrite'			=> array( 'slug' => 'event-series' ), // permalink structure slug
			'capability_type'	=> $caps,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			//'menu_icon'			=> 'dashicons-book',
			'menu_position'		=> null,
			'supports' 			=> array( 'title', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //'editor', 
			'taxonomies'		=> array( 'admin_tag' ),
			'show_in_rest'		=> false,
		);

		register_post_type( 'event_series', $args );
	
	}
	//add_action( 'init', 'register_post_type_event_series' );

}

?>