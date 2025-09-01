<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\BootOrder;
use atc\WHx4\Core\Contracts\SubtypeInterface;

// WIP 08/22/25
//final class SubtypeRegistrar
final class SubtypeRegistry
{
    private bool $registered = false;

    /** @var array<string, array{label:string, args:array}> */
    /** @var array<string, array<string, array{label:string, args:array}>> */
    protected static array $subtypes = [];

    public static function register(): void
    {
        error_log( '=== SubtypeRegistry::register() ===' );
        add_action('init', [self::class, 'collect'], BootOrder::SUBTYPES);
    }

    public static function collect(): void
    {
        error_log( '=== SubtypeRegistry::collect() ===' );
        self::$subtypes = [];
        $providers = apply_filters('whx4_register_subtypes', []);
        //error_log( 'providers: ' . print_r($providers, true) );

        foreach ($providers as $provider) {
            if ($provider instanceof SubtypeInterface) {
                error_log( 'subtype provider: ' . $provider->getSlug() . ' is a valid SubtypeInterface.');
                $pt   = $provider->getPostType();
                $slug = $provider->getSlug();
                self::$subtypes[$pt][$slug] = [
                    'label' => $provider->getLabel(),
                    'args'  => $provider->getTermArgs(),
                ];
            } else {
                error_log( 'subtype provider: ' . $provider->getSlug() . ' is NOT a valid SubtypeInterface.');
            }
        }
        /**
         * Allow other systems to react to the collected map.
         * IMPORTANT: collection only—no side effects.
         */
        do_action('whx4_subtypes_collected', self::$subtypes);
    }

    /*public static function bootstrap(): void
    {
        // 1) Gather providers
        $providers = apply_filters( 'whx4_register_subtypes', [] );

        foreach ( $providers as $provider ) {
            if ( $provider instanceof SubtypeInterface ) {
                $pt   = $provider->getPostType();
                $slug = $provider->getSlug();

                self::$subtypes[ $pt ][ $slug ] = [
                    'label' => $provider->getLabel(),
                    'args'  => $provider->getTermArgs(),
                ];
            }
        }

        // 2) For each post type with subtypes, ensure a taxonomy and terms
        foreach ( self::$subtypes as $postType => $defs ) {
            $tax = "whx4_{$postType}_type";

            if ( ! taxonomy_exists( $tax ) ) {
                register_taxonomy( $tax, $postType, [
                    'labels'            => [
                        'name'          => ucfirst( $postType ) . ' Types',
                        'singular_name' => ucfirst( $postType ) . ' Type',
                    ],
                    'public'            => false,
                    'show_ui'           => true,
                    'show_in_quick_edit'=> true,
                    'meta_box_cb'       => 'post_categories_meta_box', // familiar UI
                    'hierarchical'      => true,   // lets you nest if you ever need
                    'show_admin_column' => true,
                ] );
            }

            foreach ( $defs as $slug => $def ) {
                if ( ! term_exists( $slug, $tax ) ) {
                    wp_insert_term(
                        $def['label'],
                        $tax,
                        array_merge( ['slug' => $slug], $def['args'] )
                    );
                }
            }
        }
    }*/

    /** @return array<string, array{label:string, args:array}> */
    public static function getForPostType(string $postType): array
    {
        return self::$subtypes[$postType] ?? [];
    }

    public static function getAll(): array
    {
        return self::$subtypes;
    }

    /*public static function getTaxonomyForPostType( string $postType ): string
    {
        return "whx4_{$postType}_type";
    }*/
}
