<?php

namespace atc\WHx4\Core;

use atc\WHx4\Utils\TitleFilter;
use atc\WHx4\Core\FieldGroupLoader;
use atc\WHx4\Core\TemplateRouter;
use atc\WHx4\Core\Shortcodes\ShortcodeManager;
use atc\WHx4\Core\Assets\AssetManager;

class CoreServices
{
    /**
     * Boot all core utility services that need initialization.
     */
    public static function boot(): void
    {
        //error_log( '=== CoreServices::boot() ===' );
        $services = apply_filters( 'whx4_core_services', [
            TitleFilter::class,
            //FieldGroupLoader::class,
            TemplateRouter::class,
            ShortcodeManager::class,
            AssetManager::class,
        ]);

        foreach ( $services as $class ) {
            //error_log( 'About to attempt to load and boot class: ' . $class );
            if ( is_string( $class ) && method_exists( $class, 'boot' ) ) {
                $class::boot();
            } else {
                //if ( !is_string( $class ) ) { error_log( 'class: ' . $class . ' -- NOT a string!'); }
                //if ( !method_exists( $class, 'boot' ) ) { error_log( 'class: ' . $class . ' boot method not found!'); }
                //if ( !class_exists( $class) ) { error_log( 'class: ' . $class . ' -- DOES NOT EXIST!'); }
            }
        }
    }
}
