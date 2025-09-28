<?php

namespace atc\WHx4\Core;

use atc\WHx4\Utils\TitleFilter;
//use atc\WHx4\Core\FieldGroupLoader;
use atc\WHx4\Core\TemplateRouter;
use atc\Whx4\Core\Shortcodes\ShortcodeManager;

class CoreServices
{
    /**
     * Boot all core utility services that need initialization.
     */
    public static function boot(): void
    {
        error_log( '=== CoreServices::boot() ===' );
        $services = apply_filters( 'whx4_core_services', [
            TitleFilter::class,
            //FieldGroupLoader::class,
            TemplateRouter::class,
            ShortcodeManager::class,
            // Add more core classes here as needed
        ]);

        foreach ( $services as $class ) {
            error_log( 'About to attempt to load and boot class: ' . $class );
            if ( is_string( $class ) && method_exists( $class, 'boot' ) ) {
                $class::boot();
            }
        }
    }
}
