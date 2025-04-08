<?php

namespace atc\WHx4\Core;

class TaxonomyRegistrar {
	
	public function register_custom_taxonomy ( $args ) {
		
		// Defaults
		$defaults = array(
			'slug' 			=> 'dragon_category',
			'name' 			=> 'Dragon Category',
			'plural_name'	=> 'Dragon Categories',
			'labels'		=> array(),
			'caps'			=> array( 'post' ),
			'tax_args'		=> array(),
		);
		
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		
		//		
		if ( empty($plural_name) ) { $plural_name = $name."s"; }
		if ( empty($slug) ) { $slug = strtolower($name); }
		//if ( empty($caps) ) { $caps = array($name, $plural_name); }
		
		// Default labels
		$default_labels = array(
			'name' => __( $plural_name, 'whx4' ), //'name' => _x( 'Person Categories', 'taxonomy general name' ),
			'singular_name' => __( $name, 'whx4' ), //'singular_name' => _x( 'Person Category', 'taxonomy singular name' ),
			'search_items' => __( 'Search '.$plural_name, 'whx4' ),
			'all_items' => __( 'All '.$plural_name, 'whx4' ),
			'not_found' =>  __( 'No '.$plural_name.' Found', 'whx4' ),
			'not_found_in_trash' => __( 'No '.$plural_name.' found in Trash', 'whx4' ),
			'add_new' => __( 'New '.$name, 'whx4' ),
			'add_new_item' => __( 'Add New '.$name, 'whx4' ),
			'new_item_name' => __( 'New '.$name.' Name', 'whx4' ),
			'edit_item' => __( 'Edit '.$name, 'whx4' ),
			'update_item' => __( 'Update '.$name, 'whx4' ),
			'new_item' => __( 'New '.$name, 'whx4' ),
			'view_item' => __( 'View '.$name, 'whx4' ),
			'parent_item'       => __( 'Parent '.$name, 'whx4' ),
			///'parent_item_colon' => __( 'Parent '.$name.':', 'whx4' ),
			'menu_name' => __( $plural_name, 'whx4' ),			
		);
		
		// Merge user-defined labels
		$labels = array_merge($default_labels, $labels);
		
		// Default args
		$default_args = array(
			'public' => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'query_var'        	=> true,
			'map_meta_cap'		=> true,
			'has_archive' 		=> true,
			'hierarchical'		=> false,
			'show_in_menu'		=> true,
			//'menu_position'		=> null,
			'show_in_rest'		=> false, // false = use classic, not block editor
			//'delete_with_user' 	=> false,
			///'rewrite' => array( 'slug' => $slug ),
		);
		
		// Merge user-defined labels
		$tax_args = array_merge($default_args, $tax_args);
		
		// Build the final array
		$tax_args['labels'] = $labels;
		//$tax_args['capability_type'] = $caps; // TODO: figure out why this isn't working -- even for Admin with all caps granted, could not access new post_type
		//$tax_args['menu_icon'] = $menu_icon;

		register_taxonomy( $slug, $tax_args );
		
	}

}

?>