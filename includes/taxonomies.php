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

/*** Taxonomies for PEOPLE ***/

if ( in_array('people', $active_modules ) ) {

	// Custom Taxonomy: People Category
	function register_taxonomy_person_category() {
		$labels = array(
			'name'              => _x( 'Person Categories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Person Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Person Categories' ),
			'all_items'         => __( 'All Person Categories' ),
			'parent_item'       => __( 'Parent Person Category' ),
			'parent_item_colon' => __( 'Parent Person Category:' ),
			'edit_item'         => __( 'Edit Person Category' ),
			'update_item'       => __( 'Update Person Category' ),
			'add_new_item'      => __( 'Add New Person Category' ),
			'new_item_name'     => __( 'New Person Category Name' ),
			'menu_name'         => __( 'Person Categories' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			//'show_in_menu' => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'person_category' ],
		);
		/*if ( custom_caps() ) {
			$cap = 'person';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}*/	
		register_taxonomy( 'person_category', [ 'person' ], $args );
		//register_taxonomy( 'test_tax', array( 0 => 'person' ), $args ),
	}
	//add_action( 'init', 'register_taxonomy_person_category' );
	
	// Custom Taxonomy: Person Title
	function register_taxonomy_person_title() {
		$labels = array(
			'name'              => _x( 'Person Titles', 'taxonomy general name' ),
			'singular_name'     => _x( 'Person Title', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Person Titles' ),
			'all_items'         => __( 'All Person Titles' ),
			'parent_item'       => __( 'Parent Person Title' ),
			'parent_item_colon' => __( 'Parent Person Title:' ),
			'edit_item'         => __( 'Edit Person Title' ),
			'update_item'       => __( 'Update Person Title' ),
			'add_new_item'      => __( 'Add New Person Title' ),
			'new_item_name'     => __( 'New Person Title Name' ),
			'menu_name'         => __( 'Person Titles' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'person_title' ],
		);
		/*if ( custom_caps() ) {
			$cap = 'person';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}*/	
		register_taxonomy( 'person_title', [ 'person' ], $args );
	}
	//add_action( 'init', 'register_taxonomy_person_title' );
}

/*** Taxonomies for GROUPS (Organizations/Ensembles/Institutions) ***/

if ( in_array( 'people', $active_modules ) || in_array( 'groups', $active_modules ) ) {
	// Custom Taxonomy: Group Category
	function register_taxonomy_group_category() {
		$labels = array(
			'name'              => _x( 'Group Categories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Group Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Group Categories' ),
			'all_items'         => __( 'All Group Categories' ),
			'parent_item'       => __( 'Parent Group Category' ),
			'parent_item_colon' => __( 'Parent Group Category:' ),
			'edit_item'         => __( 'Edit Group Category' ),
			'update_item'       => __( 'Update Group Category' ),
			'add_new_item'      => __( 'Add New Group Category' ),
			'new_item_name'     => __( 'New Group Category Name' ),
			'menu_name'         => __( 'Group Categories' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'group_category' ],
		);
		/*if ( custom_caps() ) {
			$cap = 'group';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}*/	
		register_taxonomy( 'group_category', [ 'group', 'organization', 'ensemble' ], $args );
	}
	//add_action( 'init', 'register_taxonomy_group_category' );
}

/*** Taxonomies for VENUES ***/

//if ( in_array('venues', $active_modules ) ) {
if ( in_array('places', $active_modules ) ) {
	// Custom Taxonomy: Venue Category
	function register_taxonomy_venue_category() {
		$labels = array(
			'name'              => _x( 'Venue Categories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Venue Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Venue Categories' ),
			'all_items'         => __( 'All Venue Categories' ),
			'parent_item'       => __( 'Parent Venue Category' ),
			'parent_item_colon' => __( 'Parent Venue Category:' ),
			'edit_item'         => __( 'Edit Venue Category' ),
			'update_item'       => __( 'Update Venue Category' ),
			'add_new_item'      => __( 'Add New Venue Category' ),
			'new_item_name'     => __( 'New Venue Category Name' ),
			'menu_name'         => __( 'Venue Categories' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'venue_category' ],
		);
		if ( custom_caps() ) {
			$cap = 'venue';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}
		register_taxonomy( 'venue_category', [ 'venue' ], $args );
	}
	//add_action( 'init', 'register_taxonomy_venue_category' );
}


/*** Taxonomies for EVENT PROGRAMS ***/

if ( in_array('events', $active_modules ) ) {

	// Custom Taxonomy: Person Role
	function register_taxonomy_person_role() {
		$labels = array(
			'name'              => _x( 'Personnel Roles', 'taxonomy general name' ),
			'singular_name'     => _x( 'Personnel Role', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Personnel Roles' ),
			'all_items'         => __( 'All Personnel Roles' ),
			'parent_item'       => __( 'Parent Personnel Role' ),
			'parent_item_colon' => __( 'Parent Personnel Role:' ),
			'edit_item'         => __( 'Edit Personnel Role' ),
			'update_item'       => __( 'Update Personnel Role' ),
			'add_new_item'      => __( 'Add New Personnel Role' ),
			'new_item_name'     => __( 'New Personnel Role Name' ),
			'menu_name'         => __( 'Personnel Roles' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'meta_box_cb'       => false,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'person_role' ],
		);
		if ( custom_caps() ) {
			$cap = 'event_program';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}
		register_taxonomy( 'person_role', [ 'event', 'event_program' ], $args );
	}
	//add_action( 'init', 'register_taxonomy_person_role' );

	// Custom Taxonomy: Program Label
	function register_taxonomy_program_label() {
		$labels = array(
			'name'              => _x( 'Program Labels', 'taxonomy general name' ),
			'singular_name'     => _x( 'Program Label', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Program Labels' ),
			'all_items'         => __( 'All Program Labels' ),
			'parent_item'       => __( 'Parent Program Label' ),
			'parent_item_colon' => __( 'Parent Program Label:' ),
			'edit_item'         => __( 'Edit Program Label' ),
			'update_item'       => __( 'Update Program Label' ),
			'add_new_item'      => __( 'Add New Program Label' ),
			'new_item_name'     => __( 'New Program Label Name' ),
			'menu_name'         => __( 'Program Labels' ),
		);
		$args = array(
			'labels'            => $labels,
			'description'          => '',
			'public'               => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'meta_box_cb'       => false,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'program_label' ],
		);
		if ( custom_caps() ) {
			$cap = 'event_program';
			$args['capabilities'] = array(
				'manage_terms'  =>   'manage_'.$cap.'_terms',
				'edit_terms'    =>   'edit_'.$cap.'_terms',
				'delete_terms'  =>   'delete_'.$cap.'_terms',
				'assign_terms'  =>   'assign_'.$cap.'_terms',
			);
		}
		register_taxonomy( 'program_label', [ 'event', 'event_program' ], $args );
	}
	//add_action( 'init', 'register_taxonomy_program_label' );

}

?>