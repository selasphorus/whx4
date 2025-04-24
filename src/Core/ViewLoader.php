<?php

namespace atc\Whx4\Core;

class ViewLoader
{
    protected static array $moduleViewRoots = [];

    /**
     * Register a module-specific view directory (e.g., src/Modules/Supernatural/views)
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
        $paths = [];
        $moduleDir = $module ? strtolower( $module ) : '';

        // 1. Theme override: /themes/my-theme/rex/{module}/view.php
        if ( $module ) {
            $paths[] = get_stylesheet_directory() . '/rex/' . $moduleDir . '/' . $view . '.php';
        }

        // 2. Module-specific root (e.g., src/Modules/Supernatural/views/view.php)
        if ( $module && isset( self::$moduleViewRoots[ $module ] ) ) {
            $paths[] = self::$moduleViewRoots[ $module ] . '/' . $view . '.php';
        }

        // 3. Plugin-level fallback: views/modules/{module}/view.php
        if ( $module ) {
            $paths[] = WHX4_PLUGIN_DIR . 'views/modules/' . $moduleDir . '/' . $view . '.php';
        }

        // 4. Plugin-level fallback: views/view.php (shared/global)
        $paths[] = WHX4_PLUGIN_DIR . 'views/' . $view . '.php';

        foreach ( $paths as $path ) {
            if ( file_exists( $path ) ) {
                ob_start();
                extract( $vars, EXTR_SKIP );
                include $path;
                return ob_get_clean();
            }
        }

        return '<div class="notice notice-error"><p>' .
               esc_html( "View not found: $view (module: $moduleDir)" ) .
               '</p></div>';
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
     * Get the resolved full path to a view (first match), or null if not found.
     */
    public static function getViewPath( string $view, string $module = '' ): ?string
    {
        $paths = [];
        $moduleDir = $module ? strtolower( $module ) : '';

        if ( $module ) {
            $paths[] = get_stylesheet_directory() . '/whx4/' . $moduleDir . '/' . $view . '.php';
        }

        if ( $module && isset( self::$moduleViewRoots[ $module ] ) ) {
            $paths[] = self::$moduleViewRoots[ $module ] . '/' . $view . '.php';
        }

        if ( $module ) {
            $paths[] = WHX4_PLUGIN_DIR . 'views/modules/' . $moduleDir . '/' . $view . '.php';
        }

        $paths[] = WHX4_PLUGIN_DIR . 'views/' . $view . '.php';

        foreach ( $paths as $path ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        return null;
    }

}
