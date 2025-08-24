<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\PluginContext;
use atc\WHx4\Core\BootOrder;
use atc\WHx4\Core\PostTypeHandler;

class PostTypeRegistrar
{
    private bool $registered = false;
    public function __construct(private PluginContext $ctx) {}

    public function register(): void
    {
        if ( $this->registered ) return;
        add_action( 'init', [$this, 'bootstrap'], BootOrder::CPTS );
        $this->registered = true;
    }

    public function bootstrap(): void
    {
        //error_log( '=== PostTypeRegistrar::bootstrap() ===' );

        // Abort if no modules have been booted
		if ( !$this->ctx->modulesBooted() ) {
		    error_log( '=== no modules booted yet => abort ===' );
			return;
		}

        $activePostTypes = $this->ctx->getActivePostTypes();
        if ( empty( $activePostTypes ) ) {
			error_log( 'No active post types found. Skipping registration.' );
			return;
		}
		//error_log( 'activePostTypes: '.print_r($activePostTypes, true) );

		// Register CPTs
		$this->registerMany( $activePostTypes );

		// Expose active CPT slugs for wildcard shared taxonomies
		add_filter('whx4_active_post_types', function(array $cpts) use ($activePostTypes): array {
			return array_keys($activePostTypes);
		}, 10, 1);

		// Contribute CPT-specific taxonomy handlers to the unified registrar
		// Try to pull handler instances/map from the context. Guard if unavailable.
		$handlers = method_exists($this->ctx, 'getPostTypeHandlers')
			? (array) $this->ctx->getPostTypeHandlers()
			: [];

		error_log( 'PostType handlers: '.print_r($handlers, true) );

		// After resolving $handlers, deal with taxonomies
		if (!empty($handlers)) {
			add_filter('whx4_register_taxonomy_handlers', function(array $list) use ($handlers): array {
				foreach ($handlers as $slug => $handler) {
					if (is_object($handler) && method_exists($handler, 'getTaxonomyHandlerClasses')) {
						$list = array_merge($list, (array) $handler->getTaxonomyHandlerClasses());
					}
				}
				return $list;
			}, 10, 1);
		}
    }

	// Registers a custom post type using a PostTypeHandler
    public function registerCPT(PostTypeHandler $handler): void
    {
        //error_log( '=== PostTypeRegistrar->registerCPT() ===' );

    	$slug = $handler->getSlug();
    	//error_log('slug: '.$slug);

    	$labels = $handler->getLabels();
    	//error_log('labels: '.print_r($labels,true));

    	$supports = $handler->getSupports();
    	//error_log('supports: '.print_r($supports,true));

    	$taxonomies = $handler->getTaxonomies();
    	//error_log('taxonomies: '.print_r($taxonomies,true));

    	// Get capabilities (if defined, otherwise fall back to defaults)
        $capabilities = $handler->getCapabilities();
        if (!$capabilities) {
            $capabilities = $this->generateDefaultCapabilities($handler);
        }
    	//error_log('capabilities: '.print_r($capabilities,true));

    	$icon = $handler->getMenuIcon();
    	//error_log('icon: '.$icon);

        // Register the post type
        register_post_type($slug, [
            'public'       => true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true,
			'query_var'        	=> true,
            'show_in_rest' => true,  // Enable REST API support // false = use classic, not block editor
            'labels'       => $labels,
            'capability_type' => $slug,
            'capabilities' => $capabilities,
			//'caps'			=> [ 'post' ],
            'map_meta_cap'    => true,
            //
            'supports'     => $supports, //['title', 'editor'],
            //'supports'		=> [ 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ],
			//'taxonomies'	=> [ 'category', 'tag' ],
            'rewrite'      => ['slug' => $slug], //'rewrite' => ['slug' => $handlerClass::getSlug()],
            'has_archive'  => true,
            'show_in_menu' => true,
            'menu_icon' => $icon,
			'hierarchical'		=> false,
			//'menu_position'		=> null,
			//'delete_with_user' 	=> false,
        ]);
	}

    //
    public function registerMany( array $postTypeClasses ): void
    {
    	//error_log( '=== PostTypeRegistrar->registerMany() ===' );
    	//error_log( 'postTypeClasses: ' . print_r( $postTypeClasses, true ) );
        foreach( $postTypeClasses as $handlerClass ) {
        	//error_log( 'attempting to register handlerClass: '.$handlerClass );
        	$handler = new $handlerClass();
            $this->registerCPT( $handler );
        }
    }

    // Capabilities
	// TODO:  move this to the PostTypeHandler where default labels etc are defined?
	protected function generateDefaultCapabilities(PostTypeHandler $handler): array
	{

        //error_log( '=== PostTypeRegistrar->generateDefaultCapabilities() ===' );

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

	public function assignPostTypeCapabilities(array $handlers): void
	{
		//error_log( '=== PostTypeRegistrar::assignPostTypeCapabilities() ===' );
		$roles = ['administrator', 'editor'];

		foreach ( $handlers as $slug => $fqcn ) {
			// Make sure the handler is of correct type
			$handler = new $fqcn; // $postTypeHandlerClass();
			//error_log( '== handler: ' . print_r($handler, true). ' ==' );
			if ( $handler instanceof PostTypeHandler ) {
				$caps = $handler->getCapabilities();

				foreach ($roles as $roleName) {
					$role = get_role($roleName);
					if ($role) {
						foreach ($caps as $cap) {
							$role->add_cap($cap);
						}
					}
				}
			} else {
			    error_log('handler is not a PostTypeHandler.');
			}

		}
	}


	public function removePostTypeCapabilities(): void
	{
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
