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
    	error_log('labels: '.print_r($labels,true));

    	$supports = $handler->getSupports();
    	error_log('supports: '.print_r($supports,true));

    	$taxonomies = $handler->getTaxonomies();
    	error_log('taxonomies: '.print_r($taxonomies,true));

    	// Get capabilities (if defined, otherwise fall back to defaults)
        $capabilities = $handler->getCapabilities();
        if (!$capabilities) {
            $capabilities = $this->generateDefaultCapabilities($handler);
        }
    	error_log('capabilities: '.print_r($capabilities,true));

    	$icon = $handler->getMenuIcon();
    	error_log('icon: '.$icon);

        // Register the post type
        register_post_type($slug, [

            'public'       => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'query_var'        	=> true,
            'show_in_rest' => true,  // Enable REST API support // false = use classic, not block editor
            'labels'       => $labels,
            'capability_type' => $slug,
            'capabilities' => $capabilities, // $handler->getLabels(),
			//'caps'			=> [ 'post' ],
            'map_meta_cap'    => true,

            'supports'     => $supports, //['title', 'editor'],
            //'supports'		=> [ 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ],
			//'taxonomies'	=> [ 'category', 'tag' ],
            'rewrite'      => ['slug' => $slug], //'rewrite' => ['slug' => $handlerClass::getSlug()],
            'has_archive'  => true,
            'show_in_menu' => true,
			'hierarchical'		=> false,
			//'menu_position'		=> null,
			//'delete_with_user' 	=> false,
        ]);

	}

    //
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

    // Capabilities
	// TODO:  move this to the PostTypeHandler where default labels etc are defined?
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

    public function assignPostTypeCapabilities(): void {
		$roles = [ 'administrator', 'editor' ]; // Add others as needed

		foreach ( $this->getPostTypeHandlers() as $handler ) {
			$caps = $handler->getCapabilities();

			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );

				if ( $role ) {
					foreach ( $caps as $cap ) {
						$role->add_cap( $cap );
					}
				}
			}
		}
	}

	public function removePostTypeCapabilities(): void {
		$roles = [ 'administrator', 'editor' ]; // Adjust as needed

		foreach ( $this->getPostTypeHandlers() as $handler ) {
			$caps = $handler->getCapabilities();

			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );

				if ( $role ) {
					foreach ( $caps as $cap ) {
						$role->remove_cap( $cap );
					}
				}
			}
		}
	}

}
