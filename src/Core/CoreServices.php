<?php

namespace atc\Whx4\Core;

use atc\WHx4\Utils\TitleFilter;
use atc\WHx4\Core\FieldGroupLoader;

class CoreServices
{
    /**
     * Boot all core utility services that need initialization.
     */
    public static function boot(): void
    {
        $services = apply_filters( 'whx4_core_services', [
            TitleFilter::class,
            FieldGroupLoader::class,
            // Add more core classes here as needed
        ]);

        foreach ( $services as $class ) {
            if ( is_string( $class ) && method_exists( $class, 'boot' ) ) {
                $class::boot();
            }
        }
    }
}
