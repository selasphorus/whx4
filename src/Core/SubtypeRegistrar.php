<?php

namespace atc\WHx4\Core;

use atc\WHx4\Core\Contracts\SubtypeInterface;

final class SubtypeRegistrar
{
    /** @var array<string, array{label:string,args:array}> */
    protected static array $subtypes = [];

    public static function register(): void
    {
        add_action( 'init', [self::class, 'bootstrap'], 5 );
    }

    public static function bootstrap(): void
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
    }

    public static function getTaxonomyForPostType( string $postType ): string
    {
        return "whx4_{$postType}_type";
    }
}
