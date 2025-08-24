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

		// Derive the post type handlers we just used (tolerant to multiple shapes)
		$handlers = $this->resolveHandlersFromActivePostTypes($activePostTypes);

		// Fallback: if we still have nothing, try collecting from modules directly
		if (empty($handlers)) {
			$handlers = $this->collectHandlersFromModules();
		}

		// Contribute CPT-specific taxonomy handlers (e.g., Habitat) to the unified registrar
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

    /**
	 * Best-effort resolution of handler instances from the activePostTypes structure.
	 * Supports:
	 *  - ['monster' => <HandlerInstance>, ...]
	 *  - ['monster' => <HandlerClassString>, ...]  (instantiates)
	 *  - ['monster', 'group', ...]                 (returns empty; use module fallback)
	 *
	 * @param array $activePostTypes
	 * @return array<string,object> map slug => handler instance
	 */
	private function resolveHandlersFromActivePostTypes(array $activePostTypes): array
	{
		$out = [];

		foreach ($activePostTypes as $key => $val) {
			// Case A: associative map of slug => handler instance
			if (is_string($key) && is_object($val)) {
				$out[$key] = $val;
				continue;
			}

			// Case B: associative map of slug => handler class string
			if (is_string($key) && is_string($val) && class_exists($val)) {
				try {
					$out[$key] = new $val();
				} catch (\Throwable) {}
				continue;
			}

			// Case C: numeric array of slugs only — not enough info here.
			// We'll pick these up from modules in collectHandlersFromModules().
		}

		return $out;
	}

	/**
	 * Fallback: ask modules which handler classes they provide, then
	 * filter down to only the *active* post types.
	 *
	 * Expects modules to be registered on the 'rex_register_modules' filter,
	 * and each module (class) to expose getPostTypeHandlerClasses(): array.
	 *
	 * @return array<string,object> map slug => handler instance
	 */
	private function collectHandlersFromModules(): array
	{
		$map = (array) apply_filters('whx4_register_modules', []);
		if (empty($map)) {
			return [];
		}

		$want = array_keys($this->ctx->getActivePostTypes()); // active slugs
		$out  = [];

		foreach ($map as $moduleClass) {
			if (!class_exists($moduleClass)) {
				continue;
			}
			try {
				$module = new $moduleClass();
				if (!method_exists($module, 'getPostTypeHandlerClasses')) {
					continue;
				}
				foreach ((array) $module->getPostTypeHandlerClasses() as $handlerClass) {
					if (!class_exists($handlerClass)) {
						continue;
					}
					// Instantiate to discover its slug
					try {
						$handler = new $handlerClass();
						$slug    = method_exists($handler, 'getSlug') ? $handler->getSlug() : null;
						if ($slug && in_array($slug, $want, true)) {
							$out[$slug] = $handler;
						}
					} catch (\Throwable) {
						// ignore bad handlers
					}
				}
			} catch (\Throwable) {
				// ignore bad modules
			}
		}

		return $out;
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
