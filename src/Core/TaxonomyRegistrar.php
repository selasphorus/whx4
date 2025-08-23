<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\BootOrder;
use atc\WHx4\Core\TaxonomyHandler;
//use atc\WHx4\Core\SubtypeRegistry;

final class TaxonomyRegistrar
{
    public static function register(): void
    {
        add_action( 'init', [ self::class, 'bootstrap' ], BootOrder::TAXONOMIES );
    }

    /**
     * Single place where taxonomies are registered.
     * Accepts handlers via the 'whx4_register_taxonomy_handlers' filter.
     * Also synthesizes handlers for per‑CPT subtype taxonomies from SubtypeRegistry.
     */
    public static function bootstrap(): void
    {
        // 1) Start with any handlers contributed by CPTs/modules/core
        $handlers = (array) apply_filters('whx4_register_taxonomy_handlers', []);

        // 2) Subtype taxonomies -- Add synthesized handlers for per‑CPT subtype taxonomies
        $subtypes = SubtypeRegistry::getAll();
        foreach (array_keys($subtypes) as $postType) {
            $slug = SubtypeRegistry::getTaxonomyForPostType($postType);

            // Anonymous handler that behaves like a TaxonomyHandler
            $handlers[] = new class($slug, $postType) extends TaxonomyHandler {
                public function __construct(private string $slug, private string $pt)
                {
                    parent::__construct([
                        'slug'         => $this->slug,
                        'plural_slug'  => $this->slug . 's',
                        'object_types' => [$this->pt],
                        'hierarchical' => true,
                    ], 'taxonomy', null);
                }
            };
            /*
            // old way:
            if ( !taxonomy_exists( $slug ) ) {
                // Keep args minimal; you can centralize defaults here
                register_taxonomy( $slug, $postType, [
                    'labels'            => [
                        'name'          => ucfirst( $postType ) . ' Types',
                        'singular_name' => ucfirst( $postType ) . ' Type',
                    ],
                    'public'            => false,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'hierarchical'      => true,
                    'meta_box_cb'       => 'post_categories_meta_box',
                ] );
            }*/
        }

        if (empty($handlers)) {
            return; // nothing to do
        }

        // Resolve active CPTs (for '*' wildcard); decouple via a filter
        $activePostTypes = (array) apply_filters('whx4_active_post_types', []);

        foreach ($handlers as $h) {
            // Accept FQCNs or ready instances
            if (is_string($h)) {
                if (!class_exists($h) || !is_subclass_of($h, TaxonomyHandler::class)) {
                    continue;
                }
                $h = new $h();
            } elseif (!$h instanceof TaxonomyHandler) {
                continue;
            }

            // Resolve wildcard '*' and apply tax to all active WHx4 CPTs (if provided)
            $targets = $h->getObjectTypes();
            if (in_array('*', $targets, true)) {
                $targets = $activePostTypes ?: [];
                // If nothing provided, skip rather than registering a taxonomy with no object types
                if (empty($targets)) {
                    continue;
                }
                // Quick way to set the resolved targets: override via a tiny child class
                $h = new class($h, $targets) extends TaxonomyHandler {
                    public function __construct(private TaxonomyHandler $base, private array $targets)
                    {
                        // clone base config but swap object_types
                        $cfg = $this->base->getConfig();
                        $cfg['object_types'] = $this->targets;
                        parent::__construct($cfg, 'taxonomy', null);
                    }
                };
            }

            // Finally, register it (handler knows how to call register_taxonomy)
            try {
                $h->register();
            } catch (\Throwable) {
                // optional: error_log(...)
            }
        }
    }

    /**
     * Registers:
     *  - Subtype taxonomies (one per CPT that has subtypes)
     *  - Module taxonomies (from TaxonomyHandler classes)
     */
    public static function bootstrap(): void
    {
        // 1) Subtype taxonomies (single taxonomy per CPT that has subtypes)
        $subtypes = SubtypeRegistry::getAll();
        if ( !empty( $subtypes ) ) {
            foreach ( array_keys( $subtypes ) as $postType ) {
                $tax = SubtypeRegistry::getTaxonomyForPostType( $postType );

                if ( !taxonomy_exists( $tax ) ) {
                    // Keep args minimal; you can centralize defaults here
                    register_taxonomy( $tax, $postType, [
                        'labels'            => [
                            'name'          => ucfirst( $postType ) . ' Types',
                            'singular_name' => ucfirst( $postType ) . ' Type',
                        ],
                        'public'            => false,
                        'show_ui'           => true,
                        'show_admin_column' => true,
                        'hierarchical'      => true,
                        'meta_box_cb'       => 'post_categories_meta_box',
                    ] );
                }
            }
        }

        // 2) Module taxonomies (TaxonomyHandler classes)
        $handlerClasses = self::collectTaxonomyHandlerClasses();
        if ( empty( $handlerClasses ) && empty( $subtypes ) ) {
            // No work to do—exit early.
            return;
        }

        foreach ( $handlerClasses as $class ) {
            if ( !class_exists( $class ) || !is_subclass_of( $class, TaxonomyHandler::class ) ) {
                continue;
            }

            try {
                $handler = new $class();

                // If the handler knows how to register itself, let it.
                if ( method_exists( $handler, 'register' ) ) {
                    $handler->register();
                    continue;
                }

                // Otherwise, attempt a generic registration using common handler APIs.
                $slug        = method_exists( $handler, 'getSlug' ) ? $handler->getSlug() : null;
                $objectTypes = $handler->getObjectTypes(); // required by your TaxonomyHandler
                $args        = method_exists( $handler, 'getArgs' ) ? $handler->getArgs() : self::defaultArgsFromHandler( $handler );

                if ( $slug && !taxonomy_exists( $slug ) ) {
                    register_taxonomy( $slug, $objectTypes, $args );
                } elseif ( $slug && taxonomy_exists( $slug ) && !empty( $objectTypes ) ) {
                    // Ensure object types are attached in case a module adds a second CPT
                    foreach ( $objectTypes as $pt ) {
                        register_taxonomy_for_object_type( $slug, $pt );
                    }
                }
            } catch ( \Throwable $e ) {
                // Optionally log for diagnostics
                // error_log('TaxonomyRegistrar error for ' . $class . ': ' . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Collect taxonomy handler class names from:
     *  - whx4_register_taxonomy_handlers (preferred)
     *  - modules exposing getTaxonomyHandlerClasses() (fallback)
     *
     * @return string[]
     */
    private static function collectTaxonomyHandlerClasses(): array
    {
        $classes = apply_filters( 'whx4_register_taxonomy_handlers', [] );

        // Fallback: inspect registered modules for a getTaxonomyHandlerClasses() list
        $moduleMap = apply_filters( 'whx4_register_modules', [] ); // e.g. ['supernatural' => \...\Supernatural\Module::class]
        foreach ( $moduleMap as $moduleClass ) {
            if ( !class_exists( $moduleClass ) ) {
                continue;
            }
            try {
                $module = new $moduleClass();
                if ( method_exists( $module, 'getTaxonomyHandlerClasses' ) ) {
                    $moduleHandlers = (array) $module->getTaxonomyHandlerClasses();
                    $classes        = array_merge( $classes, $moduleHandlers );
                }
            } catch ( \Throwable ) {
                continue;
            }
        }

        // De-dup & reindex
        return array_values( array_unique( array_filter( $classes ) ) );
    }

    /**
     * Build sensible defaults when a handler doesn't provide getArgs().
     * Tries to infer labels from common methods or config patterns.
     */
    private static function defaultArgsFromHandler( TaxonomyHandler $handler ): array
    {
        // Try to discover a human label; fall back to slug formatting.
        $slug = method_exists( $handler, 'getSlug' ) ? $handler->getSlug() : 'taxonomy';
        $name = ucwords( str_replace( [ '_', '-' ], ' ', $slug ) );

        $hierarchical = false;
        if ( method_exists( $handler, 'isHierarchical' ) ) {
            $hierarchical = (bool) $handler->isHierarchical();
        } elseif ( method_exists( $handler, 'getConfig' ) ) {
            // If BaseHandler exposes getConfig(), read from there (optional).
            try {
                $cfg = $handler->getConfig();
                if ( isset( $cfg['hierarchical'] ) ) {
                    $hierarchical = (bool) $cfg['hierarchical'];
                }
            } catch ( \Throwable ) {
                // ignore
            }
        }

        return [
            'labels'            => [
                'name'          => $name,
                'singular_name' => $name,
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => $hierarchical,
        ];
    }



    public function registerTaxonomy ( $args )
    {
        error_log( '=== TaxonomyRegistrar->registerTaxonomy() ===' );

        // Defaults
        $defaults = [
            'slug' => 'dragon_category',
            'name' => 'Dragon Category',
            'plural_name' => 'Dragon Categories',
            'labels' => array(),
            ///'caps' => array( 'post' ),
            'tax_args' => array(),
        ];

        // Parse & Extract args
        $args = wp_parse_args( $args, $defaults );
        extract( $args );

        //
        if ( empty($plural_name) ) { $plural_name = $name."s"; }
        //if ( empty($slug) ) { $slug = strtolower($name); }
        //if ( empty($caps) ) { $caps = array($name, $plural_name); }

        // Default labels
        $default_labels = [
            'name' => _x( $plural_name, 'taxonomy general name' ), //'name' => __( $plural_name, 'whx4' ), //
            'singular_name' => _x( $name, 'taxonomy singular name' ), //'singular_name' => __( $name, 'whx4' ), //
            /*'search_items' => __( 'Search '.$plural_name, 'whx4' ),
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
            'menu_name' => __( $plural_name, 'whx4' ),*/
        ];
        /*
        $default_labels = [
            'name' => _x($plural_name, 'taxonomy general name', WHX4_TEXTDOMAIN),
            'singular_name' => _x($name, 'taxonomy singular name', WHX4_TEXTDOMAIN),
            'search_items' => __('Search ' . $plural_name, WHX4_TEXTDOMAIN),
            'all_items' => __('All ' . $plural_name, WHX4_TEXTDOMAIN),
            'parent_item' => __('Parent ' . $name, WHX4_TEXTDOMAIN),
            'parent_item_colon' => __('Parent ' . $name . ':', WHX4_TEXTDOMAIN),
            'edit_item' => __('Edit ' . $name, WHX4_TEXTDOMAIN),
            'update_item' => __('Update ' . $name, WHX4_TEXTDOMAIN),
            'add_new_item' => __('Add New ' . $name, WHX4_TEXTDOMAIN),
            'new_item_name' => __('New ' . $name . ' Name', WHX4_TEXTDOMAIN),
            'menu_name' => __($plural_name, WHX4_TEXTDOMAIN),
        ];
        */

        // Merge user-defined labels
        $labels = array_merge($default_labels, $labels);

        // Default args
        $default_args = array(
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui' => true,
            /*'query_var' => true,
            'map_meta_cap' => true,
            'has_archive'  => true,
            'hierarchical' => false,
            'show_in_menu' => true,
            //'menu_position' => null,
            'show_in_rest' => false, // false = use classic, not block editor
            //'delete_with_user' => false,
            ///'rewrite' => array( 'slug' => $slug ),*/
        );
        /*
        $default_args = [
            'labels' => $final_labels,
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => $slug],
        ];
        */
        // Merge user-defined labels
        $tax_args = array_merge($default_args, $tax_args);

        // Build the final array
        $tax_args['labels'] = $labels;
        //$tax_args['capability_type'] = $caps; // TODO: figure out why this isn't working -- even for Admin with all caps granted, could not access new post_type
        //$tax_args['menu_icon'] = $menu_icon;

        register_taxonomy( $slug, $post_types, $tax_args );
    }
}
