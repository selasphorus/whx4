<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\PostTypeHandler;

class PostTypeRegistrar {
    
	// Registers a custom post type using a PostTypeHandler
    public function registerCPT(PostTypeHandler $handler): void {
    
    	error_log( '=== PostTypeRegistrar->registerCPT() ===' );
    	
    	$slug = $handler->getSlug();
    	error_log('slug: '.$slug);
    	
    	$labels = $handler->getLabels();
    	error_log('slug: '.print_r($labels,true));
    	
    	// Get capabilities (if defined, otherwise fall back to defaults)
        $capabilities = $handler->getCapabilities();
        if (!$capabilities) {
            $capabilities = $this->generateDefaultCapabilities($handler);
        }
    	error_log('capabilities: '.$capabilities);
        
        // Register the post type
        register_post_type($slug, [
            'labels'       => $labels,
            'capabilities' => $capabilities, // $handler->getLabels(),
            'public'       => true,
            'show_in_rest' => true,  // Enable REST API support
            'supports'     => ['title', 'editor'],
            'rewrite'      => ['slug' => $slug], //'rewrite' => ['slug' => $handlerClass::getSlug()],
            'has_archive'  => true,
            'show_in_menu' => true,
        ]);
        
	}
	
	// TODO:  move this to the PostTypeHandler where default labels etc are defined
	protected function generateDefaultCapabilities(PostTypeHandler $handler): array {
        
        error_log( '=== PostTypeRegistrar->generateDefaultCapabilities() ===' );
        
        $slug = $handler->getSlug();

        return [
            'edit_post'          => "edit_{$slug}",
            'read_post'          => "read_{$slug}",
            'delete_post'        => "delete_{$slug}",
            'edit_posts'         => "edit_{$slug}s",
            'edit_others_posts'  => "edit_others_{$slug}s",
            'publish_posts'      => "publish_{$slug}s",
            'read_private_posts' => "read_private_{$slug}s",
        ];
    }
    
    public function registerMany( array $postTypeClasses ): void
    {
    	error_log( '=== PostTypeRegistrar->registerMany() ===' );
    	error_log( 'postTypeClasses: ' . print_r( $postTypeClasses, true ) );
        foreach( $postTypeClasses as $handlerClass ) {
        	error_log( 'attempting to register handlerClass: '.$handlerClass );
        	$handler = new $handlerClass();
            $this->registerCPT( $handler );
        }
    }
    
	/*
	public function registerCPT ( $args ) {
		
		// Defaults
		$defaults = [
			'slug' 			=> 'dragon',
			'name' 			=> 'Dragon',
			'plural_name'	=> null,
			'labels'		=> [],
			'supports'		=> [ 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ],
			'taxonomies'	=> [ 'category', 'tag' ],
			'caps'			=> [ 'post' ],
			'cpt_args'		=> [],
		];
		
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		
		//$name, $plural_name, $labels, $args, $caps, $supports, $taxonomies
		
		if ( empty($plural_name) ) { $plural_name = $name."s"; }
		if ( empty($slug) ) { $slug = strtolower($name); }
		//if ( empty($caps) ) { $caps = [$name, $plural_name); }
		
		// Default labels
		$default_labels = [
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
		];
		
		// Merge user-defined labels
		$labels = array_merge($default_labels, $labels);
		
		// TODO: modify so it's easier to set all individual args via function call
		// Default args
		$default_args = [
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
			'rewrite' => [ 'slug' => $slug ],
		];
		
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
	*/
	
}
