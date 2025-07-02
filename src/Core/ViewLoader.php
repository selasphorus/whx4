<?php

namespace atc\Whx4\Core;

class ViewLoader
{
    protected static array $moduleViewRoots = [];

    public static function hasViewRoot( string $slug ): bool
    {
        return isset( self::$moduleViewRoots[ $slug ] );
    }

    /**
     * Register a module-specific view directory (e.g., src/Modules/Supernatural/Views)
     */
    public static function registerModuleViewRoot( string $moduleSlug, string $absolutePath ): void
    {
        self::$moduleViewRoots[ $moduleSlug ] = rtrim( $absolutePath, '/' );
    }

    /**
     * Echo a rendered view immediately.
     */
    public static function render( string $view, array $vars = [], string $module = '' ): void
    {
        echo self::renderToString( $view, $vars, $module );
    }

    /**
     * Return a rendered view as a string, searching in theme, module, and plugin folders. Uses output buffering.
     */
    public static function renderToString( string $view, array $vars = [], string $module = '' ): string
    {
        $path = self::getViewPath( $view, $module );

        if ( $path ) {
            ob_start();
            extract( $vars, EXTR_SKIP );
            include $path;
            return ob_get_clean();
        }

        return '<div class="notice notice-error"><p>' .
            esc_html( "View not found: $view (module: " . $module . ") at path: " . $path ) .
            '</p></div>';
    }

    /**
     * Get the resolved full path to a view (first match), or null if not found.
     */
    public static function getViewPath( string $view, string $module = '' ): ?string
    {
        foreach ( self::generateSearchPaths( $view, $module ) as $path ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        return null;
    }
    /* Usage examples:
    From a module:
    $this->renderView( 'monster-list', [
        'monsters' => $this->getMonsterPosts(),
    ]);

    If views/supernatural/monster-list.php doesn’t exist, it will fall back to:
    views/monster-list.php
    */

    /**
     * Generate an ordered list of candidate view paths based on view name and module.
     */
    protected static function generateSearchPaths( string $view, string $module = '' ): array
    {
        $paths = [];
        $moduleSlug = strtolower( $module );

        if ( $moduleSlug ) {
            // 1. Theme override: /themes/my-theme/whx4/{module}/view.php
            $paths[] = get_stylesheet_directory() . "/whx4/{$moduleSlug}/{$view}.php";

            // 2. Module-registered path: src/Modules/Supernatural/Views/view.php)
            if ( isset( self::$moduleViewRoots[ $module ] ) ) {
                $paths[] = self::$moduleViewRoots[ $module ] . "/{$view}.php";
            }

            // 3. Plugin-level module fallback: views/modules/{module}/view.php
            $paths[] = WHX4_PLUGIN_DIR . "views/modules/{$moduleSlug}/{$view}.php";
        }

        // 4. Plugin-level global fallback: views/view.php (shared/global)
        $paths[] = WHX4_PLUGIN_DIR . "views/{$view}.php";

        return $paths;
    }
}
