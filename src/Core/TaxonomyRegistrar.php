<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\BootOrder;
use atc\WHx4\Core\TaxonomyHandler;
//use atc\WHx4\Core\SubtypeRegistry;

final class TaxonomyRegistrar
{
    public static function register(): void
    {
        error_log( '=== TaxonomyRegistrar::register() ===' );
        add_action( 'init', [ self::class, 'bootstrap' ], BootOrder::TAXONOMIES );
    }

    /**
     * Single place where taxonomies are registered.
     * Accepts handlers via the 'whx4_register_taxonomy_handlers' filter.
     * Also synthesizes handlers for per‑CPT subtype taxonomies from SubtypeRegistry.
     */
    public static function bootstrap(): void
    {
        error_log( '=== TaxonomyRegistrar::bootstrap() ===' );

        // 1) Start with any handlers contributed by CPTs/modules/core
        $handlers = (array) apply_filters('whx4_register_taxonomy_handlers', []);

        // 2) Subtype taxonomies -- Add synthesized handlers for per‑CPT subtype taxonomies
        $subtypes = SubtypeRegistry::getAll();
        foreach (array_keys($subtypes) as $postType) {
            $slug = SubtypeRegistry::getTaxonomyForPostType($postType);
            error_log( 'Subtype slug: ' . $slug . '/postType: '. $postType );

            // Anonymous handler that behaves like a TaxonomyHandler
            $handlers[] = new class($slug, $postType) extends TaxonomyHandler {
                public function __construct(private string $slug, private string $pt)
                {
                    parent::__construct([
                        'slug'         => $this->slug,
                        'plural_slug'  => $this->slug . 's',
                        'object_types' => [$this->pt],
                        'hierarchical' => true,
                    ], null);
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

        error_log("handlers: " . print_r($handlers, true));

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
}
