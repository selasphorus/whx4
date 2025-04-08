<?php

namespace atc\WHx4\Core;

class PostTypeRegistrar {
	
	public function register_custom_post_type ( $args ) {
		
		// Defaults
		$defaults = array(
			'slug' 			=> 'dragon',
			'name' 			=> 'Dragon',
			'plural_name'	=> null,
			'labels'		=> array(),
			'supports'		=> array( 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ),
			'taxonomies'	=> array( 'category', 'tag' ),
			'caps'			=> array( 'post' ),
			'cpt_args'		=> array(),
		);
		
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		
		//$name, $plural_name, $labels, $args, $caps, $supports, $taxonomies
		
		if ( empty($plural_name) ) { $plural_name = $name."s"; }
		if ( empty($slug) ) { $slug = strtolower($name); }
		//if ( empty($caps) ) { $caps = array($name, $plural_name); }
		
		// Default labels
		$default_labels = array(
			'name' => __( $plural_name, 'whx4' ),
			'singular_name' => __( $name, 'whx4' ),
			'add_new' => __( 'New '.$name, 'whx4' ),
			'add_new_item' => __( 'Add New '.$name, 'whx4' ),
			'edit_item' => __( 'Edit '.$name, 'whx4' ),
			'new_item' => __( 'New '.$name, 'whx4' ),
			'view_item' => __( 'View '.$name, 'whx4' ),
			'search_items' => __( 'Search '.$plural_name, 'whx4' ),
			'not_found' =>  __( 'No '.$plural_name.' Found', 'whx4' ),
			'not_found_in_trash' => __( 'No '.$plural_name.' found in Trash', 'whx4' ),
		);
		
		// Merge user-defined labels
		$labels = array_merge($default_labels, $labels);
		
		// TODO: modify so it's easier to set all individual args via function call
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
			'rewrite' => array( 'slug' => $slug ),
		);
		
		// Merge user-defined labels
		$cpt_args = array_merge($default_args, $cpt_args);
		
		$cpt_args['labels'] = $labels;
		//$cpt_args['capability_type'] = $caps; // TODO: figure out why this isn't working -- even for Admin with all caps granted, could not access new post_type
		$cpt_args['supports'] = $supports;
		$cpt_args['taxonomies'] = $taxonomies;

		register_post_type( $slug, $cpt_args );
		
		//return $cpt_args; // tft
		//return true; //???
		
	}

}

?>